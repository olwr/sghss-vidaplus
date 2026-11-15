<?php

namespace Tests\Feature\Api;

use App\Domains\Agendamentos\Mail\AgendamentoCanceladoMail;
use App\Domains\Agendamentos\Mail\AgendamentoCriadoMail;
use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Enums\StatusAgendamento;
use App\Shared\Enums\StatusUnidade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ComApi;
use Tests\TestCase;

class AgendamentosTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    private function cenario(): array
    {
        $prof = $this->profissional();
        $this->agendaDeSegunda($prof);

        return [$prof, Paciente::factory()->create(['unidade_id' => $this->unidade->id]), $this->secretario()];
    }

    public function test_disponibilidade_lista_os_blocos_livres_do_dia(): void
    {
        [$prof, , $sec] = $this->cenario();
        $dia = $this->proximaSegunda(8)->format('Y-m-d');

        $r = $this->como($sec)->getJson("/api/v1/agendas/disponibilidade?profissional={$prof->uuid}&data={$dia}");

        $r->assertOk()->assertJsonCount(8, 'data');                       // 08:00–12:00 em blocos de 30 min
    }

    public function test_agendar_cria_envia_email_e_devolve_location(): void
    {
        [$prof, $pac, $sec] = $this->cenario();

        $r = $this->como($sec)->postJson('/api/v1/agendamentos', $this->corpoAgendamento($prof, $pac, $this->proximaSegunda(9)));

        $r->assertCreated()->assertHeader('Location')->assertJsonPath('data.status', 'agendado')->assertJsonPath('data.paciente_id', $pac->uuid);
        Mail::assertQueued(AgendamentoCriadoMail::class);
    }

    public function test_horario_ocupado_responde_409_e_horario_adjacente_e_aceito(): void
    {
        [$prof, $pac, $sec] = $this->cenario();
        $outro = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        $this->como($sec)->postJson('/api/v1/agendamentos', $this->corpoAgendamento($prof, $pac, $this->proximaSegunda(9)))->assertCreated();

        $this->como($sec)->postJson('/api/v1/agendamentos', $this->corpoAgendamento($prof, $outro, $this->proximaSegunda(9)))->assertStatus(409);
        $this->como($sec)->postJson('/api/v1/agendamentos', $this->corpoAgendamento($prof, $outro, $this->proximaSegunda(9, 30)))->assertCreated();
    }

    public function test_fora_da_agenda_do_profissional_responde_422(): void
    {
        [$prof, $pac, $sec] = $this->cenario();

        $this->como($sec)->postJson('/api/v1/agendamentos', $this->corpoAgendamento($prof, $pac, $this->proximaSegunda(15)))
            ->assertStatus(422);
    }

    public function test_unidade_nao_operacional_nao_recebe_agendamento(): void
    {
        [$prof, $pac, $sec] = $this->cenario();
        $this->unidade->update(['status' => StatusUnidade::EmReforma]);

        $this->como($sec)->postJson('/api/v1/agendamentos', $this->corpoAgendamento($prof, $pac, $this->proximaSegunda(9)))->assertStatus(422);
    }

    public function test_idempotency_key_repetida_nao_duplica(): void
    {
        [$prof, $pac, $sec] = $this->cenario();
        $corpo = $this->corpoAgendamento($prof, $pac, $this->proximaSegunda(10));

        $a = $this->como($sec)->withHeader('Idempotency-Key', 'chave-123')->postJson('/api/v1/agendamentos', $corpo);
        $b = $this->como($sec)->withHeader('Idempotency-Key', 'chave-123')->postJson('/api/v1/agendamentos', $corpo);

        $a->assertCreated();
        $b->assertOk()->assertHeader('Idempotent-Replayed', 'true');
        $this->assertSame($a->json('data.id'), $b->json('data.id'));
        $this->assertSame(1, Agendamento::withoutGlobalScopes()->count());
    }

    public function test_cancelar_libera_o_horario_e_avisa_o_paciente(): void
    {
        [$prof, $pac, $sec] = $this->cenario();
        $inicio = $this->proximaSegunda(11);
        $id = $this->como($sec)->postJson('/api/v1/agendamentos', $this->corpoAgendamento($prof, $pac, $inicio))->json('data.id');

        $this->como($sec)->postJson("/api/v1/agendamentos/{$id}/cancelar", ['motivo' => 'Paciente desistiu'])->assertNoContent();

        Mail::assertQueued(AgendamentoCanceladoMail::class);
        $this->assertDatabaseHas('agendamentos', ['uuid' => $id, 'status' => 'cancelado', 'motivo_cancelamento' => 'Paciente desistiu']);
        $this->como($sec)->postJson('/api/v1/agendamentos', $this->corpoAgendamento($prof, $pac, $inicio))->assertCreated();   // CT: libera o slot
    }

    public function test_remarcar_cria_novo_ligado_ao_anterior(): void
    {
        [$prof, $pac, $sec] = $this->cenario();
        $id = $this->como($sec)->postJson('/api/v1/agendamentos', $this->corpoAgendamento($prof, $pac, $this->proximaSegunda(9)))->json('data.id');

        $r = $this->como($sec)->postJson("/api/v1/agendamentos/{$id}/remarcar", ['inicio_em' => $this->proximaSegunda(10)->utc()->toIso8601String()]);

        $r->assertCreated();
        $this->assertDatabaseHas('agendamentos', ['uuid' => $id, 'status' => 'remarcado']);
        $this->assertNotSame($id, $r->json('data.id'));
        $this->assertSame(2, Agendamento::withoutGlobalScopes()->count());
    }

    public function test_transicao_invalida_responde_422(): void
    {
        [$prof, $pac, $sec] = $this->cenario();
        $id = $this->como($sec)->postJson('/api/v1/agendamentos', $this->corpoAgendamento($prof, $pac, $this->proximaSegunda(9)))->json('data.id');

        $this->como($sec)->postJson("/api/v1/agendamentos/{$id}/confirmar")->assertOk()->assertJsonPath('data.status', 'confirmado');
        $this->como($sec)->postJson("/api/v1/agendamentos/{$id}/cancelar")->assertNoContent();
        $this->como($sec)->postJson("/api/v1/agendamentos/{$id}/confirmar")->assertStatus(422);       // cancelado é final
    }

    public function test_so_o_profissional_do_agendamento_inicia_e_conclui(): void
    {
        [$prof, $pac, $sec] = $this->cenario();
        $id = $this->como($sec)->postJson('/api/v1/agendamentos', $this->corpoAgendamento($prof, $pac, $this->proximaSegunda(9)))->json('data.id');
        $this->como($sec)->postJson("/api/v1/agendamentos/{$id}/confirmar")->assertOk();

        $this->como($sec)->postJson("/api/v1/agendamentos/{$id}/iniciar")->assertForbidden();
        $this->como($this->profissional()->usuario)->postJson("/api/v1/agendamentos/{$id}/iniciar")->assertForbidden();   // outro profissional
        $this->como($prof->usuario)->postJson("/api/v1/agendamentos/{$id}/iniciar")->assertOk()->assertJsonPath('data.status', 'em_atendimento');
        $this->como($prof->usuario)->postJson("/api/v1/agendamentos/{$id}/concluir")->assertOk()->assertJsonPath('data.status', 'concluido');
    }

    public function test_paciente_agenda_para_si_e_nao_ve_agendamento_de_outro(): void
    {
        [$prof, , ] = $this->cenario();
        $eu = $this->pacienteComAcesso();
        $outro = Paciente::factory()->comAcesso()->create(['unidade_id' => $this->unidade->id]);

        $r = $this->como($eu->usuario)->postJson('/api/v1/agendamentos', $this->corpoAgendamento($prof, $outro, $this->proximaSegunda(9), ['valor' => 999]));
        $r->assertCreated()->assertJsonPath('data.paciente_id', $eu->uuid);                     // o paciente_id enviado é ignorado
        $this->assertArrayNotHasKey('valor', $r->json('data'));                                  // paciente não vê valores

        $alheio = Agendamento::factory()->create(['paciente_id' => $outro->id, 'profissional_id' => $prof->id, 'unidade_id' => $this->unidade->id]);
        $this->como($eu->usuario)->getJson("/api/v1/agendamentos/{$alheio->uuid}")->assertForbidden();
        $this->como($eu->usuario)->getJson('/api/v1/agendamentos')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_teleconsulta_nasce_com_sala_e_janela_de_validade(): void
    {
        [$prof, $pac, $sec] = $this->cenario();

        $r = $this->como($sec)->postJson('/api/v1/agendamentos', $this->corpoAgendamento($prof, $pac, $this->proximaSegunda(9), ['modalidade' => 'telemedicina']));

        $r->assertCreated();
        $this->assertNotNull($r->json('data.sala_telemedicina_id'));
        $this->assertDatabaseCount('telemedicina_salas', 1);
    }

    public function test_agendamento_de_outra_unidade_fica_invisivel(): void
    {
        $outra = Unidade::factory()->create();
        $alheio = Agendamento::factory()->create([
            'paciente_id' => Paciente::factory()->create(['unidade_id' => $outra->id])->id,
            'profissional_id' => $this->profissional($outra)->id, 'unidade_id' => $outra->id,
        ]);

        $this->como($this->secretario())->getJson("/api/v1/agendamentos/{$alheio->uuid}")->assertNotFound();
        $this->assertSame(StatusAgendamento::Agendado, $alheio->refresh()->status);
    }
}

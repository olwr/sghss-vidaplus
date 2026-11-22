<?php

namespace Tests\Feature\Api;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Telemedicina\Models\SalaTelemedicina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ComApi;
use Tests\TestCase;

class TelemedicinaTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    /** @return array{0: SalaTelemedicina, 1: \App\Domains\Profissionais\Models\Profissional, 2: \App\Domains\Pacientes\Models\Paciente} */
    private function sala(): array
    {
        $prof = $this->profissional();
        $pac = $this->pacienteComAcesso();
        $ag = Agendamento::factory()->telemedicina()->em(now()->addMinutes(5))->create([
            'paciente_id' => $pac->id, 'profissional_id' => $prof->id, 'unidade_id' => $this->unidade->id,
        ]);

        return [SalaTelemedicina::factory()->create(['agendamento_id' => $ag->id]), $prof, $pac];
    }

    private function payload(string $jwt): array
    {
        return json_decode(base64_decode(strtr(explode('.', $jwt)[1], '-_', '+/')), true);
    }

    public function test_participantes_recebem_jwt_e_somente_o_profissional_e_moderador(): void
    {
        [$sala, $prof, $pac] = $this->sala();

        $p = $this->como($prof->usuario)->postJson("/api/v1/telemedicina/salas/{$sala->uuid}/entrar")->assertOk();
        $c = $this->como($pac->usuario)->postJson("/api/v1/telemedicina/salas/{$sala->uuid}/entrar")->assertOk();

        $this->assertCount(3, explode('.', $p->json('jwt')));
        $this->assertTrue($this->payload($p->json('jwt'))['context']['user']['moderator']);
        $this->assertFalse($this->payload($c->json('jwt'))['context']['user']['moderator']);
        $this->assertSame($sala->nome_sala, $this->payload($c->json('jwt'))['room']);
        $this->assertDatabaseCount('telemedicina_logs_sessao', 2);
        $this->assertDatabaseHas('telemedicina_salas', ['id' => $sala->id, 'status' => 'em_andamento']);
    }

    public function test_quem_nao_participa_recebe_403_inclusive_o_administrador(): void
    {
        [$sala] = $this->sala();

        $this->como($this->admin())->postJson("/api/v1/telemedicina/salas/{$sala->uuid}/entrar")->assertForbidden();
        $this->como($this->profissional()->usuario)->postJson("/api/v1/telemedicina/salas/{$sala->uuid}/entrar")->assertForbidden();
    }

    public function test_fora_da_janela_de_validade_responde_403(): void
    {
        [$sala, $prof] = $this->sala();
        $sala->update(['valida_a_partir_de' => now()->addHours(3), 'valida_ate' => now()->addHours(4)]);

        $this->como($prof->usuario)->postJson("/api/v1/telemedicina/salas/{$sala->uuid}/entrar")->assertForbidden();
    }

    public function test_so_o_profissional_encerra_e_sala_encerrada_nao_aceita_entrada(): void
    {
        [$sala, $prof, $pac] = $this->sala();

        $this->como($pac->usuario)->postJson("/api/v1/telemedicina/salas/{$sala->uuid}/encerrar")->assertForbidden();
        $this->como($prof->usuario)->postJson("/api/v1/telemedicina/salas/{$sala->uuid}/encerrar")->assertOk()->assertJsonPath('status', 'encerrada');
        $this->como($pac->usuario)->postJson("/api/v1/telemedicina/salas/{$sala->uuid}/entrar")->assertForbidden();
    }

    public function test_eventos_aceitam_apenas_os_tipos_do_cliente(): void
    {
        [$sala, , $pac] = $this->sala();

        $this->como($pac->usuario)->postJson("/api/v1/telemedicina/salas/{$sala->uuid}/eventos", ['evento' => 'verificacao_dispositivo'])->assertNoContent();
        $this->como($pac->usuario)->postJson("/api/v1/telemedicina/salas/{$sala->uuid}/eventos", ['evento' => 'token_emitido'])->assertStatus(422);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Domains\Internacoes\Models\Internacao;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Enums\StatusInternacao;
use App\Shared\Enums\StatusUnidade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ComApi;
use Tests\TestCase;

class TransferenciasTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    private function corpo(Paciente $p, Unidade $destino): array
    {
        return ['tipo' => 'paciente', 'paciente_id' => $p->uuid, 'unidade_destino_id' => $destino->uuid, 'motivo' => 'Especialidade indisponível'];
    }

    public function test_secretario_transfere_paciente_e_o_escopo_acompanha(): void
    {
        $destino = Unidade::factory()->create();
        $pac = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        $sec = $this->secretario();

        $this->como($sec)->postJson('/api/v1/transferencias', $this->corpo($pac, $destino))
            ->assertCreated()->assertHeader('Location')->assertJsonPath('data.status', 'concluida');

        $this->assertSame($destino->id, $pac->refresh()->unidade_id);
        $this->como($sec)->getJson("/api/v1/pacientes/{$pac->uuid}")->assertNotFound();               // saiu da unidade do secretário
    }

    public function test_destino_nao_operacional_ou_igual_a_origem_responde_422(): void
    {
        $pac = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        $fechada = Unidade::factory()->create(['status' => StatusUnidade::Desativada]);
        $sec = $this->secretario();

        $this->como($sec)->postJson('/api/v1/transferencias', $this->corpo($pac, $fechada))->assertStatus(422);       // CT011
        $this->como($sec)->postJson('/api/v1/transferencias', $this->corpo($pac, $this->unidade))->assertStatus(422);
    }

    public function test_paciente_internado_nao_pode_ser_transferido(): void
    {
        $destino = Unidade::factory()->create();
        $pac = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        Internacao::factory()->create(['paciente_id' => $pac->id, 'solicitante_id' => $this->profissional()->id, 'status' => StatusInternacao::Solicitada]);

        $this->como($this->secretario())->postJson('/api/v1/transferencias', $this->corpo($pac, $destino))->assertStatus(422);
        $this->assertSame($this->unidade->id, $pac->refresh()->unidade_id);                          // a transação não alterou nada
    }

    public function test_transferir_profissional_e_so_da_administracao(): void
    {
        $destino = Unidade::factory()->create();
        $prof = $this->profissional();
        $corpo = ['tipo' => 'profissional', 'profissional_id' => $prof->uuid, 'unidade_destino_id' => $destino->uuid];

        $this->como($this->secretario())->postJson('/api/v1/transferencias', $corpo)->assertForbidden();
        $this->como($this->admin())->postJson('/api/v1/transferencias', $corpo)->assertCreated();
        $this->assertSame($destino->id, $prof->refresh()->unidade_id);
        $this->assertSame($destino->id, $prof->usuario->refresh()->unidade_id);
    }

    public function test_paciente_e_profissional_juntos_violam_a_validacao(): void
    {
        $destino = Unidade::factory()->create();
        $pac = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);

        $this->como($this->secretario())->postJson('/api/v1/transferencias', $this->corpo($pac, $destino) + ['profissional_id' => $this->profissional()->uuid])
            ->assertStatus(422);
    }
}

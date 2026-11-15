<?php

namespace Tests\Feature\Api;

use App\Domains\Internacoes\Models\Leito;
use App\Domains\Internacoes\Models\Suprimento;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Unidades\Models\Unidade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ComApi;
use Tests\TestCase;

class InternacoesTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    private function solicitar(\App\Domains\Profissionais\Models\Profissional $prof, Paciente $pac): string
    {
        return $this->como($prof->usuario)->postJson('/api/v1/internacoes', ['paciente_id' => $pac->uuid, 'motivo' => 'Pneumonia'])
            ->assertCreated()->json('data.id');
    }

    public function test_fluxo_solicitar_admitir_e_dar_alta_libera_o_leito(): void
    {
        $prof = $this->profissional();
        $pac = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        $leito = Leito::factory()->create(['unidade_id' => $this->unidade->id]);
        $admin = $this->admin();

        $id = $this->solicitar($prof, $pac);
        $this->como($admin)->postJson("/api/v1/internacoes/{$id}/admitir", ['leito_id' => $leito->uuid])->assertOk()->assertJsonPath('data.status', 'ativa');
        $this->assertDatabaseHas('leitos', ['id' => $leito->id, 'status' => 'ocupado']);

        $this->como($admin)->postJson("/api/v1/internacoes/{$id}/alta")->assertOk()->assertJsonPath('data.status', 'alta');
        $this->assertDatabaseHas('leitos', ['id' => $leito->id, 'status' => 'disponivel']);
    }

    public function test_leito_ocupado_responde_409_e_paciente_nao_tem_duas_internacoes(): void
    {
        $prof = $this->profissional();
        $a = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        $b = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        $leito = Leito::factory()->create(['unidade_id' => $this->unidade->id]);
        $admin = $this->admin();

        $ia = $this->solicitar($prof, $a);
        $ib = $this->solicitar($prof, $b);
        $this->como($admin)->postJson("/api/v1/internacoes/{$ia}/admitir", ['leito_id' => $leito->uuid])->assertOk();
        $this->como($admin)->postJson("/api/v1/internacoes/{$ib}/admitir", ['leito_id' => $leito->uuid])->assertStatus(409);

        $this->como($prof->usuario)->postJson('/api/v1/internacoes', ['paciente_id' => $a->uuid])->assertStatus(409);
    }

    public function test_leito_de_outra_unidade_e_so_a_administracao_admite(): void
    {
        $prof = $this->profissional();
        $pac = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        $outro = Leito::factory()->create(['unidade_id' => Unidade::factory()->create()->id]);

        $id = $this->solicitar($prof, $pac);
        $this->como($this->secretario())->postJson("/api/v1/internacoes/{$id}/admitir", ['leito_id' => $outro->uuid])->assertForbidden();
        $this->como($this->admin())->postJson("/api/v1/internacoes/{$id}/admitir", ['leito_id' => $outro->uuid])->assertStatus(422);
    }

    public function test_leito_em_manutencao_nao_pode_ser_ocupado(): void
    {
        $leito = Leito::factory()->create(['unidade_id' => $this->unidade->id]);
        $admin = $this->admin();

        $this->como($admin)->patchJson("/api/v1/leitos/{$leito->uuid}", ['status' => 'manutencao'])->assertOk()->assertJsonPath('data.status', 'manutencao');
        $id = $this->solicitar($this->profissional(), Paciente::factory()->create(['unidade_id' => $this->unidade->id]));
        $this->como($admin)->postJson("/api/v1/internacoes/{$id}/admitir", ['leito_id' => $leito->uuid])->assertStatus(409);
    }

    public function test_estoque_nao_fica_negativo_e_alerta_abaixo_do_minimo(): void
    {
        $s = Suprimento::factory()->create(['unidade_id' => $this->unidade->id, 'quantidade_atual' => 60, 'quantidade_minima' => 50]);
        $admin = $this->admin();

        $this->como($admin)->postJson("/api/v1/suprimentos/{$s->uuid}/movimentar", ['quantidade' => -100])->assertStatus(422);
        $this->assertSame(60, $s->refresh()->quantidade_atual);

        $this->como($admin)->postJson("/api/v1/suprimentos/{$s->uuid}/movimentar", ['quantidade' => -20, 'motivo' => 'Uso na UTI'])
            ->assertOk()->assertJsonPath('data.abaixo_do_minimo', true);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'suprimentos', 'event' => 'abaixo_do_minimo']);
        $this->como($this->secretario())->getJson('/api/v1/suprimentos?abaixo_do_minimo=1')->assertOk()->assertJsonCount(1, 'data');
        $this->como($this->secretario())->postJson("/api/v1/suprimentos/{$s->uuid}/movimentar", ['quantidade' => 5])->assertForbidden();
    }
}

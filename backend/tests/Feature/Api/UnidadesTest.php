<?php

namespace Tests\Feature\Api;

use App\Shared\Enums\StatusUnidade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ComApi;
use Tests\TestCase;

class UnidadesTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    public function test_administrador_cria_unidade_com_location(): void
    {
        $r = $this->como($this->admin())->postJson('/api/v1/unidades', [
            'tipo' => 'clinica', 'nome' => 'Clínica Norte', 'cnpj' => '11.222.333/0001-81', 'cidade' => 'Pelotas', 'uf' => 'RS',
        ]);

        $r->assertCreated()->assertHeader('Location')->assertJsonPath('data.nome', 'Clínica Norte');
        $this->assertDatabaseHas('unidades', ['nome' => 'Clínica Norte', 'status' => 'ativa']);
    }

    public function test_secretario_nao_gerencia_unidades_mas_consulta(): void
    {
        $sec = $this->secretario();

        $this->como($sec)->postJson('/api/v1/unidades', ['tipo' => 'clinica', 'nome' => 'X'])->assertForbidden();
        $this->como($sec)->getJson('/api/v1/unidades')->assertOk();
    }

    public function test_filtro_por_status_e_validacao_do_valor(): void
    {
        $this->unidade->update(['status' => StatusUnidade::EmReforma]);
        $outra = \App\Domains\Unidades\Models\Unidade::factory()->create();
        $admin = $this->admin();

        $r = $this->como($admin)->getJson('/api/v1/unidades?status=ativa');
        $r->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $outra->uuid);
        $this->como($admin)->getJson('/api/v1/unidades?status=inexistente')->assertStatus(422);
    }

    public function test_alterar_status_invalida_o_cache_e_registra_auditoria(): void
    {
        $admin = $this->admin();
        $this->como($admin)->getJson('/api/v1/unidades?status=ativa')->assertJsonCount(1, 'data');   // aquece o cache

        $this->como($admin)->patchJson("/api/v1/unidades/{$this->unidade->uuid}/status", ['status' => 'desativada', 'motivo' => 'Obra'])
            ->assertOk()->assertJsonPath('data.status', 'desativada');

        $this->como($admin)->getJson('/api/v1/unidades?status=ativa')->assertJsonCount(0, 'data');
        $this->assertDatabaseHas('activity_log', ['log_name' => 'unidades', 'event' => 'status_alterado']);
    }

    public function test_cnpj_duplicado_e_rejeitado(): void
    {
        $this->unidade->update(['cnpj' => '11222333000181']);

        $this->como($this->admin())->postJson('/api/v1/unidades', ['tipo' => 'clinica', 'nome' => 'Y', 'cnpj' => '11222333000181'])
            ->assertStatus(422)->assertJsonValidationErrors('cnpj');
    }
}

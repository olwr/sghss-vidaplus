<?php

namespace Tests\Feature\Api;

use App\Domains\Seguranca\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ComApi;
use Tests\TestCase;

class AdministracaoTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    public function test_desativar_usuario_derruba_as_sessoes_abertas(): void
    {
        $alvo = Usuario::factory()->secretario()->create(['unidade_id' => $this->unidade->id]);
        $alvo->createToken('api');
        $alvo->createToken('mobile');

        $this->como($this->admin())->patchJson("/api/v1/admin/usuarios/{$alvo->uuid}", ['ativo' => false])
            ->assertOk()->assertJsonPath('data.ativo', false);

        $this->assertSame(0, $alvo->tokens()->count());                                                   // CT015
    }

    public function test_trocar_perfil_sincroniza_o_papel_e_revoga_tokens(): void
    {
        $alvo = Usuario::factory()->secretario()->create(['unidade_id' => $this->unidade->id]);
        $alvo->createToken('api');

        $this->como($this->admin())->patchJson("/api/v1/admin/usuarios/{$alvo->uuid}", ['perfil' => 'profissional_saude'])
            ->assertOk()->assertJsonPath('data.perfis.0', 'profissional_saude');

        $this->assertFalse($alvo->refresh()->hasRole('secretario'));
        $this->assertSame(0, $alvo->tokens()->count());
    }

    public function test_administrador_nao_desativa_nem_rebaixa_a_si_mesmo(): void
    {
        $adm = $this->admin();

        $this->como($adm)->patchJson("/api/v1/admin/usuarios/{$adm->uuid}", ['ativo' => false])->assertStatus(422);
        $this->como($adm)->patchJson("/api/v1/admin/usuarios/{$adm->uuid}", ['perfil' => 'secretario'])->assertStatus(422);
    }

    public function test_so_administrador_gerencia_usuarios_e_ve_a_matriz_de_perfis(): void
    {
        $sec = $this->secretario();

        $this->como($sec)->getJson('/api/v1/admin/usuarios')->assertForbidden();
        $this->como($sec)->getJson('/api/v1/admin/perfis')->assertForbidden();

        $r = $this->como($this->admin())->getJson('/api/v1/admin/perfis')->assertOk();
        $this->assertCount(4, $r->json('data'));
        $adm = collect($r->json('data'))->firstWhere('perfil', 'administrador');
        $this->assertNotContains('prontuarios.ver', $adm['permissoes']);                                   // administrador não é clínico
    }

    public function test_listagem_filtra_por_perfil_e_pagina(): void
    {
        Usuario::factory()->count(3)->secretario()->create(['unidade_id' => $this->unidade->id]);
        Usuario::factory()->count(2)->profissionalSaude()->create(['unidade_id' => $this->unidade->id]);

        $r = $this->como($this->admin())->getJson('/api/v1/admin/usuarios?perfil=secretario&per_page=2');

        $r->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 3);
    }
}

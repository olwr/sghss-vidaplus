<?php

namespace Tests\Feature\Api;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Prontuarios\Models\Exame;
use App\Domains\Prontuarios\Models\Receita;
use App\Domains\Prontuarios\Models\ReceitaItem;
use App\Domains\Unidades\Models\Unidade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ComApi;
use Tests\TestCase;

/** Parte 8.1: o que o front do paciente precisa e a API v1 ainda não entregava. */
class AutoatendimentoPacienteTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    public function test_paciente_lista_profissionais_por_unidade_sem_email_nem_cpf(): void
    {
        $prof = $this->profissional();
        $outraUnidade = Unidade::factory()->create();
        $this->profissional($outraUnidade);

        $r = $this->como($this->pacienteComAcesso()->usuario)
            ->getJson('/api/v1/profissionais?unidade='.$prof->unidade->uuid);

        $r->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $prof->uuid);
        $this->assertArrayNotHasKey('email', $r->json('data.0'));
        $this->assertArrayNotHasKey('cpf', $r->json('data.0'));
        $this->assertNotEmpty($r->json('data.0.nome'));
    }

    public function test_secretario_continua_vendo_email_e_cpf_mascarado_do_profissional(): void
    {
        $this->profissional();

        $r = $this->como($this->secretario())->getJson('/api/v1/profissionais');

        $this->assertArrayHasKey('email', $r->json('data.0'));
        $this->assertArrayHasKey('cpf', $r->json('data.0'));
    }

    public function test_agendamento_traz_os_nomes_para_a_tela(): void
    {
        $eu = $this->pacienteComAcesso();
        $prof = $this->profissional();
        Agendamento::factory()->create([
            'paciente_id' => $eu->id, 'profissional_id' => $prof->id, 'unidade_id' => $prof->unidade_id,
        ]);

        $r = $this->como($eu->usuario)->getJson('/api/v1/agendamentos');

        $r->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.profissional_nome', $prof->usuario->nome)
            ->assertJsonPath('data.0.unidade_nome', $prof->unidade->nome)
            ->assertJsonPath('data.0.paciente_nome', $eu->nome);
    }

    public function test_titular_lista_as_proprias_receitas_e_exames_e_so_elas(): void
    {
        $eu = $this->pacienteComAcesso();
        $outro = $this->pacienteComAcesso();
        $receita = Receita::factory()->create(['paciente_id' => $eu->id]);
        ReceitaItem::factory()->create(['receita_id' => $receita->id]);
        Receita::factory()->create(['paciente_id' => $outro->id]);
        $exame = Exame::factory()->create(['paciente_id' => $eu->id]);
        Exame::factory()->create(['paciente_id' => $outro->id]);

        $this->como($eu->usuario)->getJson('/api/v1/titular/receitas')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $receita->uuid)->assertJsonCount(1, 'data.0.itens');
        $this->como($eu->usuario)->getJson('/api/v1/titular/exames')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $exame->uuid)->assertJsonPath('data.0.resultado_disponivel', false);
    }

    public function test_so_o_paciente_usa_receitas_e_exames_do_titular(): void
    {
        $this->como($this->secretario())->getJson('/api/v1/titular/receitas')->assertForbidden();
        $this->como($this->secretario())->getJson('/api/v1/titular/exames')->assertForbidden();
        $this->como($this->admin())->getJson('/api/v1/titular/receitas')->assertForbidden();
    }
}

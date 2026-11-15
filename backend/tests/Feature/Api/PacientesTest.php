<?php

namespace Tests\Feature\Api;

use App\Domains\Pacientes\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ComApi;
use Tests\TestCase;

class PacientesTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    private function corpo(array $extra = []): array
    {
        return $extra + [
            'nome' => 'João da Silva', 'cpf' => '529.982.247-25', 'data_nascimento' => '1985-03-20', 'sexo' => 'masculino',
            'email' => 'joao@exemplo.test', 'unidade_id' => $this->unidade->uuid, 'consentimento' => true,
        ];
    }

    public function test_secretario_cadastra_paciente_com_cpf_mascarado_e_consentimento(): void
    {
        $r = $this->como($this->secretario())->postJson('/api/v1/pacientes', $this->corpo());

        $r->assertCreated()->assertHeader('Location')->assertJsonPath('data.cpf', '***.***.***-25');
        $this->assertStringNotContainsString('52998224725', $r->getContent());                 // CT020
        $this->assertStringNotContainsString('cpf_hash', $r->getContent());
        $this->assertDatabaseHas('consentimentos', ['finalidade' => 'atendimento_saude']);
    }

    public function test_cpf_duplicado_responde_409(): void
    {
        $sec = $this->secretario();
        $this->como($sec)->postJson('/api/v1/pacientes', $this->corpo())->assertCreated();

        $this->como($sec)->postJson('/api/v1/pacientes', $this->corpo(['nome' => 'Outro', 'cpf' => '52998224725']))->assertStatus(409);
    }

    public function test_cpf_invalido_responde_422(): void
    {
        $this->como($this->secretario())->postJson('/api/v1/pacientes', $this->corpo(['cpf' => '111.111.111-11']))
            ->assertStatus(422)->assertJsonValidationErrors('cpf');
    }

    public function test_secretario_nao_cadastra_em_outra_unidade(): void
    {
        $outra = \App\Domains\Unidades\Models\Unidade::factory()->create();

        $this->como($this->secretario())->postJson('/api/v1/pacientes', $this->corpo(['unidade_id' => $outra->uuid]))->assertForbidden();
    }

    public function test_secretario_nao_enxerga_paciente_de_outra_unidade(): void
    {
        $outra = \App\Domains\Unidades\Models\Unidade::factory()->create();
        $estrangeiro = Paciente::factory()->create(['unidade_id' => $outra->id]);
        $sec = $this->secretario();

        $this->como($sec)->getJson("/api/v1/pacientes/{$estrangeiro->uuid}")->assertNotFound();       // RNF006: 404, não 403
        $this->como($sec)->getJson('/api/v1/pacientes')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_busca_por_cpf_usa_o_blind_index(): void
    {
        $p = Paciente::factory()->create(['unidade_id' => $this->unidade->id, 'cpf' => '52998224725']);
        Paciente::factory()->count(2)->create(['unidade_id' => $this->unidade->id]);

        $r = $this->como($this->secretario())->getJson('/api/v1/pacientes?cpf=529.982.247-25');

        $r->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $p->uuid);
    }

    public function test_paciente_ve_somente_a_si_mesmo(): void
    {
        $eu = $this->pacienteComAcesso();
        $outro = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);

        $this->como($eu->usuario)->getJson("/api/v1/pacientes/{$eu->uuid}")->assertOk();
        $this->como($eu->usuario)->getJson("/api/v1/pacientes/{$outro->uuid}")->assertForbidden();
        $this->como($eu->usuario)->getJson('/api/v1/pacientes')->assertForbidden();              // listar é da equipe
    }

    public function test_atualizacao_nao_altera_o_cpf_e_exclusao_e_logica(): void
    {
        $p = Paciente::factory()->create(['unidade_id' => $this->unidade->id, 'cpf' => '52998224725']);
        $adm = $this->admin();

        $this->como($adm)->patchJson("/api/v1/pacientes/{$p->uuid}", ['nome' => 'Novo Nome', 'cpf' => '11144477735'])->assertOk();
        $this->assertSame('52998224725', $p->refresh()->cpf);
        $this->assertSame('Novo Nome', $p->nome);

        $this->como($adm)->deleteJson("/api/v1/pacientes/{$p->uuid}")->assertNoContent();
        $this->assertSoftDeleted('pacientes', ['id' => $p->id]);
    }
}

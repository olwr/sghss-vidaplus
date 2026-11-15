<?php

namespace Tests\Feature\Api;

use App\Domains\Prontuarios\Models\Prontuario;
use App\Domains\Prontuarios\Models\RegistroClinico;
use App\Domains\Relatorios\Jobs\GerarExportacao;
use App\Domains\Pacientes\Models\Paciente;
use App\Shared\Enums\AcaoAcesso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ComApi;
use Tests\TestCase;

class RelatoriosAuditoriaLgpdTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    // ---------- relatórios
    public function test_relatorio_gerencial_so_para_a_administracao(): void
    {
        $r = $this->como($this->admin())->getJson('/api/v1/relatorios/ocupacao-leitos');
        $r->assertOk()->assertJsonStructure(['titulo', 'colunas', 'linhas']);

        $this->como($this->secretario())->getJson('/api/v1/relatorios/ocupacao-leitos')->assertForbidden();
        $this->como($this->admin())->getJson('/api/v1/relatorios/inexistente')->assertStatus(422);
    }

    public function test_exportacao_e_assincrona_e_privada_do_solicitante(): void
    {
        Queue::fake();
        $dono = $this->admin();

        $r = $this->como($dono)->postJson('/api/v1/relatorios/exportacoes', ['relatorio' => 'consultas', 'formato' => 'xlsx']);

        $r->assertStatus(202)->assertJsonPath('status', 'processando');
        Queue::assertPushed(GerarExportacao::class, fn ($job) => $job->queue === 'relatorios');
        $id = $r->json('id');
        $this->como($dono)->getJson("/api/v1/relatorios/exportacoes/{$id}")->assertOk()->assertJsonPath('status', 'processando');
        $this->como($this->admin())->getJson("/api/v1/relatorios/exportacoes/{$id}")->assertNotFound();      // de outro usuário
    }

    // ---------- auditoria
    public function test_auditoria_e_somente_leitura_e_restrita(): void
    {
        $adm = $this->admin();

        $this->como($adm)->getJson('/api/v1/auditoria/atividades')->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->como($adm)->getJson('/api/v1/auditoria/conformidade-lgpd')->assertOk()->assertJsonStructure(['consentimentos_vigentes', 'exportacoes_30d']);
        $this->como($this->profissional()->usuario)->getJson('/api/v1/auditoria/atividades')->assertForbidden();

        foreach (['put', 'patch', 'delete'] as $metodo) {
            $this->como($adm)->json($metodo, '/api/v1/auditoria/atividades/1')->assertForbidden();             // CT029
        }
    }

    public function test_listagem_de_acessos_mostra_quem_viu_o_prontuario(): void
    {
        $prof = $this->profissional();
        $pac = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        Prontuario::factory()->create(['paciente_id' => $pac->id]);
        $this->como($prof->usuario)->getJson("/api/v1/pacientes/{$pac->uuid}/prontuario")->assertOk();

        $r = $this->como($this->admin())->getJson("/api/v1/auditoria/acessos?paciente={$pac->uuid}");

        $r->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.usuario', $prof->usuario->nome)->assertJsonPath('data.0.acao', 'leitura');
    }

    // ---------- LGPD: direitos do titular
    public function test_titular_exporta_os_proprios_dados_incluindo_quem_os_acessou(): void
    {
        $eu = $this->pacienteComAcesso();
        $prof = $this->profissional();
        Prontuario::factory()->create(['paciente_id' => $eu->id]);
        $this->como($prof->usuario)->getJson("/api/v1/pacientes/{$eu->uuid}/prontuario")->assertOk();

        $r = $this->como($eu->usuario)->getJson('/api/v1/titular/exportacao');

        $r->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="meus-dados.json"')
            ->assertJsonStructure(['cadastro', 'consentimentos', 'prontuario', 'acessos_aos_meus_dados']);
        $acoes = array_column($r->json('acessos_aos_meus_dados'), 'acao');
        $this->assertCount(2, $acoes);
        $this->assertContains('leitura', $acoes);
        $this->assertContains('exportacao', $acoes);
        $this->assertSame('***.***.***-'.substr($eu->cpf, 9, 2), $r->json('cadastro.cpf'));
        $this->assertDatabaseHas('logs_acesso', ['acao' => AcaoAcesso::Exportacao->value, 'recurso_tipo' => 'dados_titular']);
    }

    public function test_titular_concede_e_revoga_consentimento_mas_nao_o_de_atendimento(): void
    {
        $eu = $this->pacienteComAcesso();

        $this->como($eu->usuario)->postJson('/api/v1/titular/consentimentos', ['finalidade' => 'comunicacao'])->assertCreated();
        $this->como($eu->usuario)->deleteJson('/api/v1/titular/consentimentos/comunicacao')->assertNoContent();
        $this->assertNotNull(DB::table('consentimentos')->where('finalidade', 'comunicacao')->value('revogado_em'));
        $this->como($eu->usuario)->deleteJson('/api/v1/titular/consentimentos/atendimento_saude')->assertStatus(422);
        $this->como($eu->usuario)->postJson('/api/v1/titular/consentimentos', ['finalidade' => 'qualquer-coisa'])->assertStatus(422);
    }

    public function test_secretario_nao_usa_os_endpoints_do_titular(): void
    {
        $this->como($this->secretario())->getJson('/api/v1/titular/meus-dados')->assertForbidden();
    }

    public function test_anonimizacao_respeita_a_guarda_legal_do_prontuario(): void
    {
        $adm = $this->admin();
        $comGuarda = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        $pront = Prontuario::factory()->create(['paciente_id' => $comGuarda->id]);
        RegistroClinico::factory()->create(['prontuario_id' => $pront->id, 'registrado_em' => now()->subYear()]);

        $this->como($adm)->postJson("/api/v1/lgpd/pacientes/{$comGuarda->uuid}/anonimizar")->assertStatus(422);

        $livre = $this->pacienteComAcesso();
        $cpf = $livre->cpf;
        $this->como($adm)->postJson("/api/v1/lgpd/pacientes/{$livre->uuid}/anonimizar")->assertOk();

        $livre->refresh();
        $this->assertSame('ANONIMIZADO', $livre->nome);
        $this->assertNotNull($livre->anonimizado_em);
        $this->assertStringNotContainsString($cpf, json_encode(DB::table('pacientes')->where('id', $livre->id)->first()));
        $this->assertFalse((bool) $livre->usuario->refresh()->ativo);
        $this->como($adm)->postJson("/api/v1/lgpd/pacientes/{$livre->uuid}/anonimizar")->assertStatus(422);       // já anonimizado
    }
}

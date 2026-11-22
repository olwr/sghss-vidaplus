<?php

namespace Tests\Feature\Api;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Seguranca\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ComApi;
use Tests\TestCase;

/**
 * Parte 9: casos do plano de testes que ainda não tinham teste automatizado (matriz CT → teste, Passo 9.1).
 * CT002, CT009, CT017, CT018, CT024, CT032, CT036, CT042, CT043 e CT045.
 */
class CasosComplementaresTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    // CT002 — sem CPF
    public function test_ct002_cadastro_sem_cpf_responde_422(): void
    {
        $r = $this->como($this->secretario())->postJson('/api/v1/pacientes', [
            'nome' => 'Sem CPF', 'data_nascimento' => '1990-01-01', 'sexo' => 'feminino',
            'unidade_id' => $this->unidade->uuid, 'consentimento' => true,
        ]);

        $r->assertStatus(422)->assertJsonValidationErrors('cpf');
        $this->assertDatabaseMissing('pacientes', ['nome' => 'Sem CPF']);
    }

    // CT005 (variação) — sem token e SEM `Accept: application/json` (como faz o Insomnia/curl) continua 401, nunca 500
    public function test_ct005_sem_token_e_sem_accept_json_responde_401(): void
    {
        $this->get('/api/v1/auth/me', ['Accept' => '*/*'])
            ->assertStatus(401)
            ->assertHeader('Content-Type', 'application/problem+json');
        $this->call('PUT', '/api/v1/auditoria/qualquer', [], [], [], ['HTTP_ACCEPT' => '*/*'])->assertStatus(401);
    }

    // CT009 — prontuário para paciente inexistente
    public function test_ct009_prontuario_de_paciente_inexistente_responde_422(): void
    {
        $prof = $this->profissional();

        $this->como($prof->usuario)->postJson('/api/v1/prontuarios', ['paciente_id' => '00000000-0000-4000-8000-000000000000'])
            ->assertStatus(422);
    }

    // CT017 — os 4 tipos de unidade
    /** @return array<string, array{string, string}> */
    public static function tiposDeUnidade(): array
    {
        return [
            'hospital' => ['hospital', '11.222.333/0001-81'],
            'clinica' => ['clinica', '45.723.174/0001-10'],
            'laboratorio' => ['laboratorio', '34.028.316/0001-03'],
            'home care' => ['home_care', '07.526.557/0001-00'],
        ];
    }

    #[DataProvider('tiposDeUnidade')]
    public function test_ct017_administrador_cadastra_cada_tipo_de_unidade(string $tipo, string $cnpj): void
    {
        $this->como($this->admin())->postJson('/api/v1/unidades', [
            'tipo' => $tipo, 'nome' => "Unidade {$tipo}", 'cnpj' => $cnpj, 'cidade' => 'Pelotas', 'uf' => 'RS',
        ])->assertCreated()->assertJsonPath('data.tipo', $tipo);
    }

    // CT018 — médico, enfermeiro e técnico
    /** @return array<string, array{string, string}> */
    public static function tiposDeProfissional(): array
    {
        return [
            'médico' => ['medico', '52998224725'],
            'enfermeiro' => ['enfermeiro', '11144477735'],
            'técnico' => ['tecnico', '39053344705'],
        ];
    }

    #[DataProvider('tiposDeProfissional')]
    public function test_ct018_administracao_cadastra_cada_tipo_de_profissional(string $tipo, string $cpf): void
    {
        $r = $this->como($this->admin())->postJson('/api/v1/profissionais', [
            'nome' => "Profissional {$tipo}", 'email' => "{$tipo}@vidaplus.test", 'password' => 'Senha@12345', 'cpf' => $cpf,
            'tipo' => $tipo, 'conselho_sigla' => 'CRM', 'conselho_numero' => '12345', 'conselho_uf' => 'RS',
            'unidade_id' => $this->unidade->uuid,
        ]);

        $r->assertCreated();
        $this->assertDatabaseHas('usuarios', ['email' => "{$tipo}@vidaplus.test"]);
        $this->assertStringNotContainsString($cpf, $r->getContent());          // CPF não volta em claro
    }

    // CT024 — profissional de outra unidade (a API nega; o escopo por unidade pode responder 403 ou 404)
    public function test_ct024_profissional_de_outra_unidade_nao_acessa_o_prontuario(): void
    {
        $pac = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        \App\Domains\Prontuarios\Models\Prontuario::factory()->create(['paciente_id' => $pac->id]);
        $outraUnidade = \App\Domains\Unidades\Models\Unidade::factory()->create();
        $forasteiro = $this->profissional($outraUnidade);

        $r = $this->como($forasteiro->usuario)->getJson("/api/v1/pacientes/{$pac->uuid}/prontuario");

        $this->assertContains($r->status(), [403, 404]);
        $this->assertStringNotContainsString('tipo_sanguineo', $r->getContent());
    }

    // CT032 — token expirado e revogado
    public function test_ct032_token_expirado_responde_401(): void
    {
        Usuario::factory()->secretario()->create(['unidade_id' => $this->unidade->id, 'email' => 'exp@vidaplus.test']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'exp@vidaplus.test', 'password' => 'Senha@12345'])->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();

        $this->travel((int) config('sanctum.expiration') + 1)->minutes();
        $this->app['auth']->forgetGuards();                 // o guard guarda o usuário da chamada anterior

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    // CT036 — a documentação OpenAPI é gerada e cobre os módulos; fora de dev/staging a UI é negada
    public function test_ct036_openapi_e_gerado_com_os_endpoints_principais(): void
    {
        $arquivo = storage_path('app/openapi-teste.json');
        Artisan::call('scramble:export', ['--path' => $arquivo]);

        $spec = json_decode((string) file_get_contents($arquivo), true, flags: JSON_THROW_ON_ERROR);
        @unlink($arquivo);

        $caminhos = implode(' ', array_keys($spec['paths']));
        foreach (['/v1/auth/login', '/v1/pacientes', '/v1/agendamentos', '/v1/prontuarios', '/v1/telemedicina', '/v1/titular'] as $trecho) {
            $this->assertStringContainsString($trecho, $caminhos, "OpenAPI sem {$trecho}");
        }
        $this->assertArrayHasKey('securitySchemes', $spec['components']);
    }

    public function test_ct036_ui_da_documentacao_nao_abre_em_producao(): void
    {
        // Simula o ambiente de produção (o contêiner de desenvolvimento tem APP_ENV=local e o Scramble libera a UI em local)
        $this->app->detectEnvironment(fn () => 'production');

        $this->get('/docs/api')->assertForbidden();          // Gate viewApiDocs: só local/staging
    }

    // CT042 — cabeçalhos de segurança e CORS
    public function test_ct042_cabecalhos_de_seguranca_em_toda_resposta_da_api(): void
    {
        $r = $this->getJson('/api/v1/auth/unidades');

        $r->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
    }

    public function test_ct042_cors_so_aceita_a_origem_do_front(): void
    {
        config(['cors.allowed_origins' => ['https://app.vidaplus.test']]);

        $permitida = $this->call('OPTIONS', '/api/v1/auth/login', [], [], [], [
            'HTTP_ORIGIN' => 'https://app.vidaplus.test', 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type,authorization',
        ]);
        $negada = $this->call('OPTIONS', '/api/v1/auth/login', [], [], [], [
            'HTTP_ORIGIN' => 'https://malicioso.test', 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertSame('https://app.vidaplus.test', $permitida->headers->get('Access-Control-Allow-Origin'));
        // Com UMA origem configurada o pacote de CORS devolve sempre essa origem (não ecoa a do pedido);
        // o navegador compara e bloqueia. Portanto o que importa: a origem maliciosa NUNCA é autorizada.
        $this->assertNotSame('https://malicioso.test', $negada->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $negada->headers->get('Access-Control-Allow-Origin'));
    }

    // CT043 — injeção de SQL e mass assignment
    public function test_ct043_sql_injection_em_filtros_nao_vaza_nem_quebra(): void
    {
        $sec = $this->secretario();
        Paciente::factory()->count(2)->create(['unidade_id' => $this->unidade->id]);

        foreach (["' OR '1'='1", "x'; DROP TABLE pacientes; --", '1 UNION SELECT cpf_hash FROM pacientes'] as $payload) {
            $r = $this->como($sec)->getJson('/api/v1/pacientes?nome='.urlencode($payload));
            $this->assertContains($r->status(), [200, 422]);
            if ($r->status() === 200) {
                $this->assertCount(0, $r->json('data'));          // o texto é dado, não SQL
            }
        }
        $this->assertSame(2, Paciente::query()->count());          // a tabela continua lá
    }

    public function test_ct043_mass_assignment_ignora_campos_nao_permitidos(): void
    {
        $r = $this->como($this->secretario())->postJson('/api/v1/pacientes', [
            'nome' => 'Maria Teste', 'cpf' => '529.982.247-25', 'data_nascimento' => '1985-03-20', 'sexo' => 'feminino',
            'unidade_id' => $this->unidade->uuid, 'consentimento' => true,
            'id' => 999999, 'uuid' => '00000000-0000-4000-8000-00000000dead', 'cpf_hash' => 'forjado', 'deleted_at' => now()->toDateTimeString(),
        ]);

        $r->assertCreated();
        $paciente = Paciente::query()->where('nome', 'Maria Teste')->firstOrFail();
        $this->assertNotSame(999999, $paciente->id);
        $this->assertNotSame('00000000-0000-4000-8000-00000000dead', $paciente->uuid);
        $this->assertNotSame('forjado', $paciente->cpf_hash);
        $this->assertNull($paciente->deleted_at);
    }

    // CT045 — XLSX e PDF íntegros (a fila é "sync" nos testes: o job roda na hora)
    /** @return array<string, array{string, string}> */
    public static function formatosDeExportacao(): array
    {
        return ['xlsx' => ['xlsx', 'PK'], 'pdf' => ['pdf', '%PDF']];
    }

    #[DataProvider('formatosDeExportacao')]
    public function test_ct045_exportacao_gera_arquivo_integro(string $formato, string $assinatura): void
    {
        $dono = $this->admin();

        $id = $this->como($dono)->postJson('/api/v1/relatorios/exportacoes', ['relatorio' => 'consultas', 'formato' => $formato])
            ->assertStatus(202)->json('id');

        // Pronto, o endpoint devolve o ARQUIVO (download), não JSON de estado.
        $download = $this->como($dono)->get("/api/v1/relatorios/exportacoes/{$id}")->assertOk();
        $this->assertStringContainsString('attachment', (string) $download->headers->get('Content-Disposition'));
        $this->assertStringContainsString("relatorio-{$id}.{$formato}", (string) $download->headers->get('Content-Disposition'));
        Storage::disk('s3')->assertExists("exportacoes/{$id}.{$formato}");
        $this->assertStringStartsWith($assinatura, Storage::disk('s3')->get("exportacoes/{$id}.{$formato}"));
    }
}

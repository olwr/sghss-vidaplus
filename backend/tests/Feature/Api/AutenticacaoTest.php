<?php

namespace Tests\Feature\Api;

use App\Domains\Seguranca\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ComApi;
use Tests\TestCase;

class AutenticacaoTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    public function test_login_devolve_token_e_perfil(): void
    {
        $u = Usuario::factory()->secretario()->create(['unidade_id' => $this->unidade->id, 'email' => 'ana@vidaplus.test']);

        $r = $this->postJson('/api/v1/auth/login', ['email' => 'ana@vidaplus.test', 'password' => 'Senha@12345']);

        $r->assertOk()->assertJsonPath('tipo', 'Bearer')->assertJsonPath('usuario.id', $u->uuid)
            ->assertJsonPath('usuario.perfis.0', 'secretario');
        $this->assertNotEmpty($r->json('token'));
        $this->assertArrayNotHasKey('password', $r->json('usuario'));
    }

    public function test_senha_errada_e_usuario_inexistente_recebem_a_mesma_resposta(): void
    {
        Usuario::factory()->create(['email' => 'ana@vidaplus.test']);

        $errada = $this->postJson('/api/v1/auth/login', ['email' => 'ana@vidaplus.test', 'password' => 'errada-errada']);
        $inexistente = $this->postJson('/api/v1/auth/login', ['email' => 'nao@existe.test', 'password' => 'errada-errada']);

        $errada->assertStatus(401);
        $inexistente->assertStatus(401);
        $this->assertSame($errada->json('detail'), $inexistente->json('detail'));   // não revela se o e-mail existe
    }

    public function test_usuario_inativo_nao_autentica(): void
    {
        Usuario::factory()->inativo()->create(['email' => 'off@vidaplus.test']);

        $this->postJson('/api/v1/auth/login', ['email' => 'off@vidaplus.test', 'password' => 'Senha@12345'])->assertStatus(401);
    }

    public function test_sexta_tentativa_de_login_e_limitada(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'x@vidaplus.test', 'password' => 'errada-errada'])->assertStatus(401);
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'x@vidaplus.test', 'password' => 'errada-errada'])->assertStatus(429);
    }

    public function test_rota_protegida_sem_token_responde_401_em_problem_json(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401)->assertHeader('Content-Type', 'application/problem+json');
    }

    public function test_logout_revoga_o_token(): void
    {
        Usuario::factory()->create(['email' => 'ana@vidaplus.test']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'ana@vidaplus.test', 'password' => 'Senha@12345'])->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->app['auth']->forgetGuards();   // o guard guarda o usuário em memória entre requisições do mesmo teste
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_autocadastro_do_paciente_cria_usuario_paciente_e_consentimento(): void
    {
        $r = $this->postJson('/api/v1/auth/registro', [
            'nome' => 'Maria Souza', 'email' => 'maria@exemplo.test', 'password' => 'SenhaForte123', 'password_confirmation' => 'SenhaForte123',
            'cpf' => '529.982.247-25', 'data_nascimento' => '1990-05-10', 'sexo' => 'feminino',
            'unidade_id' => $this->unidade->uuid, 'consentimento' => true,
        ]);

        $r->assertCreated()->assertHeader('Location')->assertJsonPath('data.perfis.0', 'paciente');
        $this->assertDatabaseHas('consentimentos', ['finalidade' => 'atendimento_saude']);
        $this->assertDatabaseMissing('pacientes', ['cpf_criptografado' => '52998224725']);   // nunca em claro
    }

    public function test_cpf_duplicado_no_autocadastro_responde_409(): void
    {
        $corpo = fn (string $email) => [
            'nome' => 'Maria Souza', 'email' => $email, 'password' => 'SenhaForte123', 'password_confirmation' => 'SenhaForte123',
            'cpf' => '52998224725', 'data_nascimento' => '1990-05-10', 'sexo' => 'feminino',
            'unidade_id' => $this->unidade->uuid, 'consentimento' => true,
        ];

        $this->postJson('/api/v1/auth/registro', $corpo('a@exemplo.test'))->assertCreated();
        $this->postJson('/api/v1/auth/registro', $corpo('b@exemplo.test'))->assertStatus(409);
    }

    public function test_autocadastro_exige_consentimento_e_senha_forte(): void
    {
        $this->postJson('/api/v1/auth/registro', [
            'nome' => 'M', 'email' => 'm@exemplo.test', 'password' => '123', 'password_confirmation' => '123',
            'cpf' => '52998224725', 'data_nascimento' => '1990-05-10', 'sexo' => 'feminino', 'unidade_id' => $this->unidade->uuid,
        ])->assertStatus(422)->assertJsonValidationErrors(['password', 'consentimento']);
    }
}

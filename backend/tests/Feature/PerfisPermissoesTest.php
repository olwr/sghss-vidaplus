<?php

namespace Tests\Feature;

use App\Domains\Seguranca\Models\Usuario;
use Database\Seeders\PerfisPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfisPermissoesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PerfisPermissoesSeeder::class);
    }

    public function test_secretario_nunca_acessa_conteudo_clinico(): void
    {
        $u = Usuario::factory()->secretario()->create();

        $this->assertTrue($u->hasPermissionTo('agendamentos.criar'));
        $this->assertFalse($u->hasPermissionTo('prontuarios.ver'));
        $this->assertFalse($u->hasPermissionTo('registros_clinicos.ver'));
        $this->assertFalse($u->hasPermissionTo('receitas.ver'));
    }

    public function test_administrador_nao_tem_permissoes_clinicas(): void
    {
        $u = Usuario::factory()->administrador()->create();

        $this->assertTrue($u->hasPermissionTo('usuarios.gerenciar'));
        $this->assertTrue($u->hasPermissionTo('auditoria.ver'));
        $this->assertFalse($u->hasPermissionTo('prontuarios.ver'));
        $this->assertFalse($u->hasPermissionTo('exames.registrar_resultado'));
    }

    public function test_profissional_de_saude_acessa_prontuario_mas_nao_administra(): void
    {
        $u = Usuario::factory()->profissionalSaude()->create();

        $this->assertTrue($u->hasPermissionTo('prontuarios.ver'));
        $this->assertTrue($u->hasPermissionTo('receitas.emitir'));
        $this->assertFalse($u->hasPermissionTo('usuarios.gerenciar'));
        $this->assertFalse($u->hasPermissionTo('relatorios.ver'));
    }

    public function test_paciente_so_tem_permissoes_de_autoatendimento(): void
    {
        $u = Usuario::factory()->comPerfilPaciente()->create();

        $this->assertTrue($u->hasPermissionTo('agendamentos.criar'));
        $this->assertTrue($u->hasPermissionTo('telemedicina.entrar'));
        $this->assertFalse($u->hasPermissionTo('pacientes.criar'));
        $this->assertFalse($u->hasPermissionTo('prontuarios.editar'));
    }

    public function test_seeder_e_idempotente(): void
    {
        $this->seed(PerfisPermissoesSeeder::class);
        $this->seed(PerfisPermissoesSeeder::class);

        $this->assertSame(4, \Spatie\Permission\Models\Role::count());
    }
}

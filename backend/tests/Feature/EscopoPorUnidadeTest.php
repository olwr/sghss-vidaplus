<?php

namespace Tests\Feature;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Unidades\Models\Unidade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscopoPorUnidadeTest extends TestCase
{
    use RefreshDatabase;

    private Unidade $a;

    private Unidade $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->a = Unidade::factory()->create();
        $this->b = Unidade::factory()->create();
        Paciente::factory()->count(2)->create(['unidade_id' => $this->a->id]);
        Paciente::factory()->create(['unidade_id' => $this->b->id]);
    }

    public function test_usuario_de_unidade_so_enxerga_a_propria_unidade(): void
    {
        $this->actingAs(Usuario::factory()->secretario()->create(['unidade_id' => $this->a->id]));

        $this->assertSame(2, Paciente::count());
        $this->assertSame(0, Paciente::where('unidade_id', $this->b->id)->count());
    }

    public function test_usuario_de_rede_enxerga_todas_as_unidades(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $this->assertSame(3, Paciente::count());
    }

    public function test_sem_usuario_autenticado_nao_filtra_jobs_e_seeders(): void
    {
        $this->assertSame(3, Paciente::count());
    }

    public function test_perfil_paciente_nao_e_filtrado_pelo_escopo(): void
    {
        $this->actingAs(Usuario::factory()->comPerfilPaciente()->create(['unidade_id' => $this->a->id]));

        $this->assertSame(3, Paciente::count());   // quem restringe é a Policy
    }
}

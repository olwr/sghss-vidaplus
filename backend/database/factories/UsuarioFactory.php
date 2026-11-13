<?php

namespace Database\Factories;

use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Unidades\Models\Unidade;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/** @extends Factory<Usuario> */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    private static ?string $senhaComHash = null;

    public function definition(): array
    {
        return [
            'unidade_id' => Unidade::factory(),
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'telefone' => fake()->numerify('11#########'),
            // um único hash por processo: Argon2id é caro e a suíte cria muitos usuários
            'password' => self::$senhaComHash ??= Hash::make('Senha@12345'),
            'ativo' => true,
            'email_verificado_em' => now(),
        ];
    }

    public function semUnidade(): static
    {
        return $this->state(['unidade_id' => null]);   // acesso global à rede
    }

    public function inativo(): static
    {
        return $this->state(['ativo' => false]);
    }

    public function comPapel(string $papel): static
    {
        return $this->afterCreating(function (Usuario $usuario) use ($papel) {
            Role::findOrCreate($papel, 'web');
            $usuario->assignRole($papel);
        });
    }

    public function administrador(): static
    {
        return $this->semUnidade()->comPapel('administrador');
    }

    public function secretario(): static
    {
        return $this->comPapel('secretario');
    }

    public function profissionalSaude(): static
    {
        return $this->comPapel('profissional_saude');
    }

    public function comPerfilPaciente(): static
    {
        return $this->comPapel('paciente');
    }
}

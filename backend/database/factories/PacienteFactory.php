<?php

namespace Database\Factories;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Enums\Sexo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Paciente> */
class PacienteFactory extends Factory
{
    protected $model = Paciente::class;

    public function definition(): array
    {
        return [
            'unidade_id' => Unidade::factory(),
            'usuario_id' => null,                                   // cadastrado pela recepção
            'nome' => fake()->name(),
            'cpf' => fake()->unique()->cpf(false),                  // o mutator cifra e gera o blind index
            'data_nascimento' => fake()->dateTimeBetween('-90 years', '-1 year')->format('Y-m-d'),
            'sexo' => fake()->randomElement(Sexo::cases()),
            'telefone' => fake()->numerify('11#########'),
            'email' => fake()->safeEmail(),
            'cep' => fake()->numerify('########'),
            'logradouro' => fake()->streetName(),
            'numero' => (string) fake()->buildingNumber(),
            'bairro' => fake()->citySuffix(),
            'cidade' => fake()->city(),
            'uf' => fake()->stateAbbr(),
            'preferencias_acessibilidade' => null,
        ];
    }

    /** Paciente com conta de login (perfil `paciente`) na mesma unidade. */
    public function comAcesso(): static
    {
        return $this->state(fn (array $a) => [
            'usuario_id' => Usuario::factory()->comPerfilPaciente()->create(['unidade_id' => $a['unidade_id']])->id,
        ]);
    }

    public function comAcessibilidade(): static
    {
        return $this->state(['preferencias_acessibilidade' => ['fonte_ampliada' => true, 'alto_contraste' => true]]);
    }
}

<?php

namespace Database\Factories;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Prontuarios\Models\Prontuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Prontuario> */
class ProntuarioFactory extends Factory
{
    protected $model = Prontuario::class;

    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'tipo_sanguineo' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'alergias_criptografado' => fake()->randomElement(['Nenhuma conhecida', 'Dipirona', 'Penicilina']),
            'observacoes_gerais_criptografado' => fake()->sentence(),
        ];
    }
}

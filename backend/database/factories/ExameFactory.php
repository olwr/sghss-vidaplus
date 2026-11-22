<?php

namespace Database\Factories;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Prontuarios\Models\Exame;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Exame> */
class ExameFactory extends Factory
{
    protected $model = Exame::class;

    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'solicitante_id' => Profissional::factory(),
            'unidade_id' => fn (array $a) => Profissional::withoutGlobalScopes()->findOrFail($a['solicitante_id'])->unidade_id,
            'nome' => fake()->randomElement(['Hemograma completo', 'Glicemia em jejum', 'Raio-X de tórax']),
            'instrucoes' => 'Jejum de 8 horas',
            'valor' => fake()->randomFloat(2, 30, 300),
        ];
    }
}

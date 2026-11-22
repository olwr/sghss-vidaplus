<?php

namespace Database\Factories;

use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Prontuarios\Models\Exame;
use App\Domains\Prontuarios\Models\ResultadoExame;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ResultadoExame> */
class ResultadoExameFactory extends Factory
{
    protected $model = ResultadoExame::class;

    public function definition(): array
    {
        return [
            'exame_id' => Exame::factory(),
            'registrado_por_id' => Profissional::factory(),
            'resumo_criptografado' => fake()->sentence(),
            'disponibilizado_em' => null,
        ];
    }

    public function disponivel(): static
    {
        return $this->state(['disponibilizado_em' => now()->subMinute()]);
    }
}

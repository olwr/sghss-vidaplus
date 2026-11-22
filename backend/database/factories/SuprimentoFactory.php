<?php

namespace Database\Factories;

use App\Domains\Internacoes\Models\Suprimento;
use App\Domains\Unidades\Models\Unidade;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Suprimento> */
class SuprimentoFactory extends Factory
{
    protected $model = Suprimento::class;

    public function definition(): array
    {
        return [
            'unidade_id' => Unidade::factory(),
            'nome' => fake()->randomElement(['Luva de procedimento', 'Seringa 10 ml', 'Gaze estéril', 'Soro fisiológico 500 ml', 'Máscara cirúrgica']).' '.fake()->unique()->numerify('#####'),
            'codigo' => fake()->unique()->bothify('SUP-####'),
            'unidade_medida' => fake()->randomElement(['un', 'cx', 'ml']),
            'quantidade_atual' => fake()->numberBetween(0, 500),
            'quantidade_minima' => 50,
        ];
    }

    public function abaixoDoMinimo(): static
    {
        return $this->state(['quantidade_atual' => 5, 'quantidade_minima' => 50]);
    }
}

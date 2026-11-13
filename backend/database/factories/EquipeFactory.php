<?php

namespace Database\Factories;

use App\Domains\Profissionais\Models\Equipe;
use App\Domains\Unidades\Models\Unidade;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Equipe> */
class EquipeFactory extends Factory
{
    protected $model = Equipe::class;

    public function definition(): array
    {
        return [
            'unidade_id' => Unidade::factory(),
            'nome' => 'Equipe ' . fake()->unique()->words(2, true),
            'descricao' => fake()->sentence(),
            'ativa' => true,
        ];
    }
}

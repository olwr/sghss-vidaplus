<?php

namespace Database\Factories;

use App\Domains\Internacoes\Models\Leito;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Enums\TipoLeito;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Leito> */
class LeitoFactory extends Factory
{
    protected $model = Leito::class;

    public function definition(): array
    {
        return [
            'unidade_id' => Unidade::factory(),
            'codigo' => fake()->unique()->bothify('L-####'),
            'setor' => fake()->randomElement(['Ala A', 'Ala B', 'UTI Adulto']),
            'tipo' => fake()->randomElement(TipoLeito::cases()),
            'valor_diaria' => fake()->randomFloat(2, 300, 2500),
        ];
    }
}

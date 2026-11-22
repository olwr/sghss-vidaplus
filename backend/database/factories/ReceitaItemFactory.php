<?php

namespace Database\Factories;

use App\Domains\Prontuarios\Models\Receita;
use App\Domains\Prontuarios\Models\ReceitaItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReceitaItem> */
class ReceitaItemFactory extends Factory
{
    protected $model = ReceitaItem::class;

    public function definition(): array
    {
        return [
            'receita_id' => Receita::factory(),
            'medicamento' => fake()->randomElement(['Dipirona 500 mg', 'Amoxicilina 500 mg', 'Ibuprofeno 400 mg']),
            'dosagem' => '1 comprimido',
            'posologia' => 'A cada 8 horas',
            'duracao' => '7 dias',
            'quantidade' => 1,
        ];
    }
}

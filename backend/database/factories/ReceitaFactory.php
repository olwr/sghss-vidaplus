<?php

namespace Database\Factories;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Prontuarios\Models\Receita;
use App\Domains\Prontuarios\Models\ReceitaItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Receita> */
class ReceitaFactory extends Factory
{
    protected $model = Receita::class;

    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'profissional_id' => Profissional::factory(),
            'unidade_id' => fn (array $a) => Profissional::withoutGlobalScopes()->findOrFail($a['profissional_id'])->unidade_id,
            'emitida_em' => now(),
        ];
    }

    public function comItens(int $quantidade = 2): static
    {
        return $this->afterCreating(function (Receita $receita) use ($quantidade) {
            ReceitaItem::factory()->count($quantidade)->create(['receita_id' => $receita->id]);
        });
    }
}

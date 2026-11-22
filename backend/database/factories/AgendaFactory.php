<?php

namespace Database\Factories;

use App\Domains\Agendamentos\Models\Agenda;
use App\Domains\Profissionais\Models\Profissional;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Agenda> */
class AgendaFactory extends Factory
{
    protected $model = Agenda::class;

    public function definition(): array
    {
        return [
            'profissional_id' => Profissional::factory(),
            'unidade_id' => fn (array $a) => Profissional::withoutGlobalScopes()->findOrFail($a['profissional_id'])->unidade_id,
            'dia_semana' => fake()->numberBetween(1, 5),
            'hora_inicio' => '08:00',
            'hora_fim' => '12:00',
            'duracao_atendimento_min' => 30,
            'vigencia_inicio' => now()->toDateString(),
            'vigencia_fim' => null,
            'ativa' => true,
        ];
    }
}

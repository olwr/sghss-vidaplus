<?php

namespace Database\Factories;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Telemedicina\Models\SalaTelemedicina;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SalaTelemedicina> */
class SalaTelemedicinaFactory extends Factory
{
    protected $model = SalaTelemedicina::class;

    public function definition(): array
    {
        return [
            'agendamento_id' => Agendamento::factory()->telemedicina(),
            'nome_sala' => 'sghss-'.Str::lower(Str::random(16)),
            'valida_a_partir_de' => now()->addMinutes(-15),
            'valida_ate' => now()->addMinutes(45),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Telemedicina\Models\LogSessaoTelemedicina;
use App\Domains\Telemedicina\Models\SalaTelemedicina;
use App\Shared\Enums\EventoSessaoTelemedicina;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LogSessaoTelemedicina> */
class LogSessaoTelemedicinaFactory extends Factory
{
    protected $model = LogSessaoTelemedicina::class;

    public function definition(): array
    {
        return [
            'sala_id' => SalaTelemedicina::factory(),
            'usuario_id' => Usuario::factory(),
            'evento' => EventoSessaoTelemedicina::Entrou,
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'detalhes' => null,
            'ocorrido_em' => now(),
        ];
    }
}

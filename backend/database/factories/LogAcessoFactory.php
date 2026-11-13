<?php

namespace Database\Factories;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Seguranca\Models\LogAcesso;
use App\Domains\Seguranca\Models\Usuario;
use App\Shared\Enums\AcaoAcesso;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LogAcesso> */
class LogAcessoFactory extends Factory
{
    protected $model = LogAcesso::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'unidade_id' => null,
            'paciente_id' => Paciente::factory(),
            'acao' => AcaoAcesso::Leitura,
            'recurso_tipo' => 'prontuario',
            'recurso_id' => fake()->numberBetween(1, 1000),
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'rota' => 'api/v1/prontuarios',
        ];
    }
}

<?php

namespace Database\Factories;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Seguranca\Models\Consentimento;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Consentimento> */
class ConsentimentoFactory extends Factory
{
    protected $model = Consentimento::class;

    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'finalidade' => 'Tratamento de dados de saúde para atendimento',
            'versao_termo' => '1.0',
            'concedido_em' => now(),
            'ip_origem' => fake()->ipv4(),
        ];
    }

    public function revogado(): static
    {
        return $this->state(['revogado_em' => now()]);
    }
}

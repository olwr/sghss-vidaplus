<?php

namespace Database\Factories;

use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Prontuarios\Models\Prontuario;
use App\Domains\Prontuarios\Models\RegistroClinico;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RegistroClinico> */
class RegistroClinicoFactory extends Factory
{
    protected $model = RegistroClinico::class;

    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'profissional_id' => Profissional::factory(),
            'unidade_id' => fn (array $a) => Profissional::withoutGlobalScopes()->findOrFail($a['profissional_id'])->unidade_id,
            'agendamento_id' => null,
            'queixa_principal_criptografado' => fake()->sentence(),
            'diagnostico_criptografado' => fake()->sentence(),
            'conduta_criptografado' => fake()->sentence(),
            'observacoes_criptografado' => fake()->sentence(),
            'registrado_em' => now(),
        ];
    }
}

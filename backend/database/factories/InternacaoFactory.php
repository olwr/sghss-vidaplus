<?php

namespace Database\Factories;

use App\Domains\Internacoes\Models\Internacao;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Internacao> */
class InternacaoFactory extends Factory
{
    protected $model = Internacao::class;

    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'unidade_id' => fn (array $a) => Paciente::withoutGlobalScopes()->findOrFail($a['paciente_id'])->unidade_id,
            'solicitante_id' => Profissional::factory(),
            'motivo_criptografado' => fake()->sentence(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Pacientes\Models\PacienteEquipe;
use App\Domains\Profissionais\Models\Equipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PacienteEquipe> */
class PacienteEquipeFactory extends Factory
{
    protected $model = PacienteEquipe::class;

    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'equipe_id' => Equipe::factory(),
            'inicio_em' => now()->subMonth()->toDateString(),
            'fim_em' => null,
        ];
    }
}

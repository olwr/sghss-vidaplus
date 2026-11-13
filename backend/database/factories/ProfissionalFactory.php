<?php

namespace Database\Factories;

use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Enums\TipoProfissional;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Profissional> */
class ProfissionalFactory extends Factory
{
    protected $model = Profissional::class;

    public function definition(): array
    {
        return [
            'unidade_id' => Unidade::factory(),
            // o usuário nasce na mesma unidade do profissional
            'usuario_id' => fn (array $a) => Usuario::factory()->profissionalSaude()->create(['unidade_id' => $a['unidade_id']])->id,
            'tipo' => TipoProfissional::Medico,
            'especialidade' => fake()->randomElement(['Clínica Geral', 'Cardiologia', 'Pediatria', 'Ortopedia']),
            'conselho_sigla' => 'CRM',
            'conselho_numero' => (string) fake()->unique()->numberBetween(10000, 999999),
            'conselho_uf' => fake()->stateAbbr(),
            'cpf' => fake()->unique()->cpf(false),
            'ativo' => true,
        ];
    }

    public function enfermeiro(): static
    {
        return $this->state(['tipo' => TipoProfissional::Enfermeiro, 'conselho_sigla' => 'COREN', 'especialidade' => null]);
    }
}

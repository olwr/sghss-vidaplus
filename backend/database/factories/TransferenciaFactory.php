<?php

namespace Database\Factories;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Transferencias\Models\Transferencia;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Enums\TipoTransferencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transferencia> */
class TransferenciaFactory extends Factory
{
    protected $model = Transferencia::class;

    public function definition(): array
    {
        return [
            'tipo' => TipoTransferencia::Paciente,
            'paciente_id' => Paciente::factory(),
            'profissional_id' => null,
            'unidade_origem_id' => fn (array $a) => Paciente::withoutGlobalScopes()->findOrFail($a['paciente_id'])->unidade_id,
            'unidade_destino_id' => Unidade::factory(),
            'solicitante_id' => Usuario::factory()->administrador(),
            'motivo' => fake()->sentence(4),
        ];
    }
}

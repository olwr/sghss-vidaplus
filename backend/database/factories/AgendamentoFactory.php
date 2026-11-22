<?php

namespace Database\Factories;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Seguranca\Models\Usuario;
use App\Shared\Enums\ModalidadeAgendamento;
use App\Shared\Enums\StatusAgendamento;
use App\Shared\Enums\TipoAgendamento;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Agendamento> */
class AgendamentoFactory extends Factory
{
    protected $model = Agendamento::class;

    public function definition(): array
    {
        $inicio = now()->addDays(fake()->numberBetween(1, 60))
            ->setTime(fake()->numberBetween(8, 17), fake()->randomElement([0, 30]));

        return [
            'tipo' => TipoAgendamento::Consulta,
            'modalidade' => ModalidadeAgendamento::Presencial,
            'status' => StatusAgendamento::Agendado,
            'paciente_id' => Paciente::factory(),
            'profissional_id' => Profissional::factory(),
            // A ordem importa: a uniadde deriva do profissional já resolvido
            'unidade_id' => fn (array $a) => Profissional::withoutGlobalScopes()->findOrFail($a['profissional_id'])->unidade_id,
            'inicio_em' => $inicio,
            'fim_em' => (clone $inicio)->addMinutes(30),
            'motivo' => fake()->sentence(4),
            'valor' => fake()->randomFloat(2, 80, 400),
            'criado_por_id' => Usuario::factory()->secretario(),
        ];
    }

    public function em(CarbonInterface $inicio, int $minutos = 30): static
    {
        return $this->state([
            'inicio_em' => $inicio,
            'fim_em' => (clone $inicio)->addMinutes($minutos)
        ]);
    }

    public function status(StatusAgendamento $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function telemedicina(): static
    {
        return $this->state(['modalidade' => ModalidadeAgendamento::Telemedicina]);
    }
}

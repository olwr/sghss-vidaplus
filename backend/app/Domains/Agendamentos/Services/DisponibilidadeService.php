<?php

namespace App\Domains\Agendamentos\Services;

use App\Domains\Agendamentos\Models\Agenda;
use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Profissionais\Models\Profissional;
use App\Shared\Exceptions\RegraDeNegocioException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Horários = grade semanal (agendas) - agendamentos que ocupam o horário.
 * As horas da agenda são interpretadas no fuso `sghss.fuso_agenda` (o banco guarda tudo em UTC).
 */
class DisponibilidadeService
{
    /** @return Collection<int, array{inicio: string, fim: string, unidade_id: string}> */
    public function slots(Profissional $profissional, string $data, ?string $unidadeUuid = null): Collection
    {
        $fuso = config('sghss.fuso_agenda');
        $dia = CarbonImmutable::createFromFormat('Y-m-d', $data, $fuso)->startOfDay();

        $agendas = $this->agendasDoDia($profissional, $dia, $unidadeUuid)->load('unidade');
        $ocupados = Agendamento::withoutGlobalScopes()->ocupandoHorario()
            ->where('profissional_id', $profissional->id)
            ->where('inicio_em', '<', $dia->addDay())->where('fim_em', '>', $dia)
            ->get(['inicio_em', 'fim_em']);

        $slots = collect();
        foreach ($agendas as $agenda) {
            $cursor = $this->combinar($dia, $agenda->hora_inicio);
            $limite = $this->combinar($dia, $agenda->hora_fim);
            $passo = $agenda->duracao_atendimento_min;

            while ($cursor->addMinutes($passo) <= $limite) {
                $fim = $cursor->addMinutes($passo);
                $livre = $cursor->isFuture()
                    && $ocupados->every(fn ($o) => ! ($cursor < $o->fim_em && $fim > $o->inicio_em));
                if ($livre) {
                    $slots->push([
                        'inicio' => $cursor->utc()->toIso8601String(),
                        'fim' => $fim->utc()->toIso8601String(),
                        'unidade_id' => $agenda->unidade->uuid,
                    ]);
                }
                $cursor = $fim;
            }
        }

        return $slots->sortBy('inicio')->values();
    }

    /** Garante que [inicio, fim) cabe numa agenda vigente do profissional naquela unidade; devolve a agenda. */
    public function validarDentroDaAgenda(Profissional $profissional, int $unidadeId, CarbonImmutable $inicio, CarbonImmutable $fim): Agenda
    {
        $fuso = config('sghss.fuso_agenda');
        $ini = $inicio->setTimezone($fuso);
        $fi = $fim->setTimezone($fuso);

        if (! $ini->isSameDay($fi)) {
            throw new RegraDeNegocioException('O atendimento deve começar e terminar no mesmo dia.');
        }

        $agenda = $this->agendasDoDia($profissional, $ini->startOfDay(), null)
            ->first(fn (Agenda $a) => $a->unidade_id === $unidadeId
                && $this->combinar($ini->startOfDay(), $a->hora_inicio) <= $ini
                && $this->combinar($ini->startOfDay(), $a->hora_fim) >= $fi);

        if (! $agenda) {
            throw new RegraDeNegocioException('O horário está fora da agenda do profissional nesta unidade.');
        }

        return $agenda;
    }

    /** @return EloquentCollection<int, Agenda> */
    private function agendasDoDia(Profissional $profissional, CarbonImmutable $dia, ?string $unidadeUuid): EloquentCollection
    {
        return Agenda::withoutGlobalScopes()
            ->where('profissional_id', $profissional->id)
            ->where('ativa', true)
            ->where('dia_semana', $dia->dayOfWeek)              // 0 = domingo (igual ao DBML)
            ->whereDate('vigencia_inicio', '<=', $dia->toDateString())
            ->where(fn ($q) => $q->whereNull('vigencia_fim')->orWhereDate('vigencia_fim', '>=', $dia->toDateString()))
            ->when($unidadeUuid, fn ($q) => $q->whereHas('unidade', fn ($u) => $u->where('uuid', $unidadeUuid)))
            ->orderBy('hora_inicio')->get();
    }

    private function combinar(CarbonImmutable $dia, string $hora): CarbonImmutable
    {
        [$h, $m] = array_map('intval', explode(':', $hora));

        return $dia->setTime($h, $m);
    }
}

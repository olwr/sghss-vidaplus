<?php

namespace App\Domains\Agendamentos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Agendamentos\Models\Agenda */
class AgendaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'profissional_id' => $this->whenLoaded('profissional', fn () => $this->profissional->uuid),
            'unidade_id' => $this->whenLoaded('unidade', fn () => $this->unidade->uuid),
            'dia_semana' => $this->dia_semana,
            'hora_inicio' => substr((string) $this->hora_inicio, 0, 5),
            'hora_fim' => substr((string) $this->hora_fim, 0, 5),
            'duracao_atendimento_min' => $this->duracao_atendimento_min,
            'vigencia_inicio' => $this->vigencia_inicio?->toDateString(),
            'vigencia_fim' => $this->vigencia_fim?->toDateString(),
            'ativa' => $this->ativa,
        ];
    }
}

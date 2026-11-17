<?php

namespace App\Domains\Agendamentos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Agendamentos\Models\Agendamento */
class AgendamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'tipo' => $this->tipo,
            'modalidade' => $this->modalidade,
            'status' => $this->status,
            'inicio_em' => $this->inicio_em?->toIso8601String(),
            'fim_em' => $this->fim_em?->toIso8601String(),
            'motivo' => $this->motivo,
            'valor' => $this->when(! $request->user()?->hasRole('paciente'), $this->valor),
            'paciente_id' => $this->whenLoaded('paciente', fn () => $this->paciente->uuid),
            // 8.1: nomes para a tela (evita N chamadas extras do front); data_get() porque as relações são tipadas como Model (Larastan)
            'paciente_nome' => $this->whenLoaded('paciente', fn () => data_get($this->paciente, 'nome')),
            'profissional_nome' => $this->whenLoaded('profissional', fn () => data_get($this->profissional, 'usuario.nome')),
            'unidade_nome' => $this->whenLoaded('unidade', fn () => data_get($this->unidade, 'nome')),
            'profissional_id' => $this->whenLoaded('profissional', fn () => $this->profissional?->uuid),
            'unidade_id' => $this->whenLoaded('unidade', fn () => $this->unidade->uuid),
            'remarcado_de_id' => $this->whenLoaded('remarcadoDe', fn () => $this->remarcadoDe?->uuid),
            'sala_telemedicina_id' => $this->whenLoaded('sala', fn () => $this->sala?->uuid),
            'cancelado_em' => $this->cancelado_em?->toIso8601String(),
            'motivo_cancelamento' => $this->motivo_cancelamento,
        ];
    }
}

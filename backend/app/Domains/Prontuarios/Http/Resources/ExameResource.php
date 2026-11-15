<?php

namespace App\Domains\Prontuarios\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Prontuarios\Models\Exame */
class ExameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'nome' => $this->nome,
            'instrucoes' => $this->instrucoes,
            'status' => $this->status,
            'valor' => $this->when(! $request->user()?->hasRole('paciente'), $this->valorEfetivo()),
            'solicitado_em' => $this->solicitado_em?->toIso8601String(),
            'realizado_em' => $this->realizado_em?->toIso8601String(),
            'paciente_id' => $this->whenLoaded('paciente', fn () => $this->paciente->uuid),
            'resultado_disponivel' => $this->whenLoaded('resultados', fn () => $this->resultados->contains(fn ($r) => $r->estaDisponivel())),
        ];
    }
}

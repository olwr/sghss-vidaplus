<?php

namespace App\Domains\Transferencias\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Transferencias\Models\Transferencia */
class TransferenciaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'tipo' => $this->tipo,
            'status' => $this->status,
            'paciente_id' => $this->whenLoaded('paciente', fn () => $this->paciente?->uuid),
            'profissional_id' => $this->whenLoaded('profissional', fn () => $this->profissional?->uuid),
            'unidade_origem_id' => $this->whenLoaded('origem', fn () => $this->origem->uuid),
            'unidade_destino_id' => $this->whenLoaded('destino', fn () => $this->destino->uuid),
            'motivo' => $this->motivo,
            'efetivada_em' => $this->efetivada_em?->toIso8601String(),
        ];
    }
}

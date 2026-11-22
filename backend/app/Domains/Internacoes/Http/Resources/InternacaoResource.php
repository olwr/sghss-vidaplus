<?php

namespace App\Domains\Internacoes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Internacoes\Models\Internacao */
class InternacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'status' => $this->status,
            'paciente_id' => $this->whenLoaded('paciente', fn () => $this->paciente->uuid),
            'leito_id' => $this->whenLoaded('leito', fn () => $this->leito?->uuid),
            'unidade_id' => $this->whenLoaded('unidade', fn () => $this->unidade->uuid),
            'solicitada_em' => $this->solicitada_em?->toIso8601String(),
            'admitida_em' => $this->admitida_em?->toIso8601String(),
            'alta_em' => $this->alta_em?->toIso8601String(),
        ];
    }
}

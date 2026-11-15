<?php

namespace App\Domains\Prontuarios\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Prontuarios\Models\RegistroClinico */
class RegistroClinicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'registrado_em' => $this->registrado_em?->toIso8601String(),
            'profissional_id' => $this->whenLoaded('profissional', fn () => $this->profissional->uuid),
            'queixa_principal' => $this->queixa_principal_criptografado,
            'diagnostico' => $this->diagnostico_criptografado,
            'conduta' => $this->conduta_criptografado,
            'observacoes' => $this->observacoes_criptografado,
        ];
    }
}

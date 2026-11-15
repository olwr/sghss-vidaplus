<?php

namespace App\Domains\Prontuarios\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Prontuarios\Models\Prontuario */
class ProntuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'paciente_id' => $this->whenLoaded('paciente', fn () => $this->paciente->uuid),
            'tipo_sanguineo' => $this->tipo_sanguineo,
            'alergias' => $this->alergias_criptografado,
            'observacoes_gerais' => $this->observacoes_gerais_criptografado,
            'registros' => RegistroClinicoResource::collection($this->whenLoaded('registros')),
        ];
    }
}

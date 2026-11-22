<?php

namespace App\Domains\Internacoes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Internacoes\Models\Leito */
class LeitoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'codigo' => $this->codigo,
            'setor' => $this->setor,
            'tipo' => $this->tipo,
            'status' => $this->status,
            'valor_diaria' => $this->valor_diaria,
            'unidade_id' => $this->whenLoaded('unidade', fn () => $this->unidade->uuid),
        ];
    }
}

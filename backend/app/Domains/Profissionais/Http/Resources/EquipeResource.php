<?php

namespace App\Domains\Profissionais\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Profissionais\Models\Equipe */
class EquipeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'ativa' => $this->ativa,
            'profissionais' => $this->whenLoaded('profissionais', fn () => $this->profissionais->map(fn ($p) => [
                'id' => $p->uuid,
                'nome' => $p->usuario?->nome,
                'tipo' => $p->tipo,
                'funcao' => $p->pivot->funcao,
            ])),
        ];
    }
}

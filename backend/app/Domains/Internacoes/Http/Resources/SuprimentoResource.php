<?php

namespace App\Domains\Internacoes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Internacoes\Models\Suprimento */
class SuprimentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'nome' => $this->nome,
            'codigo' => $this->codigo,
            'unidade_medida' => $this->unidade_medida,
            'quantidade_atual' => $this->quantidade_atual,
            'quantidade_minima' => $this->quantidade_minima,
            'abaixo_do_minimo' => $this->abaixoDoMinimo(),
        ];
    }
}

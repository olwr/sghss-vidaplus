<?php

namespace App\Domains\Unidades\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Unidades\Models\Unidade */
class UnidadeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'tipo' => $this->tipo,
            'nome' => $this->nome,
            'cnpj' => $this->cnpj,
            'status' => $this->status,
            'telefone' => $this->telefone,
            'email' => $this->email,
            'endereco' => [
                'cep' => $this->cep, 'logradouro' => $this->logradouro, 'numero' => $this->numero,
                'complemento' => $this->complemento, 'bairro' => $this->bairro,
                'cidade' => $this->cidade, 'uf' => $this->uf,
            ],
        ];
    }
}

<?php

namespace App\Domains\Seguranca\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Seguranca\Models\Usuario */
class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,                      // UUID público, nunca o id sequencial
            'nome' => $this->nome,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'ativo' => $this->ativo,
            'unidade_id' => $this->unidade?->uuid,
            'perfis' => $this->getRoleNames()->values(),
            'permissoes' => $this->getAllPermissions()->pluck('name')->sort()->values(),
            'ultimo_login_em' => $this->ultimo_login_em?->toIso8601String()
        ];
    }
}

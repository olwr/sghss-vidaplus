<?php

namespace App\Domains\Profissionais\Http\Resources;

use App\Domains\Pacientes\Http\Resources\PacienteResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Profissionais\Models\Profissional */
class ProfissionalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'nome' => $this->whenLoaded('usuario', fn () => $this->usuario->nome),
            'email' => $this->whenLoaded('usuario', fn () => $this->usuario->email),
            'tipo' => $this->tipo,
            'especialidade' => $this->especialidade,
            'conselho' => $this->conselho_sigla.' '.$this->conselho_numero.'/'.$this->conselho_uf,
            'cpf' => PacienteResource::mascarar($this->cpf),
            'ativo' => $this->ativo,
            'unidade_id' => $this->whenLoaded('unidade', fn () => $this->unidade->uuid),
        ];
    }
}

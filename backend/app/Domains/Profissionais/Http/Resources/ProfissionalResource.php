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
        $ehPaciente = (bool) $request->user()?->hasRole('paciente');

        return [
            'id' => $this->uuid,
            'nome' => $this->whenLoaded('usuario', fn () => $this->usuario->nome),
            // 8.1: e-mail e CPF (mascarado) são dados de gestão; o paciente só vê o necessário para escolher o médico
            'email' => $this->when(! $ehPaciente, fn () => $this->whenLoaded('usuario', fn () => $this->usuario->email)),
            'tipo' => $this->tipo,
            'especialidade' => $this->especialidade,
            'conselho' => $this->conselho_sigla.' '.$this->conselho_numero.'/'.$this->conselho_uf,
            'cpf' => $this->when(! $ehPaciente, fn () => PacienteResource::mascarar($this->cpf)),
            'ativo' => $this->ativo,
            'unidade_id' => $this->whenLoaded('unidade', fn () => $this->unidade->uuid),
        ];
    }
}

<?php

namespace App\Domains\Pacientes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Pacientes\Models\Paciente */
class PacienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,                          // UUID público, nunca o id sequencial
            'nome' => $this->nome,
            'cpf' => self::mascarar($this->cpf),          // CT020: o CPF completo nunca sai na API
            'data_nascimento' => $this->data_nascimento?->toDateString(),
            'sexo' => $this->sexo,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'endereco' => [
                'cep' => $this->cep, 'logradouro' => $this->logradouro, 'numero' => $this->numero,
                'complemento' => $this->complemento, 'bairro' => $this->bairro,
                'cidade' => $this->cidade, 'uf' => $this->uf,
            ],
            'preferencias_acessibilidade' => $this->preferencias_acessibilidade,
            'unidade_id' => $this->whenLoaded('unidade', fn () => $this->unidade->uuid),
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }

    /** 12345678909 -> ***.***.***-09 */
    public static function mascarar(?string $cpf): ?string
    {
        $d = preg_replace('/\D/', '', (string) $cpf);

        return strlen($d) === 11 ? '***.***.***-'.substr($d, 9, 2) : null;
    }
}

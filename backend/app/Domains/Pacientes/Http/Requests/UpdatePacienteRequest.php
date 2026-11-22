<?php

namespace App\Domains\Pacientes\Http\Requests;

use App\Shared\Enums\Sexo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/** O CPF não é alterável por aqui (identidade); correção de CPF seria um processo administrativo à parte. */
class UpdatePacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'string', 'max:150'],
            'data_nascimento' => ['sometimes', 'date', 'before:today'],
            'sexo' => ['sometimes', new Enum(Sexo::class)],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'cep' => ['nullable', 'string', 'size:8'],
            'logradouro' => ['nullable', 'string', 'max:150'],
            'numero' => ['nullable', 'string', 'max:10'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'uf' => ['nullable', 'string', 'size:2'],
            'preferencias_acessibilidade' => ['nullable', 'array'],
        ];
    }
}

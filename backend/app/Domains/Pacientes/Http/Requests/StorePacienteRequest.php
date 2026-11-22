<?php

namespace App\Domains\Pacientes\Http\Requests;

use App\Shared\Enums\Sexo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StorePacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // a autorização real está na PacientePolicy (Gate::authorize no controller)
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['cpf' => preg_replace('/\D/', '', (string) $this->input('cpf'))]);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150'],
            'cpf' => ['required', 'string', 'cpf'],
            'data_nascimento' => ['required', 'date', 'before:today'],
            'sexo' => ['required', new Enum(Sexo::class)],
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
            'unidade_id' => ['required', 'uuid', Rule::exists('unidades', 'uuid')],
            'consentimento' => ['required', 'accepted'],
        ];
    }
}

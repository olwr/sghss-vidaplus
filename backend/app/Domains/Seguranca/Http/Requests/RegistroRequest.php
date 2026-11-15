<?php

namespace App\Domains\Seguranca\Http\Requests;

use App\Shared\Enums\Sexo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class RegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autocadastro público do paciente
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('usuarios', 'email')],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'cpf' => ['required', 'string', 'cpf'],
            'data_nascimento' => ['required', 'date', 'before:today'],
            'sexo' => ['required', new Enum(Sexo::class)],
            'telefone' => ['nullable', 'string', 'max:20'],
            'unidade_id' => ['required', 'uuid', Rule::exists('unidades', 'uuid')],
            'consentimento' => ['required', 'accepted']
        ];
    }
}

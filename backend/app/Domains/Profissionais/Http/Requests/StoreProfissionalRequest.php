<?php

namespace App\Domains\Profissionais\Http\Requests;

use App\Shared\Enums\TipoProfissional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreProfissionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // middleware can:profissionais.gerenciar na rota
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
            'password' => ['required', Password::min(10)->letters()->numbers()],
            'cpf' => ['required', 'string', 'cpf'],
            'tipo' => ['required', new Enum(TipoProfissional::class)],
            'especialidade' => ['nullable', 'string', 'max:100'],
            'conselho_sigla' => ['required', 'string', 'max:10'],
            'conselho_numero' => ['required', 'string', 'max:20'],
            'conselho_uf' => ['required', 'string', 'size:2'],
            'unidade_id' => ['required', 'uuid', Rule::exists('unidades', 'uuid')],
        ];
    }
}

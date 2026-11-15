<?php

namespace App\Domains\Administracao\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsuarioAdminRequest extends FormRequest
{
    public const PERFIS = ['administrador', 'secretario', 'profissional_saude', 'paciente'];

    public function authorize(): bool
    {
        return true; // middleware can:usuarios.gerenciar
    }

    public function rules(): array
    {
        return [
            'ativo' => ['sometimes', 'boolean'],
            'perfil' => ['sometimes', Rule::in(self::PERFIS)],
            // null = acesso à rede inteira (só faz sentido para administrador)
            'unidade_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('unidades', 'uuid')],
        ];
    }
}

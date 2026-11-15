<?php

namespace App\Domains\Prontuarios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistroClinicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'queixa_principal' => ['nullable', 'string', 'max:5000'],
            'diagnostico' => ['required_without_all:queixa_principal,conduta,observacoes', 'nullable', 'string', 'max:5000'],
            'conduta' => ['nullable', 'string', 'max:5000'],
            'observacoes' => ['nullable', 'string', 'max:10000'],
            'agendamento_id' => ['nullable', 'uuid', Rule::exists('agendamentos', 'uuid')],
        ];
    }
}

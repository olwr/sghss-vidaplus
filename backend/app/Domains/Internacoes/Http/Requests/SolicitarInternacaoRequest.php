<?php

namespace App\Domains\Internacoes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitarInternacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paciente_id' => ['required', 'uuid', Rule::exists('pacientes', 'uuid')],
            'motivo' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

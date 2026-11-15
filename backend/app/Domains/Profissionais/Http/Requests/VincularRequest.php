<?php

namespace App\Domains\Profissionais\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Vincular profissional ou paciente a uma equipe (um dos dois campos). */
class VincularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profissional_id' => ['required_without:paciente_id', 'uuid', Rule::exists('profissionais', 'uuid')],
            'paciente_id' => ['required_without:profissional_id', 'uuid', Rule::exists('pacientes', 'uuid')],
            'funcao' => ['nullable', 'string', 'max:60'],
            'inicio_em' => ['nullable', 'date'],
        ];
    }
}

<?php

namespace App\Domains\Prontuarios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** POST (cria, exige paciente_id) e PATCH (atualiza os dados gerais). */
class ProntuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ProntuarioPolicy no controller
    }

    public function rules(): array
    {
        return [
            'paciente_id' => [$this->isMethod('POST') ? 'required' : 'prohibited', 'uuid', Rule::exists('pacientes', 'uuid')],
            'tipo_sanguineo' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'alergias' => ['nullable', 'string', 'max:5000'],
            'observacoes_gerais' => ['nullable', 'string', 'max:10000'],
        ];
    }
}

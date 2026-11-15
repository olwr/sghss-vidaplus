<?php

namespace App\Domains\Prontuarios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReceitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paciente_id' => ['required', 'uuid', Rule::exists('pacientes', 'uuid')],
            'agendamento_id' => ['nullable', 'uuid', Rule::exists('agendamentos', 'uuid')],
            'itens' => ['required', 'array', 'min:1', 'max:30'],
            'itens.*.medicamento' => ['required', 'string', 'max:200'],
            'itens.*.dosagem' => ['nullable', 'string', 'max:100'],
            'itens.*.posologia' => ['nullable', 'string', 'max:255'],
            'itens.*.duracao' => ['nullable', 'string', 'max:100'],
            'itens.*.quantidade' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}

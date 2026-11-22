<?php

namespace App\Domains\Prontuarios\Http\Requests;

use App\Shared\Enums\StatusExame;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** POST solicita o exame; PATCH muda o andamento (agendado, realizado ou cancelado). */
class ExameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return [
                'paciente_id' => ['required', 'uuid', Rule::exists('pacientes', 'uuid')],
                'nome' => ['required', 'string', 'max:150'],
                'instrucoes' => ['nullable', 'string', 'max:255'],
                'valor' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            ];
        }

        return [
            'status' => ['required', Rule::in([
                StatusExame::Agendado->value, StatusExame::Realizado->value, StatusExame::Cancelado->value,
            ])],
        ];
    }
}

<?php

namespace App\Domains\Agendamentos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Corpo opcional das ações: cancelar (motivo) e remarcar (novo horário). */
class AgendamentoAcaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['nullable', 'string', 'max:255'],
            'inicio_em' => [$this->routeIs('*remarcar') ? 'required' : 'prohibited', 'date', 'after:now'],
        ];
    }
}

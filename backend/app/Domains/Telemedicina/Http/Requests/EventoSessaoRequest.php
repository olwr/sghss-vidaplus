<?php

namespace App\Domains\Telemedicina\Http\Requests;

use App\Shared\Enums\EventoSessaoTelemedicina;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventoSessaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // o serviço confere se o usuário participa da sala
    }

    public function rules(): array
    {
        return [
            // token_emitido é gerado só pelo servidor
            'evento' => ['required', Rule::in([
                EventoSessaoTelemedicina::VerificacaoDispositivo->value,
                EventoSessaoTelemedicina::Entrou->value,
                EventoSessaoTelemedicina::Saiu->value,
            ])],
            'detalhes' => ['nullable', 'array'],
        ];
    }
}

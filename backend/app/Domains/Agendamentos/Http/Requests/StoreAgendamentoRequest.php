<?php

namespace App\Domains\Agendamentos\Http\Requests;

use App\Shared\Enums\ModalidadeAgendamento;
use App\Shared\Enums\TipoAgendamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // AgendamentoPolicy no controller
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', new Enum(TipoAgendamento::class)],
            'modalidade' => ['required', new Enum(ModalidadeAgendamento::class)],
            // para o perfil paciente o campo é ignorado (vale o próprio paciente)
            'paciente_id' => ['nullable', 'uuid', Rule::exists('pacientes', 'uuid')],
            'profissional_id' => ['required', 'uuid', Rule::exists('profissionais', 'uuid')],
            'unidade_id' => ['required', 'uuid', Rule::exists('unidades', 'uuid')],
            'inicio_em' => ['required', 'date', 'after:now'],
            'duracao_min' => ['nullable', 'integer', 'between:5,240'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'valor' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }
}

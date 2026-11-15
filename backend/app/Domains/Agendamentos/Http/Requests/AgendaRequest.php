<?php

namespace App\Domains\Agendamentos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // AgendaPolicy no controller
    }

    public function rules(): array
    {
        $obrigatorio = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'profissional_id' => [$obrigatorio, 'uuid', Rule::exists('profissionais', 'uuid')],
            'unidade_id' => [$obrigatorio, 'uuid', Rule::exists('unidades', 'uuid')],
            'dia_semana' => [$obrigatorio, 'integer', 'between:0,6'],           // 0 = domingo ... 6 = sábado
            'hora_inicio' => [$obrigatorio, 'date_format:H:i'],
            'hora_fim' => [$obrigatorio, 'date_format:H:i', 'after:hora_inicio'],
            'duracao_atendimento_min' => ['sometimes', 'integer', 'between:5,240'],
            'vigencia_inicio' => [$obrigatorio, 'date'],
            'vigencia_fim' => ['nullable', 'date', 'after_or_equal:vigencia_inicio'],
            'ativa' => ['sometimes', 'boolean'],
        ];
    }
}

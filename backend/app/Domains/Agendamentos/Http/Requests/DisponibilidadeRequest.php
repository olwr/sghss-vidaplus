<?php

namespace App\Domains\Agendamentos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DisponibilidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profissional' => ['required', 'uuid', Rule::exists('profissionais', 'uuid')],
            'unidade' => ['nullable', 'uuid', Rule::exists('unidades', 'uuid')],
            'data' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }
}

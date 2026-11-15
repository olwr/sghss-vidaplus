<?php

namespace App\Domains\Profissionais\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $obrigatorio = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'nome' => [$obrigatorio, 'string', 'max:150'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativa' => ['sometimes', 'boolean'],
            'unidade_id' => [$obrigatorio, 'uuid', Rule::exists('unidades', 'uuid')],
        ];
    }
}

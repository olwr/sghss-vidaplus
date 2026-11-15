<?php

namespace App\Domains\Internacoes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuprimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $post = $this->isMethod('POST');

        return [
            'unidade_id' => [$post ? 'required' : 'prohibited', 'uuid', Rule::exists('unidades', 'uuid')],
            'nome' => [$post ? 'required' : 'sometimes', 'string', 'max:150'],
            'codigo' => ['nullable', 'string', 'max:50'],
            'unidade_medida' => [$post ? 'required' : 'sometimes', 'string', 'max:20'],
            'quantidade_atual' => [$post ? 'required' : 'prohibited', 'integer', 'min:0'],   // depois, só via movimentação
            'quantidade_minima' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}

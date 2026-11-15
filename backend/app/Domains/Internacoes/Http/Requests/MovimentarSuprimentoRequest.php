<?php

namespace App\Domains\Internacoes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovimentarSuprimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // positivo = entrada, negativo = saída
            'quantidade' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}

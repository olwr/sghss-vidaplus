<?php

namespace App\Domains\Prontuarios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResultadoExameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // tipo e tamanho validados; o nome original nunca vira caminho (o arquivo recebe nome aleatório)
            'arquivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'resumo' => ['nullable', 'string', 'max:10000', 'required_without:arquivo'],
        ];
    }
}

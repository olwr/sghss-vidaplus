<?php

namespace App\Domains\Seguranca\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsentimentoRequest extends FormRequest
{
    public const FINALIDADES = ['atendimento_saude', 'comunicacao', 'pesquisa'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['finalidade' => ['required', Rule::in(self::FINALIDADES)]];
    }
}

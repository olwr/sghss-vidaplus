<?php

namespace App\Domains\Internacoes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdmitirInternacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leito_id' => ['required', 'uuid', Rule::exists('leitos', 'uuid')],
            'responsavel_id' => ['nullable', 'uuid', Rule::exists('profissionais', 'uuid')],
        ];
    }
}

<?php

namespace App\Domains\Unidades\Http\Requests;

use App\Shared\Enums\StatusUnidade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AlterarStatusUnidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(StatusUnidade::class)],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}

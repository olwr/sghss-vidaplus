<?php

namespace App\Domains\Relatorios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RelatorioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // middleware can:relatorios.ver / relatorios.exportar
    }

    public function rules(): array
    {
        return [
            'de' => ['nullable', 'date_format:Y-m-d'],
            'ate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:de'],
            'formato' => [$this->isMethod('POST') ? 'required' : 'prohibited', 'in:xlsx,pdf'],
            'relatorio' => [$this->isMethod('POST') ? 'required' : 'prohibited', 'string'],
        ];
    }
}

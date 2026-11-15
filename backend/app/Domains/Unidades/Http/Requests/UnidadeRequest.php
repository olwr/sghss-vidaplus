<?php

namespace App\Domains\Unidades\Http\Requests;

use App\Shared\Enums\StatusUnidade;
use App\Shared\Enums\TipoUnidade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/** Serve para POST (todos obrigatórios) e PATCH (todos opcionais). */
class UnidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // a permissão é verificada na rota (middleware can:unidades.gerenciar)
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('cnpj')) {
            $this->merge(['cnpj' => preg_replace('/\D/', '', (string) $this->input('cnpj'))]);
        }
    }

    public function rules(): array
    {
        $obrigatorio = $this->isMethod('POST') ? 'required' : 'sometimes';
        $atual = $this->route('unidade');

        return [
            'tipo' => [$obrigatorio, new Enum(TipoUnidade::class)],
            'nome' => [$obrigatorio, 'string', 'max:150'],
            'cnpj' => ['nullable', 'string', 'cnpj', Rule::unique('unidades', 'cnpj')->ignore($atual?->id)],
            'status' => ['sometimes', new Enum(StatusUnidade::class)],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'cep' => ['nullable', 'string', 'size:8'],
            'logradouro' => ['nullable', 'string', 'max:150'],
            'numero' => ['nullable', 'string', 'max:10'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'uf' => ['nullable', 'string', 'size:2'],
        ];
    }
}

<?php

namespace App\Domains\Internacoes\Http\Requests;

use App\Shared\Enums\StatusLeito;
use App\Shared\Enums\TipoLeito;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class LeitoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // middleware can:leitos.gerenciar
    }

    public function rules(): array
    {
        $post = $this->isMethod('POST');

        return [
            'unidade_id' => [$post ? 'required' : 'prohibited', 'uuid', Rule::exists('unidades', 'uuid')],
            'codigo' => [$post ? 'required' : 'prohibited', 'string', 'max:20'],
            'setor' => ['nullable', 'string', 'max:100'],
            'tipo' => [$post ? 'required' : 'sometimes', new Enum(TipoLeito::class)],
            'valor_diaria' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            // o status "ocupado" só é definido pela admissão; aqui apenas manutenção/disponível
            'status' => ['sometimes', Rule::in([StatusLeito::Disponivel->value, StatusLeito::Manutencao->value])],
        ];
    }
}

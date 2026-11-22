<?php

namespace App\Domains\Transferencias\Http\Requests;

use App\Shared\Enums\TipoTransferencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class TransferenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // middleware can:transferencias.registrar; transferir profissional exige `gerenciar` (no serviço)
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', new Enum(TipoTransferencia::class)],
            // exatamente um dos dois, conforme o tipo (o CHECK do banco garante o mesmo)
            'paciente_id' => ['required_if:tipo,paciente', 'prohibited_if:tipo,profissional', 'nullable', 'uuid', Rule::exists('pacientes', 'uuid')],
            'profissional_id' => ['required_if:tipo,profissional', 'prohibited_if:tipo,paciente', 'nullable', 'uuid', Rule::exists('profissionais', 'uuid')],
            'unidade_destino_id' => ['required', 'uuid', Rule::exists('unidades', 'uuid')],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}

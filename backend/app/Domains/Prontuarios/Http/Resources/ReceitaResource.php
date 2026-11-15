<?php

namespace App\Domains\Prontuarios\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domains\Prontuarios\Models\Receita */
class ReceitaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'status' => $this->status,
            'emitida_em' => $this->emitida_em?->toIso8601String(),
            'cancelada_em' => $this->cancelada_em?->toIso8601String(),
            'paciente_id' => $this->whenLoaded('paciente', fn () => $this->paciente->uuid),
            'profissional_id' => $this->whenLoaded('profissional', fn () => $this->profissional->uuid),
            'itens' => $this->whenLoaded('itens', fn () => $this->itens->map(fn ($i) => [
                'medicamento' => $i->medicamento, 'dosagem' => $i->dosagem, 'posologia' => $i->posologia,
                'duracao' => $i->duracao, 'quantidade' => $i->quantidade,
            ])),
            'pdf' => url("/api/v1/receitas/{$this->uuid}/pdf"),
        ];
    }
}

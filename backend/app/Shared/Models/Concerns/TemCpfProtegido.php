<?php

namespace App\Shared\Models\Concerns;

use App\Shared\Support\BlindIndex;
use Illuminate\Database\Eloquent\Builder;

/**
 * CPF nunca fica em exposto no banco: `cpf_criptografado` (cast `encrypted`) + `cpf_hash` (blind_index).
 * Uso: $modelo->cpf => '123.456.78-09' (ou create(['cpf' => ...])); leitura: $modelo->cpf (descriptografado)
 */
trait TemCpfProtegido
{
    protected function initializeTemCpfProtegido(): void
    {
        $this->mergeCasts(['cpf_criptografado' => 'encrypted']);
        $this->mergeFillable(['cpf']);
        $this->makeHidden(['cpf_criptografado', 'cpf_hash']);
    }

    // Mutator "clássico": ao atribuir pelas propriedades reais, o cast `encrypted` é aplicado
    // com certeza (um Attribute::set devolvendo array não passa pelos casts: risco de gravar "em claro"/exposto).
    public function setCpfAttribute(string $valor): void
    {
        $digitos = preg_replace('/\D/', '', $valor);

        $this->cpf_criptografado = $digitos;
        $this->cpf_hash = BlindIndex::cpf($digitos);
    }

    public function getCpfAttribute(): ?string
    {
        return $this->cpf_criptografado;
    }

    public function scopePorCpf(Builder $consulta, string $cpf): Builder
    {
        return $consulta->where('cpf_hash', BlindIndex::cpf($cpf));
    }
}

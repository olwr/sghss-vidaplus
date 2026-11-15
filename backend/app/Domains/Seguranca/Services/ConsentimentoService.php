<?php

namespace App\Domains\Seguranca\Services;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Seguranca\Models\Consentimento;

class ConsentimentoService
{
    public const TERMO_VIGENTE = '1.0';

    public function conceder(Paciente $paciente, string $finalidade, ?string $ip = null): Consentimento
    {
        // idempotente: se já existe consentimento vigente para a finalidade, devolve-o
        $vigente = $paciente->consentimentos()
            ->where('finalidade', $finalidade)->whereNull('revogado_em')->first();
        if ($vigente) {
            return $vigente;
        }

        return $paciente->consentimentos()->create([
            'finalidade' => $finalidade,
            'versao_termo' => self::TERMO_VIGENTE,
            'concedido_em' => now(),
            'ip_origem' => $ip,
        ]);
    }

    public function revogar(Paciente $paciente, string $finalidade): void
    {
        $paciente->consentimentos()
            ->where('finalidade', $finalidade)->whereNull('revogado_em')
            ->update(['revogado_em' => now()]);
    }
}

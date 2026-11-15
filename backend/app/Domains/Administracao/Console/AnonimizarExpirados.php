<?php

namespace App\Domains\Administracao\Console;

use App\Domains\Administracao\Services\AnonimizacaoService;
use App\Domains\Pacientes\Models\Paciente;
use App\Shared\Exceptions\RegraDeNegocioException;
use Illuminate\Console\Command;

class AnonimizarExpirados extends Command
{
    protected $signature = 'lgpd:anonimizar-expirados';

    protected $description = 'Anonimiza pacientes excluídos (soft delete) cuja guarda legal do prontuário já terminou.';

    public function handle(AnonimizacaoService $service): int
    {
        $feitos = 0;

        Paciente::onlyTrashed()->whereNull('anonimizado_em')
            ->where('deleted_at', '<=', now()->subYears((int) config('sghss.retencao_prontuario_anos')))
            ->chunkById(100, function ($lote) use ($service, &$feitos) {
                foreach ($lote as $paciente) {
                    try {
                        $service->anonimizar($paciente);
                        $feitos++;
                    } catch (RegraDeNegocioException $e) {
                        $this->warn("Paciente {$paciente->uuid}: {$e->getMessage()}");
                    }
                }
            });

        $this->info("Pacientes anonimizados: {$feitos}");

        return self::SUCCESS;
    }
}

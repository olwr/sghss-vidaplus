<?php

namespace App\Domains\Relatorios\Jobs;

use App\Domains\Relatorios\Services\ExportacaoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GerarExportacao implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public readonly string $id,
        public readonly int $usuarioId,
        public readonly string $relatorio,
        public readonly string $formato,
        public readonly array $filtros,
    ) {
        $this->onQueue('relatorios');
    }

    public function handle(ExportacaoService $service): void
    {
        $service->executar($this->id, $this->usuarioId, $this->relatorio, $this->formato, $this->filtros);
    }

    public function failed(Throwable $e): void
    {
        app(ExportacaoService::class)->falhar($this->id);
    }
}

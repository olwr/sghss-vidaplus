<?php

namespace App\Domains\Relatorios\Services;

use App\Domains\Prontuarios\Services\ReceitaService;
use App\Domains\Relatorios\Jobs\GerarExportacao;
use App\Domains\Seguranca\Models\Usuario;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Exportação assíncrona (RF016): o estado fica no cache (Redis), o arquivo no bucket privado, sem tabela nova.
 * O job roda sem sessão HTTP: por isso reautentica como o solicitante para o UnidadeScope valer.
 */
class ExportacaoService
{
    private const TTL_HORAS = 24;

    public function __construct(private readonly RelatorioService $relatorios)
    {
    }

    public function solicitar(Usuario $usuario, string $relatorio, string $formato, array $filtros): string
    {
        $this->relatorios->gerar($relatorio, $filtros);       // valida tipo e período antes de enfileirar (falha rápida)

        $id = (string) Str::uuid();
        $this->gravar($id, ['status' => 'processando', 'usuario_id' => $usuario->id, 'formato' => $formato]);
        GerarExportacao::dispatch($id, $usuario->id, $relatorio, $formato, $filtros);

        return $id;
    }

    public function executar(string $id, int $usuarioId, string $relatorio, string $formato, array $filtros): void
    {
        Auth::setUser(Usuario::query()->findOrFail($usuarioId));

        $dados = $this->relatorios->gerar($relatorio, $filtros);
        $caminho = "exportacoes/{$id}.{$formato}";

        if ($formato === 'xlsx') {
            Excel::store(new RelatorioExport($dados), $caminho, ReceitaService::DISCO);
        } else {
            Storage::disk(ReceitaService::DISCO)->put($caminho, Pdf::loadView('pdf.relatorio', ['dados' => $dados])->setPaper('a4', 'landscape')->output());
        }

        $this->gravar($id, ['status' => 'pronto', 'usuario_id' => $usuarioId, 'formato' => $formato, 'caminho' => $caminho]);
    }

    public function falhar(string $id): void
    {
        $atual = Cache::get($this->chave($id), []);
        $this->gravar($id, ['status' => 'falhou'] + $atual);
    }

    /** @return array{status: string, usuario_id: int, formato: string, caminho?: string}|null */
    public function estado(string $id): ?array
    {
        return Cache::get($this->chave($id));
    }

    private function gravar(string $id, array $estado): void
    {
        Cache::put($this->chave($id), $estado, now()->addHours(self::TTL_HORAS));
    }

    private function chave(string $id): string
    {
        return 'exportacao:'.$id;
    }
}

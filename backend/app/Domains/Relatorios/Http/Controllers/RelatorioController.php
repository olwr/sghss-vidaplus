<?php

namespace App\Domains\Relatorios\Http\Controllers;

use App\Domains\Relatorios\Http\Requests\RelatorioRequest;
use App\Domains\Relatorios\Services\ExportacaoService;
use App\Domains\Relatorios\Services\RelatorioService;
use App\Domains\Seguranca\Models\LogAcesso;
use App\Http\Controllers\Controller;
use App\Shared\Enums\AcaoAcesso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatorioController extends Controller
{
    public function __construct(
        private readonly RelatorioService $relatorios,
        private readonly ExportacaoService $exportacoes,
    ) {
    }

    public function show(RelatorioRequest $request, string $tipo): JsonResponse
    {
        return response()->json($this->relatorios->gerar($tipo, $request->validated()));
    }

    /** 202 + id: o arquivo é gerado em fila (Horizon, fila `relatorios`). */
    public function exportar(RelatorioRequest $request): JsonResponse
    {
        $id = $this->exportacoes->solicitar($request->user(), $request->validated('relatorio'), $request->validated('formato'), $request->safe()->only(['de', 'ate']));

        return response()->json(['id' => $id, 'status' => 'processando', 'consulta' => url("/api/v1/relatorios/exportacoes/{$id}")], 202);
    }

    public function exportacao(Request $request, string $id): JsonResponse|StreamedResponse
    {
        $estado = $this->exportacoes->estado($id);
        // de outro usuário ou inexistente/expirado: mesma resposta (não revela que o id existe)
        abort_if($estado === null || $estado['usuario_id'] !== $request->user()->id, 404, 'Exportação não encontrada.');

        if ($estado['status'] !== 'pronto') {
            return response()->json(['id' => $id, 'status' => $estado['status']]);
        }

        LogAcesso::create([
            'usuario_id' => $request->user()->id, 'unidade_id' => $request->user()->unidade_id,
            'acao' => AcaoAcesso::Exportacao, 'recurso_tipo' => 'exportacao_relatorio',
            'ip' => $request->ip(), 'user_agent' => substr((string) $request->userAgent(), 0, 255), 'rota' => $request->path(),
        ]);

        return Storage::disk('s3')->download($estado['caminho'], 'relatorio-'.$id.'.'.$estado['formato'], ['Cache-Control' => 'no-store, private']);
    }
}

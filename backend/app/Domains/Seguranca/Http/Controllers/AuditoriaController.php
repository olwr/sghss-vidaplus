<?php

namespace App\Domains\Seguranca\Http\Controllers;

use App\Domains\Seguranca\Models\Consentimento;
use App\Domains\Seguranca\Models\LogAcesso;
use App\Http\Controllers\Controller;
use App\Shared\Enums\AcaoAcesso;
use App\Shared\Http\Consulta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/** Somente leitura. PUT/PATCH/DELETE são recusados na rota; o banco também bloqueia (gatilhos). */
class AuditoriaController extends Controller
{
    public function atividades(Request $request): JsonResponse
    {
        $consulta = Activity::query()->with('causer')
            ->when($request->query('log'), fn ($q, $v) => $q->where('log_name', $v))
            ->when($request->query('evento'), fn ($q, $v) => $q->where('event', $v))
            ->when($request->query('de'), fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($request->query('ate'), fn ($q, $v) => $q->where('created_at', '<=', $v));

        $pagina = Consulta::ordenar($consulta, $request, ['id', 'created_at'], 'id')->paginate(Consulta::porPagina($request));

        return response()->json([
            'data' => collect($pagina->items())->map(fn ($a) => [
                'id' => $a->id, 'log' => $a->log_name, 'evento' => $a->event, 'descricao' => $a->description,
                'entidade' => class_basename((string) $a->subject_type),
                'usuario' => $a->causer?->nome,
                'alteracoes' => $a->attribute_changes,     // o Auditavel já exclui cifrados/ocultos
                'ip' => $a->ip_address, 'quando' => $a->created_at?->toIso8601String(),
            ]),
            'meta' => ['total' => $pagina->total(), 'pagina' => $pagina->currentPage(), 'por_pagina' => $pagina->perPage()],
        ]);
    }

    public function acessos(Request $request): JsonResponse
    {
        $consulta = LogAcesso::query()->with(['usuario', 'paciente'])
            ->when($request->query('acao'), fn ($q, $v) => $q->where('acao', $v))
            ->when($request->query('paciente'), fn ($q, $v) => $q->whereHas('paciente', fn ($p) => $p->where('uuid', $v)))
            ->when($request->query('usuario'), fn ($q, $v) => $q->whereHas('usuario', fn ($u) => $u->where('uuid', $v)))
            ->when($request->query('de'), fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($request->query('ate'), fn ($q, $v) => $q->where('created_at', '<=', $v));

        $pagina = Consulta::ordenar($consulta, $request, ['id', 'created_at'], 'id')->paginate(Consulta::porPagina($request));

        return response()->json([
            'data' => collect($pagina->items())->map(fn ($l) => [
                'id' => $l->id, 'acao' => $l->acao, 'recurso' => $l->recurso_tipo,
                'usuario' => $l->usuario?->nome, 'paciente_id' => $l->paciente?->uuid,
                'ip' => $l->ip, 'quando' => $l->created_at?->toIso8601String(),
            ]),
            'meta' => ['total' => $pagina->total(), 'pagina' => $pagina->currentPage(), 'por_pagina' => $pagina->perPage()],
        ]);
    }

    public function conformidade(): JsonResponse
    {
        $desde = now()->subDays(30);

        return response()->json([
            'consentimentos_vigentes' => Consentimento::query()->whereNull('revogado_em')->count(),
            'consentimentos_revogados' => Consentimento::query()->whereNotNull('revogado_em')->count(),
            'acessos_a_prontuarios_30d' => LogAcesso::query()->where('acao', AcaoAcesso::Leitura->value)->where('recurso_tipo', 'prontuario')->where('created_at', '>=', $desde)->count(),
            'exportacoes_30d' => LogAcesso::query()->where('acao', AcaoAcesso::Exportacao->value)->where('created_at', '>=', $desde)->count(),
            'gerado_em' => now()->toIso8601String(),
        ]);
    }
}

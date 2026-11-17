<?php

namespace App\Domains\Seguranca\Http\Controllers;

use App\Domains\Pacientes\Http\Requests\UpdatePacienteRequest;
use App\Domains\Prontuarios\Http\Resources\ExameResource;
use App\Domains\Prontuarios\Http\Resources\ReceitaResource;
use App\Domains\Pacientes\Http\Resources\PacienteResource;
use App\Domains\Pacientes\Services\PacienteService;
use App\Domains\Seguranca\Http\Requests\ConsentimentoRequest;
use App\Domains\Seguranca\Services\ConsentimentoService;
use App\Domains\Seguranca\Services\TitularService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TitularController extends Controller
{
    public function __construct(
        private readonly TitularService $titular,
        private readonly ConsentimentoService $consentimentos,
    ) {
    }

    public function meusDados(Request $request): PacienteResource
    {
        return PacienteResource::make($this->titular->pacienteDe($request->user())->load('unidade'));
    }

    public function atualizar(UpdatePacienteRequest $request, PacienteService $pacientes): PacienteResource
    {
        return PacienteResource::make($pacientes->atualizar($this->titular->pacienteDe($request->user()), $request->validated()));
    }

    public function exportacao(Request $request): JsonResponse
    {
        return response()->json($this->titular->exportar($request->user()), 200, [
            'Content-Disposition' => 'attachment; filename="meus-dados.json"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /** 8.1: as receitas do PRÓPRIO titular (sem passar pelo prontuário; o PDF segue a Policy de receitas). */
    public function receitas(Request $request): AnonymousResourceCollection
    {
        $p = $this->titular->pacienteDe($request->user());

        return ReceitaResource::collection($p->receitas()->with('itens')->orderByDesc('emitida_em')->limit(100)->get());
    }

    /** 8.1: os exames do PRÓPRIO titular, com a flag `resultado_disponivel` (o arquivo segue a Policy de exames). */
    public function exames(Request $request): AnonymousResourceCollection
    {
        $p = $this->titular->pacienteDe($request->user());

        return ExameResource::collection($p->exames()->with('resultados')->orderByDesc('solicitado_em')->limit(100)->get());
    }

    public function consentimentos(Request $request): JsonResponse
    {
        $itens = $this->titular->pacienteDe($request->user())->consentimentos()->orderBy('concedido_em')->get()
            ->map(fn ($c) => [
                'finalidade' => $c->finalidade, 'versao_termo' => $c->versao_termo, 'vigente' => $c->estaVigente(),
                'concedido_em' => $c->concedido_em?->toIso8601String(), 'revogado_em' => $c->revogado_em?->toIso8601String(),
            ]);

        return response()->json(['data' => $itens]);
    }

    public function conceder(ConsentimentoRequest $request): JsonResponse
    {
        $c = $this->consentimentos->conceder($this->titular->pacienteDe($request->user()), $request->validated('finalidade'), $request->ip());

        return response()->json(['finalidade' => $c->finalidade, 'vigente' => $c->estaVigente()], 201)->header('Location', url('/api/v1/titular/consentimentos'));
    }

    public function revogar(Request $request, string $finalidade): JsonResponse
    {
        $this->titular->revogar($request->user(), $finalidade);

        return Resposta::semConteudo();
    }
}

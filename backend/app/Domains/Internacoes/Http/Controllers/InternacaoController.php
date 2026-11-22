<?php

namespace App\Domains\Internacoes\Http\Controllers;

use App\Domains\Internacoes\Http\Requests\AdmitirInternacaoRequest;
use App\Domains\Internacoes\Http\Requests\SolicitarInternacaoRequest;
use App\Domains\Internacoes\Http\Resources\InternacaoResource;
use App\Domains\Internacoes\Models\Internacao;
use App\Domains\Internacoes\Services\InternacaoService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Consulta;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InternacaoController extends Controller
{
    public function __construct(private readonly InternacaoService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $consulta = Internacao::query()->with(['paciente', 'leito', 'unidade'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s));

        return InternacaoResource::collection(
            Consulta::ordenar($consulta, $request, ['id', 'solicitada_em', 'status'], 'solicitada_em')->paginate(Consulta::porPagina($request))
        );
    }

    public function store(SolicitarInternacaoRequest $request): JsonResponse
    {
        $i = $this->service->solicitar($request->validated(), $request->user());

        return Resposta::criado(InternacaoResource::make($i), url("/api/v1/internacoes/{$i->uuid}"));
    }

    public function show(Internacao $internacao): InternacaoResource
    {
        return InternacaoResource::make($internacao->load(['paciente', 'leito', 'unidade']));
    }

    public function admitir(AdmitirInternacaoRequest $request, Internacao $internacao): InternacaoResource
    {
        return InternacaoResource::make($this->service->admitir($internacao, $request->validated()));
    }

    public function alta(Internacao $internacao): InternacaoResource
    {
        return InternacaoResource::make($this->service->darAlta($internacao));
    }

    public function cancelar(Internacao $internacao): InternacaoResource
    {
        return InternacaoResource::make($this->service->cancelar($internacao));
    }
}

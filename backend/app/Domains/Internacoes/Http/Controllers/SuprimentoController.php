<?php

namespace App\Domains\Internacoes\Http\Controllers;

use App\Domains\Internacoes\Http\Requests\MovimentarSuprimentoRequest;
use App\Domains\Internacoes\Http\Requests\SuprimentoRequest;
use App\Domains\Internacoes\Http\Resources\SuprimentoResource;
use App\Domains\Internacoes\Models\Suprimento;
use App\Domains\Internacoes\Services\EstoqueService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Consulta;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SuprimentoController extends Controller
{
    public function __construct(private readonly EstoqueService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $consulta = Suprimento::query()
            ->when($request->boolean('abaixo_do_minimo'), fn ($q) => $q->whereColumn('quantidade_atual', '<', 'quantidade_minima'));

        return SuprimentoResource::collection(
            Consulta::ordenar($consulta, $request, ['id', 'nome', 'quantidade_atual'], 'nome')->paginate(Consulta::porPagina($request))
        );
    }

    public function store(SuprimentoRequest $request): JsonResponse
    {
        $s = $this->service->criar($request->validated());

        return Resposta::criado(SuprimentoResource::make($s), url("/api/v1/suprimentos/{$s->uuid}"));
    }

    public function update(SuprimentoRequest $request, Suprimento $suprimento): SuprimentoResource
    {
        $suprimento->update($request->validated());

        return SuprimentoResource::make($suprimento->refresh());
    }

    public function movimentar(MovimentarSuprimentoRequest $request, Suprimento $suprimento): SuprimentoResource
    {
        return SuprimentoResource::make(
            $this->service->movimentar($suprimento, (int) $request->validated('quantidade'), $request->validated('motivo'))
        );
    }
}

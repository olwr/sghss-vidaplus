<?php

namespace App\Domains\Unidades\Http\Controllers;

use App\Domains\Unidades\Http\Requests\AlterarStatusUnidadeRequest;
use App\Domains\Unidades\Http\Requests\UnidadeRequest;
use App\Domains\Unidades\Http\Resources\UnidadeResource;
use App\Domains\Unidades\Models\Unidade;
use App\Domains\Unidades\Services\UnidadeService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\StatusUnidade;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnidadeController extends Controller
{
    public function __construct(private readonly UnidadeService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status');
        abort_if($status !== null && StatusUnidade::tryFrom((string) $status) === null, 422, 'Status inválido.');

        return UnidadeResource::collection($this->service->listar($status));
    }

    public function show(Unidade $unidade): UnidadeResource
    {
        return UnidadeResource::make($unidade);
    }

    public function store(UnidadeRequest $request): JsonResponse
    {
        $unidade = $this->service->criar($request->validated());

        return Resposta::criado(UnidadeResource::make($unidade), url("/api/v1/unidades/{$unidade->uuid}"));
    }

    public function update(UnidadeRequest $request, Unidade $unidade): UnidadeResource
    {
        return UnidadeResource::make($this->service->atualizar($unidade, $request->validated()));
    }

    public function status(AlterarStatusUnidadeRequest $request, Unidade $unidade): UnidadeResource
    {
        $novo = StatusUnidade::from($request->validated('status'));

        return UnidadeResource::make($this->service->alterarStatus($unidade, $novo, $request->validated('motivo')));
    }
}

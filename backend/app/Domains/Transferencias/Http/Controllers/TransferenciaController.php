<?php

namespace App\Domains\Transferencias\Http\Controllers;

use App\Domains\Transferencias\Http\Requests\TransferenciaRequest;
use App\Domains\Transferencias\Http\Resources\TransferenciaResource;
use App\Domains\Transferencias\Models\Transferencia;
use App\Domains\Transferencias\Services\TransferenciaService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Consulta;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransferenciaController extends Controller
{
    public function __construct(private readonly TransferenciaService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $consulta = Transferencia::query()->with(['paciente', 'profissional', 'origem', 'destino'])
            ->when($request->query('tipo'), fn ($q, $t) => $q->where('tipo', $t));

        return TransferenciaResource::collection(
            Consulta::ordenar($consulta, $request, ['id', 'efetivada_em', 'created_at'], 'created_at')->paginate(Consulta::porPagina($request))
        );
    }

    public function store(TransferenciaRequest $request): JsonResponse
    {
        $t = $this->service->registrar($request->validated(), $request->user());

        return Resposta::criado(TransferenciaResource::make($t), url("/api/v1/transferencias/{$t->uuid}"));
    }

    public function show(Transferencia $transferencia): TransferenciaResource
    {
        return TransferenciaResource::make($transferencia->load(['paciente', 'profissional', 'origem', 'destino']));
    }
}

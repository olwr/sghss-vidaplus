<?php

namespace App\Domains\Agendamentos\Http\Controllers;

use App\Domains\Agendamentos\Http\Requests\AgendamentoAcaoRequest;
use App\Domains\Agendamentos\Http\Requests\StoreAgendamentoRequest;
use App\Domains\Agendamentos\Http\Resources\AgendamentoResource;
use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Agendamentos\Services\AgendamentoService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AgendamentoController extends Controller
{
    public function __construct(private readonly AgendamentoService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Agendamento::class);

        return AgendamentoResource::collection($this->service->listar($request, $request->user()));
    }

    /** Idempotente: o header `Idempotency-Key` repetido devolve o mesmo agendamento (200) em vez de duplicar. */
    public function store(StoreAgendamentoRequest $request): JsonResponse
    {
        Gate::authorize('create', Agendamento::class);

        [$ag, $criado] = $this->service->agendar($request->validated(), $request->user(), $request->header('Idempotency-Key'));
        $resposta = AgendamentoResource::make($ag->loadMissing(['paciente', 'profissional', 'unidade', 'sala']));

        return $criado
            ? Resposta::criado($resposta, url("/api/v1/agendamentos/{$ag->uuid}"))
            : $resposta->response()->setStatusCode(200)->header('Idempotent-Replayed', 'true');
    }

    public function show(Agendamento $agendamento): AgendamentoResource
    {
        Gate::authorize('view', $agendamento);

        return AgendamentoResource::make($agendamento->load(['paciente', 'profissional', 'unidade', 'sala']));
    }

    public function confirmar(Agendamento $agendamento): AgendamentoResource
    {
        Gate::authorize('confirmar', $agendamento);

        return $this->resposta($this->service->confirmar($agendamento));
    }

    public function cancelar(AgendamentoAcaoRequest $request, Agendamento $agendamento): JsonResponse
    {
        Gate::authorize('cancelar', $agendamento);
        $this->service->cancelar($agendamento, $request->user(), $request->validated('motivo'));

        return Resposta::semConteudo();
    }

    public function remarcar(AgendamentoAcaoRequest $request, Agendamento $agendamento): JsonResponse
    {
        Gate::authorize('remarcar', $agendamento);
        $novo = $this->service->remarcar($agendamento, $request->validated('inicio_em'), $request->user());

        return Resposta::criado(AgendamentoResource::make($novo), url("/api/v1/agendamentos/{$novo->uuid}"));
    }

    public function iniciar(Agendamento $agendamento): AgendamentoResource
    {
        Gate::authorize('conduzir', $agendamento);

        return $this->resposta($this->service->iniciar($agendamento));
    }

    public function concluir(Agendamento $agendamento): AgendamentoResource
    {
        Gate::authorize('conduzir', $agendamento);

        return $this->resposta($this->service->concluir($agendamento));
    }

    public function faltou(Agendamento $agendamento): AgendamentoResource
    {
        Gate::authorize('remarcar', $agendamento);   // quem remarca (equipe) registra a falta

        return $this->resposta($this->service->marcarFalta($agendamento));
    }

    private function resposta(Agendamento $a): AgendamentoResource
    {
        return AgendamentoResource::make($a->loadMissing(['paciente', 'profissional', 'unidade']));
    }
}

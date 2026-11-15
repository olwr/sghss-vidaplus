<?php

namespace App\Domains\Agendamentos\Http\Controllers;

use App\Domains\Agendamentos\Http\Requests\AgendaRequest;
use App\Domains\Agendamentos\Http\Requests\DisponibilidadeRequest;
use App\Domains\Agendamentos\Http\Resources\AgendaResource;
use App\Domains\Agendamentos\Models\Agenda;
use App\Domains\Agendamentos\Services\DisponibilidadeService;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Unidades\Models\Unidade;
use App\Http\Controllers\Controller;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AgendaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Agenda::class);

        $agendas = Agenda::query()->with(['profissional', 'unidade'])
            ->when($request->query('profissional'), fn ($q, $uuid) => $q->whereHas('profissional', fn ($p) => $p->where('uuid', $uuid)))
            ->orderBy('dia_semana')->orderBy('hora_inicio')->get();

        return AgendaResource::collection($agendas);
    }

    public function store(AgendaRequest $request): JsonResponse
    {
        Gate::authorize('create', Agenda::class);
        // autoriza ANTES de gravar: o profissional só cria agenda para si
        $profissional = Profissional::query()->where('uuid', $request->validated('profissional_id'))->firstOrFail();
        Gate::authorize('criarPara', [Agenda::class, $profissional]);

        $agenda = $this->salvar(new Agenda(), $request->validated());

        return Resposta::criado(AgendaResource::make($agenda->load(['profissional', 'unidade'])), url("/api/v1/agendas/{$agenda->id}"));
    }

    public function update(AgendaRequest $request, Agenda $agenda): AgendaResource
    {
        Gate::authorize('update', $agenda);

        return AgendaResource::make($this->salvar($agenda, $request->validated())->load(['profissional', 'unidade']));
    }

    /** Horários livres do profissional em um dia (CT022). */
    public function disponibilidade(DisponibilidadeRequest $request, DisponibilidadeService $service): JsonResponse
    {
        $profissional = Profissional::query()->where('uuid', $request->validated('profissional'))->firstOrFail();

        return response()->json(['data' => $service->slots(
            $profissional,
            $request->validated('data'),
            $request->validated('unidade'),
        )]);
    }

    private function salvar(Agenda $agenda, array $dados): Agenda
    {
        if (isset($dados['profissional_id'])) {
            $dados['profissional_id'] = Profissional::query()->where('uuid', $dados['profissional_id'])->firstOrFail()->id;
        }
        if (isset($dados['unidade_id'])) {
            $dados['unidade_id'] = Unidade::query()->where('uuid', $dados['unidade_id'])->firstOrFail()->id;
        }
        $agenda->fill($dados)->save();

        return $agenda;
    }
}

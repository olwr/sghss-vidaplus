<?php

namespace App\Domains\Profissionais\Http\Controllers;

use App\Domains\Agendamentos\Http\Resources\AgendaResource;
use App\Domains\Profissionais\Http\Requests\StoreProfissionalRequest;
use App\Domains\Profissionais\Http\Resources\ProfissionalResource;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Profissionais\Services\ProfissionalService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Consulta;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfissionalController extends Controller
{
    public function __construct(private readonly ProfissionalService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $consulta = Profissional::query()->with('usuario', 'unidade')
            ->when($request->query('tipo'), fn ($q, $t) => $q->where('tipo', $t))
            ->when($request->query('unidade'), fn ($q, $u) => $q->whereHas('unidade', fn ($x) => $x->where('uuid', $u)))
            ->when($request->query('especialidade'), fn ($q, $e) => $q->where('especialidade', 'ilike', '%'.addcslashes((string) $e, '%_\\').'%'));

        return ProfissionalResource::collection(
            Consulta::ordenar($consulta, $request, ['id', 'especialidade', 'created_at'])->paginate(Consulta::porPagina($request))
        );
    }

    public function show(Profissional $profissional): ProfissionalResource
    {
        return ProfissionalResource::make($profissional->load('usuario', 'unidade'));
    }

    public function store(StoreProfissionalRequest $request): JsonResponse
    {
        $p = $this->service->cadastrar($request->validated());

        return Resposta::criado(ProfissionalResource::make($p), url("/api/v1/profissionais/{$p->uuid}"));
    }

    public function destroy(Profissional $profissional): JsonResponse
    {
        $this->service->desativar($profissional);

        return Resposta::semConteudo();
    }

    public function agenda(Profissional $profissional): AnonymousResourceCollection
    {
        return AgendaResource::collection($profissional->agendas()->where('ativa', true)->orderBy('dia_semana')->orderBy('hora_inicio')->get());
    }
}

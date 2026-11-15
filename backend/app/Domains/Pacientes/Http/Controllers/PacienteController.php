<?php

namespace App\Domains\Pacientes\Http\Controllers;

use App\Domains\Pacientes\Http\Requests\StorePacienteRequest;
use App\Domains\Pacientes\Http\Requests\UpdatePacienteRequest;
use App\Domains\Pacientes\Http\Resources\PacienteResource;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Pacientes\Services\PacienteService;
use App\Domains\Profissionais\Http\Resources\EquipeResource;
use App\Http\Controllers\Controller;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PacienteController extends Controller
{
    public function __construct(private readonly PacienteService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Paciente::class);

        return PacienteResource::collection($this->service->listar($request));
    }

    public function store(StorePacienteRequest $request): JsonResponse
    {
        Gate::authorize('create', Paciente::class);
        $paciente = $this->service->cadastrar($request->validated(), $request->user(), $request->ip());

        return Resposta::criado(PacienteResource::make($paciente), url("/api/v1/pacientes/{$paciente->uuid}"));
    }

    public function show(Paciente $paciente): PacienteResource
    {
        Gate::authorize('view', $paciente);

        return PacienteResource::make($paciente->load('unidade'));
    }

    public function update(UpdatePacienteRequest $request, Paciente $paciente): PacienteResource
    {
        Gate::authorize('update', $paciente);

        return PacienteResource::make($this->service->atualizar($paciente, $request->validated()));
    }

    public function destroy(Paciente $paciente): JsonResponse
    {
        Gate::authorize('delete', $paciente);
        $this->service->excluir($paciente);

        return Resposta::semConteudo();
    }

    public function equipe(Paciente $paciente): AnonymousResourceCollection
    {
        Gate::authorize('view', $paciente);

        return EquipeResource::collection($paciente->equipesAtivas()->with('profissionais.usuario')->get());
    }
}

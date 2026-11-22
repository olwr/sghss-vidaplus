<?php

namespace App\Domains\Profissionais\Http\Controllers;

use App\Domains\Profissionais\Http\Requests\EquipeRequest;
use App\Domains\Profissionais\Http\Requests\VincularRequest;
use App\Domains\Profissionais\Http\Resources\EquipeResource;
use App\Domains\Profissionais\Models\Equipe;
use App\Domains\Profissionais\Services\EquipeService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EquipeController extends Controller
{
    public function __construct(private readonly EquipeService $service)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return EquipeResource::collection(Equipe::query()->with('profissionais.usuario')->orderBy('nome')->get());
    }

    public function show(Equipe $equipe): EquipeResource
    {
        return EquipeResource::make($equipe->load('profissionais.usuario'));
    }

    public function store(EquipeRequest $request): JsonResponse
    {
        $equipe = $this->service->criar($request->validated());

        return Resposta::criado(EquipeResource::make($equipe), url("/api/v1/equipes/{$equipe->uuid}"));
    }

    public function profissionais(VincularRequest $request, Equipe $equipe): EquipeResource
    {
        return EquipeResource::make(
            $this->service->vincularProfissional($equipe, $request->validated('profissional_id'), $request->validated('funcao'))
        );
    }

    public function pacientes(VincularRequest $request, Equipe $equipe): EquipeResource
    {
        return EquipeResource::make(
            $this->service->vincularPaciente($equipe, $request->validated('paciente_id'), $request->validated('inicio_em'))
        );
    }
}

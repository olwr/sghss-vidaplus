<?php

namespace App\Domains\Prontuarios\Http\Controllers;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Prontuarios\Http\Requests\ProntuarioRequest;
use App\Domains\Prontuarios\Http\Requests\RegistroClinicoRequest;
use App\Domains\Prontuarios\Http\Resources\ProntuarioResource;
use App\Domains\Prontuarios\Http\Resources\RegistroClinicoResource;
use App\Domains\Prontuarios\Models\Prontuario;
use App\Domains\Prontuarios\Policies\VinculoClinico;
use App\Domains\Prontuarios\Services\ProntuarioService;
use App\Domains\Seguranca\Services\LogAcessoService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\AcaoAcesso;
use App\Shared\Http\Resposta;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProntuarioController extends Controller
{
    use VinculoClinico;

    public function __construct(
        private readonly ProntuarioService $service,
        private readonly LogAcessoService $acessos,
    ) {
    }

    public function store(ProntuarioRequest $request): JsonResponse
    {
        $paciente = Paciente::withoutGlobalScopes()->where('uuid', $request->validated('paciente_id'))->firstOrFail();
        if (! $request->user()->can('prontuarios.editar') || ! $this->podeAcessarPaciente($request->user(), $paciente)) {
            throw new AuthorizationException('Sem acesso ao prontuário deste paciente.');
        }
        $prontuario = $this->service->criar($paciente, $request->validated());

        return Resposta::criado(
            ProntuarioResource::make($prontuario->load('paciente')),
            url("/api/v1/pacientes/{$paciente->uuid}/prontuario"),
        );
    }

    /** Toda leitura do prontuário gera um registro em logs_acesso (RF010/LGPD). */
    public function show(Paciente $paciente): ProntuarioResource
    {
        $prontuario = Prontuario::query()->where('paciente_id', $paciente->id)->firstOrFail();
        Gate::authorize('view', $prontuario);

        $this->acessos->registrar(AcaoAcesso::Leitura, 'prontuario', $prontuario, $paciente->id);

        return ProntuarioResource::make($prontuario->load(['paciente', 'registros' => fn ($q) => $q->latest('registrado_em')->with('profissional')]));
    }

    public function update(ProntuarioRequest $request, Prontuario $prontuario): ProntuarioResource
    {
        Gate::authorize('update', $prontuario);

        return ProntuarioResource::make($this->service->atualizar($prontuario, $request->validated(), $request->user())->load('paciente'));
    }

    public function registrar(RegistroClinicoRequest $request, Prontuario $prontuario): JsonResponse
    {
        Gate::authorize('registrar', $prontuario);
        $registro = $this->service->registrar($prontuario, $request->validated(), $request->user());

        return Resposta::criado(
            RegistroClinicoResource::make($registro->load('profissional')),
            url("/api/v1/prontuarios/{$prontuario->uuid}"),
        );
    }
}

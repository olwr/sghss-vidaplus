<?php

namespace App\Domains\Prontuarios\Http\Controllers;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Prontuarios\Http\Requests\ReceitaRequest;
use App\Domains\Prontuarios\Http\Resources\ReceitaResource;
use App\Domains\Prontuarios\Models\Receita;
use App\Domains\Prontuarios\Services\ReceitaService;
use App\Domains\Seguranca\Services\LogAcessoService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\AcaoAcesso;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReceitaController extends Controller
{
    public function __construct(
        private readonly ReceitaService $service,
        private readonly LogAcessoService $acessos,
    ) {
    }

    public function store(ReceitaRequest $request): JsonResponse
    {
        $paciente = Paciente::withoutGlobalScopes()->where('uuid', $request->validated('paciente_id'))->firstOrFail();
        Gate::authorize('emitir', [Receita::class, $paciente]);

        $receita = $this->service->emitir($paciente, $request->validated(), $request->user());

        return Resposta::criado(ReceitaResource::make($receita), url("/api/v1/receitas/{$receita->uuid}"));
    }

    public function show(Receita $receita): ReceitaResource
    {
        Gate::authorize('view', $receita);

        return ReceitaResource::make($receita->load(['itens', 'paciente', 'profissional']));
    }

    public function pdf(Receita $receita): Response
    {
        Gate::authorize('view', $receita);
        $this->acessos->registrar(AcaoAcesso::Download, 'receita', $receita, $receita->paciente_id);

        return response($this->service->pdf($receita), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receita-'.$receita->uuid.'.pdf"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function cancelar(Receita $receita): ReceitaResource
    {
        Gate::authorize('cancelar', $receita);

        return ReceitaResource::make($this->service->cancelar($receita));
    }
}

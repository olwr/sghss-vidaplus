<?php

namespace App\Domains\Prontuarios\Http\Controllers;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Prontuarios\Http\Requests\ExameRequest;
use App\Domains\Prontuarios\Http\Requests\ResultadoExameRequest;
use App\Domains\Prontuarios\Http\Resources\ExameResource;
use App\Domains\Prontuarios\Models\Exame;
use App\Domains\Prontuarios\Services\ExameService;
use App\Domains\Prontuarios\Services\ReceitaService;
use App\Domains\Seguranca\Services\LogAcessoService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\AcaoAcesso;
use App\Shared\Enums\StatusExame;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExameController extends Controller
{
    public function __construct(
        private readonly ExameService $service,
        private readonly LogAcessoService $acessos,
    ) {
    }

    public function store(ExameRequest $request): JsonResponse
    {
        $paciente = Paciente::withoutGlobalScopes()->where('uuid', $request->validated('paciente_id'))->firstOrFail();
        Gate::authorize('solicitar', [Exame::class, $paciente]);
        $exame = $this->service->solicitar($paciente, $request->validated(), $request->user());

        return Resposta::criado(ExameResource::make($exame), url("/api/v1/exames/{$exame->uuid}"));
    }

    public function show(Exame $exame): ExameResource
    {
        Gate::authorize('view', $exame);

        return ExameResource::make($exame->load(['paciente', 'resultados']));
    }

    public function update(ExameRequest $request, Exame $exame): ExameResource
    {
        Gate::authorize('registrarResultado', $exame);

        return ExameResource::make($this->service->mudarStatus($exame, StatusExame::from($request->validated('status'))));
    }

    public function registrarResultado(ResultadoExameRequest $request, Exame $exame): JsonResponse
    {
        Gate::authorize('registrarResultado', $exame);
        $this->service->registrarResultado($exame, $request->validated(), $request->file('arquivo'), $request->user());

        return Resposta::criado(ExameResource::make($exame->refresh()->load('resultados')), url("/api/v1/exames/{$exame->uuid}/resultado"));
    }

    /** Download autenticado e auditado: o arquivo nunca tem URL pública. */
    public function resultado(Exame $exame): StreamedResponse|JsonResponse
    {
        Gate::authorize('view', $exame);
        $resultado = $this->service->resultadoAtual($exame);
        abort_if($resultado === null, 404, 'Resultado ainda não disponível.');

        $this->acessos->registrar(AcaoAcesso::Download, 'resultado_exame', $resultado, $exame->paciente_id);

        if (! $resultado->arquivo_path) {
            return response()->json(['resumo' => $resultado->resumo_criptografado]);
        }

        return Storage::disk(ReceitaService::DISCO)->download(
            $resultado->arquivo_path,
            $resultado->arquivo_nome_original ?: 'resultado',
            ['Cache-Control' => 'no-store, private'],
        );
    }
}

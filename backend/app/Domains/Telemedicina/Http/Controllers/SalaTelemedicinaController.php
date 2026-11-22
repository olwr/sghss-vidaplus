<?php

namespace App\Domains\Telemedicina\Http\Controllers;

use App\Domains\Telemedicina\Http\Requests\EventoSessaoRequest;
use App\Domains\Telemedicina\Models\SalaTelemedicina;
use App\Domains\Telemedicina\Services\SalaTelemedicinaService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\EventoSessaoTelemedicina;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaTelemedicinaController extends Controller
{
    public function __construct(private readonly SalaTelemedicinaService $service)
    {
    }

    public function entrar(Request $request, SalaTelemedicina $sala): JsonResponse
    {
        return response()->json($this->service->entrar($sala, $request->user()));
    }

    public function eventos(EventoSessaoRequest $request, SalaTelemedicina $sala): JsonResponse
    {
        $this->service->registrarEvento(
            $sala,
            $request->user(),
            EventoSessaoTelemedicina::from($request->validated('evento')),
            $request->validated('detalhes'),
        );

        return Resposta::semConteudo();
    }

    public function encerrar(Request $request, SalaTelemedicina $sala): JsonResponse
    {
        $sala = $this->service->encerrar($sala, $request->user());

        return response()->json(['id' => $sala->uuid, 'status' => $sala->status, 'encerrada_em' => $sala->encerrada_em?->toIso8601String()]);
    }
}

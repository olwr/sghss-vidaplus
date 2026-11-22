<?php

namespace App\Domains\Administracao\Http\Controllers;

use App\Domains\Administracao\Services\AnonimizacaoService;
use App\Domains\Pacientes\Http\Resources\PacienteResource;
use App\Domains\Pacientes\Models\Paciente;
use App\Http\Controllers\Controller;

class AnonimizacaoController extends Controller
{
    public function __invoke(Paciente $paciente, AnonimizacaoService $service): PacienteResource
    {
        return PacienteResource::make($service->anonimizar($paciente)->load('unidade'));
    }
}

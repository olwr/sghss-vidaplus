<?php

use App\Domains\Pacientes\Http\Controllers\PacienteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('pacientes', PacienteController::class)->parameters(['pacientes' => 'paciente']);
    Route::get('pacientes/{paciente}/equipe', [PacienteController::class, 'equipe']);
});

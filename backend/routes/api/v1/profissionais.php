<?php

use App\Domains\Profissionais\Http\Controllers\EquipeController;
use App\Domains\Profissionais\Http\Controllers\ProfissionalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('can:profissionais.ver')->group(function () {
        Route::get('profissionais', [ProfissionalController::class, 'index']);
        Route::get('profissionais/{profissional}', [ProfissionalController::class, 'show']);
        Route::get('profissionais/{profissional}/agenda', [ProfissionalController::class, 'agenda']);
    });
    Route::middleware('can:profissionais.gerenciar')->group(function () {
        Route::post('profissionais', [ProfissionalController::class, 'store']);
        Route::delete('profissionais/{profissional}', [ProfissionalController::class, 'destroy']);
    });

    Route::middleware('can:equipes.ver')->group(function () {
        Route::get('equipes', [EquipeController::class, 'index']);
        Route::get('equipes/{equipe}', [EquipeController::class, 'show']);
    });
    Route::middleware('can:equipes.gerenciar')->group(function () {
        Route::post('equipes', [EquipeController::class, 'store']);
        Route::post('equipes/{equipe}/profissionais', [EquipeController::class, 'profissionais']);
        Route::post('equipes/{equipe}/pacientes', [EquipeController::class, 'pacientes']);
    });
});

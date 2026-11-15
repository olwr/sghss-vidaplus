<?php

use App\Domains\Administracao\Http\Controllers\AnonimizacaoController;
use App\Domains\Administracao\Http\Controllers\UsuarioAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::middleware('can:usuarios.gerenciar')->group(function () {
        Route::get('usuarios', [UsuarioAdminController::class, 'index']);
        Route::get('usuarios/{usuario}', [UsuarioAdminController::class, 'show']);
        Route::patch('usuarios/{usuario}', [UsuarioAdminController::class, 'update']);
        Route::post('usuarios/{usuario}/revogar-sessoes', [UsuarioAdminController::class, 'revogarSessoes']);
    });
    Route::get('perfis', [UsuarioAdminController::class, 'perfis'])->middleware('can:perfis.gerenciar');
});

// LGPD: anonimização (respeita a guarda legal do prontuário)
Route::post('lgpd/pacientes/{paciente}/anonimizar', AnonimizacaoController::class)
    ->middleware(['auth:sanctum', 'can:lgpd.anonimizar']);

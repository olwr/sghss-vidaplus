<?php

use App\Domains\Transferencias\Http\Controllers\TransferenciaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('transferencias', [TransferenciaController::class, 'store'])->middleware('can:transferencias.registrar');
    Route::middleware('can:transferencias.gerenciar')->group(function () {
        Route::get('transferencias', [TransferenciaController::class, 'index']);
        Route::get('transferencias/{transferencia}', [TransferenciaController::class, 'show']);
    });
});

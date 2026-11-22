<?php

use App\Domains\Unidades\Http\Controllers\UnidadeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('unidades', [UnidadeController::class, 'index'])->middleware('can:unidades.ver');
    Route::get('unidades/{unidade}', [UnidadeController::class, 'show'])->middleware('can:unidades.ver');

    Route::middleware('can:unidades.gerenciar')->group(function () {
        Route::post('unidades', [UnidadeController::class, 'store']);
        Route::patch('unidades/{unidade}', [UnidadeController::class, 'update']);
        Route::patch('unidades/{unidade}/status', [UnidadeController::class, 'status']);
    });
});

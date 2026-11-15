<?php

use App\Domains\Relatorios\Http\Controllers\RelatorioController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('relatorios')->group(function () {
    Route::middleware('can:relatorios.exportar')->group(function () {
        Route::post('exportacoes', [RelatorioController::class, 'exportar']);
        Route::get('exportacoes/{id}', [RelatorioController::class, 'exportacao']);
    });
    Route::get('{tipo}', [RelatorioController::class, 'show'])->middleware('can:relatorios.ver');
});

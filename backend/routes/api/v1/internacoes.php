<?php

use App\Domains\Internacoes\Http\Controllers\InternacaoController;
use App\Domains\Internacoes\Http\Controllers\LeitoController;
use App\Domains\Internacoes\Http\Controllers\SuprimentoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('can:leitos.ver')->get('leitos', [LeitoController::class, 'index']);
    Route::middleware('can:leitos.gerenciar')->group(function () {
        Route::post('leitos', [LeitoController::class, 'store']);
        Route::patch('leitos/{leito}', [LeitoController::class, 'update']);
    });

    Route::middleware('can:internacoes.ver')->group(function () {
        Route::get('internacoes', [InternacaoController::class, 'index']);
        Route::get('internacoes/{internacao}', [InternacaoController::class, 'show']);
    });
    Route::post('internacoes', [InternacaoController::class, 'store'])->middleware('can:internacoes.solicitar');
    Route::middleware('can:internacoes.gerenciar')->group(function () {
        Route::post('internacoes/{internacao}/admitir', [InternacaoController::class, 'admitir']);
        Route::post('internacoes/{internacao}/alta', [InternacaoController::class, 'alta']);
        Route::post('internacoes/{internacao}/cancelar', [InternacaoController::class, 'cancelar']);
    });

    Route::middleware('can:suprimentos.ver')->get('suprimentos', [SuprimentoController::class, 'index']);
    Route::middleware('can:suprimentos.gerenciar')->group(function () {
        Route::post('suprimentos', [SuprimentoController::class, 'store']);
        Route::patch('suprimentos/{suprimento}', [SuprimentoController::class, 'update']);
        Route::post('suprimentos/{suprimento}/movimentar', [SuprimentoController::class, 'movimentar']);
    });
});

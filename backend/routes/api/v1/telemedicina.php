<?php

use App\Domains\Telemedicina\Http\Controllers\SalaTelemedicinaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('telemedicina/salas')->group(function () {
    Route::post('{sala}/entrar', [SalaTelemedicinaController::class, 'entrar']);
    Route::post('{sala}/eventos', [SalaTelemedicinaController::class, 'eventos']);
    Route::post('{sala}/encerrar', [SalaTelemedicinaController::class, 'encerrar']);
});

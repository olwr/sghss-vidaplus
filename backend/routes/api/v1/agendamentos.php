<?php

use App\Domains\Agendamentos\Http\Controllers\AgendaController;
use App\Domains\Agendamentos\Http\Controllers\AgendamentoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // agendas (a rota literal "disponibilidade" vem antes de {agenda})
    Route::get('agendas/disponibilidade', [AgendaController::class, 'disponibilidade'])->middleware('can:agendamentos.criar');
    Route::get('agendas', [AgendaController::class, 'index']);
    Route::post('agendas', [AgendaController::class, 'store']);
    Route::patch('agendas/{agenda}', [AgendaController::class, 'update']);

    Route::get('agendamentos', [AgendamentoController::class, 'index']);
    Route::post('agendamentos', [AgendamentoController::class, 'store']);
    Route::get('agendamentos/{agendamento}', [AgendamentoController::class, 'show']);
    Route::post('agendamentos/{agendamento}/confirmar', [AgendamentoController::class, 'confirmar']);
    Route::post('agendamentos/{agendamento}/cancelar', [AgendamentoController::class, 'cancelar']);
    Route::post('agendamentos/{agendamento}/remarcar', [AgendamentoController::class, 'remarcar'])->name('agendamentos.remarcar');
    Route::post('agendamentos/{agendamento}/iniciar', [AgendamentoController::class, 'iniciar']);
    Route::post('agendamentos/{agendamento}/concluir', [AgendamentoController::class, 'concluir']);
    Route::post('agendamentos/{agendamento}/faltou', [AgendamentoController::class, 'faltou']);
});

<?php

use App\Domains\Prontuarios\Http\Controllers\ExameController;
use App\Domains\Prontuarios\Http\Controllers\ProntuarioController;
use App\Domains\Prontuarios\Http\Controllers\ReceitaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // prontuário: a autorização fina está nas Policies; o middleware `can` barra os perfis sem a permissão
    Route::post('prontuarios', [ProntuarioController::class, 'store'])->middleware('can:prontuarios.editar');
    Route::get('pacientes/{paciente}/prontuario', [ProntuarioController::class, 'show'])->middleware('can:prontuarios.ver');
    Route::patch('prontuarios/{prontuario}', [ProntuarioController::class, 'update'])->middleware('can:prontuarios.editar');
    Route::post('prontuarios/{prontuario}/registros', [ProntuarioController::class, 'registrar'])->middleware('can:registros_clinicos.criar');

    Route::post('receitas', [ReceitaController::class, 'store'])->middleware('can:receitas.emitir');
    Route::get('receitas/{receita}', [ReceitaController::class, 'show'])->middleware('can:receitas.ver');
    Route::get('receitas/{receita}/pdf', [ReceitaController::class, 'pdf'])->middleware('can:receitas.ver');
    Route::post('receitas/{receita}/cancelar', [ReceitaController::class, 'cancelar'])->middleware('can:receitas.cancelar');

    Route::post('exames', [ExameController::class, 'store'])->middleware('can:exames.solicitar');
    Route::get('exames/{exame}', [ExameController::class, 'show'])->middleware('can:exames.ver');
    Route::patch('exames/{exame}', [ExameController::class, 'update'])->middleware('can:exames.registrar_resultado');
    Route::post('exames/{exame}/resultado', [ExameController::class, 'registrarResultado'])->middleware('can:exames.registrar_resultado');
    Route::get('exames/{exame}/resultado', [ExameController::class, 'resultado'])->middleware('can:exames.ver');
});

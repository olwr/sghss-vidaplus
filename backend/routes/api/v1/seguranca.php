<?php

use App\Domains\Seguranca\Http\Controllers\AuditoriaController;
use App\Domains\Seguranca\Http\Controllers\TitularController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

Route::middleware('auth:sanctum')->group(function () {
    // ---- direitos do titular (LGPD): só o próprio paciente
    Route::prefix('titular')->group(function () {
        Route::get('meus-dados', [TitularController::class, 'meusDados'])->middleware('can:lgpd.exportar');
        Route::patch('meus-dados', [TitularController::class, 'atualizar'])->middleware('can:lgpd.exportar');
        Route::get('exportacao', [TitularController::class, 'exportacao'])->middleware('can:lgpd.exportar');
        Route::get('consentimentos', [TitularController::class, 'consentimentos'])->middleware('can:lgpd.consentimentos');
        Route::post('consentimentos', [TitularController::class, 'conceder'])->middleware('can:lgpd.consentimentos');
        Route::delete('consentimentos/{finalidade}', [TitularController::class, 'revogar'])->middleware('can:lgpd.consentimentos');
    });

    // ---- auditoria: leitura para quem tem `auditoria.ver`; escrita é recusada (CT029)
    Route::prefix('auditoria')->group(function () {
        Route::middleware('can:auditoria.ver')->group(function () {
            Route::get('atividades', [AuditoriaController::class, 'atividades']);
            Route::get('acessos', [AuditoriaController::class, 'acessos']);
            Route::get('conformidade-lgpd', [AuditoriaController::class, 'conformidade']);
        });
        Route::match(['put', 'patch', 'delete'], '{qualquer?}', function () {
            throw new AccessDeniedHttpException('Registros de auditoria são imutáveis.');
        })->where('qualquer', '.*');
    });
});

<?php

declare(strict_types=1);

namespace App\Shared\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Sonda de disponibilidade. Pública e SEM dados sensíveis:
 * só informa se cada dependência responde. Detalhes do erro vão para o log.
 */
class SaudeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $componentes = [
            'banco' => $this->verificar(fn () => DB::select('select 1')),
            'cache' => $this->verificar(function () {
                Cache::put('saude', '1', 10);
                if (Cache::get('saude') !== '1') {
                    throw new \RuntimeException('cache sem leitura');
                }
            }),
            'armazenamento' => $this->verificar(fn () => Storage::disk('s3')->exists('.saude')),
        ];

        $ok = ! in_array(false, $componentes, true);

        return response()->json(
            ['status' => $ok ? 'ok' : 'degradado', 'componentes' => array_map(fn ($c) => $c ? 'ok' : 'falha', $componentes)],
            $ok ? 200 : 503,
        );
    }

    private function verificar(callable $teste): bool
    {
        try {
            $teste();

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}

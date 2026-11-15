<?php

namespace App\Shared\Http;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Paginação e ordenação padronizadas (?page=&per_page=&ordem=&direcao=). */
final class Consulta
{
    public static function porPagina(Request $request): int
    {
        return max(1, min(100, (int) $request->query('per_page', 15)));
    }

    /** @param list<string> $permitidos whitelist de colunas ordenáveis (nunca confie no nome vindo do cliente) */
    public static function ordenar(Builder $consulta, Request $request, array $permitidos, string $padrao = 'id'): Builder
    {
        $campo = (string) $request->query('ordem', $padrao);
        $campo = in_array($campo, $permitidos, true) ? $campo : $padrao;
        $direcao = strtolower((string) $request->query('direcao', 'asc')) === 'desc' ? 'desc' : 'asc';

        return $consulta->orderBy($campo, $direcao)->orderBy('id');
    }
}

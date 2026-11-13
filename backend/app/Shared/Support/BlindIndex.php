<?php

namespace App\Shared\Support;

use RuntimeException;

final class BlindIndex
{
    public static function cpf(string $cpf): string
    {
        $chave = (string) config('sghss.blind_index_key');

        // fail closed: hash_hmac aceita chave vazia e geraria um índice "válido", porém sem segredo
        if ($chave === '') {
            throw new RuntimeException('BLIND_INDEX_KEY não configurada.');
        }

        $digitos = preg_replace('/\D/', '', $cpf);
        return hash_hmac('sha256', $digitos, $chave);
    }
}

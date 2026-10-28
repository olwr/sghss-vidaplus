<?php

namespace App\Shared\Support;

final class BlindIndex
{
    public static function cpf(string $cpf): string
    {
        $digitos = preg_replace('/\D/', '', $cpf);
        return hash_hmac('sha256', $digitos, (string) config('sghss.blind_index_key'));
    }
}

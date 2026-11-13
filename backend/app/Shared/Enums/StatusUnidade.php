<?php

namespace App\Shared\Enums;

enum StatusUnidade: string
{
    case Ativa = 'ativa';
    case Desativada = 'desativada';
    case EmReforma = 'em_reforma';
    case EmRecesso = 'em_recesso';

    public function label(): string
    {
        return match ($this) {
            self::Ativa => 'Ativa',
            self::Desativada => 'Desativada',
            self::EmReforma => 'Em reforma',
            self::EmRecesso => 'Em recesso',
        };
    }
}

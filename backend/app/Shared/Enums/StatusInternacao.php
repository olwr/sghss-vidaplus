<?php

namespace App\Shared\Enums;

enum StatusInternacao: string
{
    case Solicitada = 'solicitada';
    case Ativa = 'ativa';
    case Alta = 'alta';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Solicitada => 'Solicitada',
            self::Ativa => 'Ativa',
            self::Alta => 'Alta',
            self::Cancelada => 'Cancelada',
        };
    }
}

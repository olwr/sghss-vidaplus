<?php

namespace App\Shared\Enums;

enum StatusReceita: string
{
    case Emitida = 'emitida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match($this) {
            self::Emitida => 'Emitida',
            self::Cancelada => 'Cancelada',
        };
    }
}

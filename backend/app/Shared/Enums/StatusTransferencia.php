<?php

namespace App\Shared\Enums;

enum StatusTransferencia: string
{
    case Pendente = 'pendente';
    case Concluida = 'concluida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Concluida => 'Concluída',
            self::Cancelada => 'Cancelada',
        };
    }
}

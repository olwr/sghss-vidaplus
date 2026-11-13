<?php

namespace App\Shared\Enums;

enum TipoAgendamento: string
{
    case Consulta = 'consulta';
    case Exame = 'exame';

    public function label(): string
    {
        return match ($this) {
            self::Consulta => 'Consulta',
            self::Exame => 'Exame',
        };
    }
}

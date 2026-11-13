<?php

namespace App\Shared\Enums;

enum TipoTransferencia: string
{
    case Paciente = 'paciente';
    case Profissional = 'profissional';

    public function label(): string
    {
        return match ($this) {
            self::Paciente => 'Paciente',
            self::Profissional => 'Profissional',
        };
    }
}

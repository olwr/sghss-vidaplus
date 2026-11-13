<?php

namespace App\Shared\Enums;

enum TipoLeito: string
{
    case Enfermaria = 'enfermaria';
    case Apartamento = 'apartamento';
    case Uti = 'uti';

    public function label(): string
    {
        return match ($this) {
            self::Enfermaria => 'Enfermaria',
            self::Apartamento => 'Apartamento',
            self::Uti => 'UTI',
        };
    }
}

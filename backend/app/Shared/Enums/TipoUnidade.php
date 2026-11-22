<?php

namespace App\Shared\Enums;

enum TipoUnidade: string
{
    case Hospital = 'hospital';
    case Clinica = 'clinica';
    case Laboratorio = 'laboratorio';
    case HomeCare = 'home_care';

    public function label(): string
    {
        return match ($this) {
            self::Hospital => 'Hospital',
            self::Clinica => 'Clínica',
            self::Laboratorio => 'Laboratório',
            self::HomeCare => 'Home care',
        };
    }
}

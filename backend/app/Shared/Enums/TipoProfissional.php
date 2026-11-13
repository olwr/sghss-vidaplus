<?php

namespace App\Shared\Enums;

enum TipoProfissional: string
{
    case Medico = 'medico';
    case Enfermeiro = 'enfermeiro';
    case Tecnico = 'tecnico';

    public function label(): string
    {
        return match ($this) {
            self::Medico => 'Médico',
            self::Enfermeiro => 'Enfermeiro',
            self::Tecnico => 'Técnico',
        };
    }
}

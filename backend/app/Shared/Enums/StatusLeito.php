<?php

namespace App\Shared\Enums;

enum StatusLeito: string
{
    case Disponivel = 'disponivel';
    case Ocupado = 'ocupado';
    case Manutencao = 'manutencao';

    public function label(): string
    {
        return match ($this) {
            self::Disponivel => 'Disponível',
            self::Ocupado => 'Ocupado',
            self::Manutencao => 'Manutenção',
        };
    }
}

<?php

namespace App\Shared\Enums;

enum StatusSalaTelemedicina: string
{
    case Criada = 'criada';
    case EmAndamento = 'em_andamento';
    case Encerrada = 'encerrada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Criada => 'Criada',
            self::EmAndamento => 'Em andamento',
            self::Encerrada => 'Encerrada',
            self::Cancelada => 'Cancelada',
        };
    }
}

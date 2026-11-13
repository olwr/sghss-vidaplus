<?php

namespace App\Shared\Enums;

enum StatusExame: string
{
    case Solicitado = 'solicitado';
    case Agendado = 'agendado';
    case Realizado = 'realizado';
    case ResultadoDisponivel = 'resultado_disponivel';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Solicitado => 'Solicitado',
            self::Agendado => 'Agendado',
            self::Realizado => 'Realizado',
            self::ResultadoDisponivel => 'Resultado disponível',
            self::Cancelado => 'Cancelado',
        };
    }
}

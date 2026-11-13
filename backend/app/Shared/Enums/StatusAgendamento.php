<?php

namespace App\Shared\Enums;

enum StatusAgendamento: string
{
    case Agendado = 'agendado';
    case Confirmado = 'confirmado';
    case EmAtendimento = 'em_atendimento';
    case Concluido = 'concluido';
    case Cancelado = 'cancelado';
    case Remarcado = 'remarcado';
    case Faltou = 'faltou';

    public function label(): string
    {
        return match ($this) {
            self::Agendado => 'Agendado',
            self::Confirmado => 'Confirmado',
            self::EmAtendimento => 'Em atendimento',
            self::Concluido => 'Concluído',
            self::Cancelado => 'Cancelado',
            self::Remarcado => 'Remarcado',
            self::Faltou => 'Faltou',
        };
    }
}

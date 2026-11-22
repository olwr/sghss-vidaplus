<?php

namespace App\Shared\Enums;

enum ModalidadeAgendamento: string
{
    case Presencial = 'presencial';
    case Telemedicina = 'telemedicina';

    public function label(): string
    {
        return match ($this) {
            self::Presencial => 'Presencial',
            self::Telemedicina => 'Telemedicina',
        };
    }
}

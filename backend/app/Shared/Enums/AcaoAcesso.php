<?php

namespace App\Shared\Enums;

enum AcaoAcesso: string
{
    case Leitura = 'leitura';
    case Exportacao = 'exportacao';
    case Download = 'download';

    public function label(): string
    {
        return match ($this) {
            self::Leitura => 'Leitura',
            self::Exportacao => 'Exportação',
            self::Download => 'Download',
        };
    }
}

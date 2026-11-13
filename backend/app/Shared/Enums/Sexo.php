<?php

namespace App\Shared\Enums;

enum Sexo: string
{
    case Feminino = 'feminino';
    case Masculino = 'masculino';
    case Outro = 'outro';
    case NaoInformado = 'nao_informado';

    public function label(): string
    {
        return match ($this) {
            self::Feminino => 'Feminino',
            self::Masculino => 'Masculino',
            self::Outro => 'Outro',
            self::NaoInformado => 'Não informado',
        };
    }
}

<?php

namespace App\Shared\Enums;

enum EventoSessaoTelemedicina: string
{
    case TokenEmitido = 'token_emitido';
    case VerificacaoDispositivo = 'verificacao_dispositivo';
    case Entrou = 'entrou';
    case Saiu = 'saiu';

    public function label(): string
    {
        return match ($this) {
            self::TokenEmitido => 'Token emitido',
            self::VerificacaoDispositivo => 'Verificação dispositivo',
            self::Entrou => 'Entrou',
            self::Saiu => 'Saiu',
        };
    }
}

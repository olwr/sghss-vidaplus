<?php

namespace App\Domains\Telemedicina\Services;

use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Telemedicina\Models\SalaTelemedicina;
use RuntimeException;

/**
 * JWT HS256 para o Jitsi (self-hosted, autenticação por JWT). Sem biblioteca: só hash_hmac nativo.
 */
final class JitsiTokenService
{
    /** @return array{jwt: string, expira_em: int} */
    public function emitir(Usuario $usuario, SalaTelemedicina $sala, bool $moderador): array
    {
        $segredo = (string) config('sghss.jitsi.app_secret');
        if ($segredo === '') {
            throw new RuntimeException('JITSI_APP_SECRET não configurado.');
        }

        $agora = time();
        $exp = min($sala->valida_ate->getTimestamp(), $agora + 2 * 3600);   // curta duração
        $payload = [
            'aud' => 'jitsi',
            'iss' => config('sghss.jitsi.app_id'),
            'sub' => config('sghss.jitsi.domain'),
            'room' => $sala->nome_sala,
            'nbf' => $agora - 10,
            'exp' => $exp,
            'context' => ['user' => ['name' => $usuario->nome, 'moderator' => $moderador]],
        ];

        $b64 = fn (string $d): string => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
        $cabecalho = $b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $corpo = $b64(json_encode($payload, JSON_THROW_ON_ERROR));
        $assinatura = $b64(hash_hmac('sha256', "$cabecalho.$corpo", $segredo, true));

        return ['jwt' => "$cabecalho.$corpo.$assinatura", 'expira_em' => $exp];
    }
}

<?php

namespace App\Domains\Telemedicina\Services;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Telemedicina\Models\LogSessaoTelemedicina;
use App\Domains\Telemedicina\Models\SalaTelemedicina;
use App\Shared\Enums\EventoSessaoTelemedicina;
use App\Shared\Enums\StatusSalaTelemedicina;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

class SalaTelemedicinaService
{
    public function __construct(private readonly JitsiTokenService $tokens)
    {
    }

    /** RF012: toda teleconsulta agendada ganha uma sala com janela de validade. */
    public function criarParaAgendamento(Agendamento $ag): SalaTelemedicina
    {
        $antes = (int) config('sghss.jitsi.antecedencia_min', 15);
        $depois = (int) config('sghss.jitsi.tolerancia_apos_fim_min', 30);

        $sala = new SalaTelemedicina([
            'agendamento_id' => $ag->id,
            // nome aleatório: nunca derivado de dados do paciente
            'nome_sala' => 'vp-'.Str::lower(Str::random(28)),
            'valida_a_partir_de' => $ag->inicio_em->subMinutes($antes),
            'valida_ate' => $ag->fim_em->addMinutes($depois),
        ]);
        $sala->save();

        return $sala->refresh();
    }

    /** @return array{sala: string, dominio: string, jwt: string, expira_em: int} */
    public function entrar(SalaTelemedicina $sala, Usuario $u): array
    {
        $papel = $this->papelNaSala($sala, $u);                      // 403 se não for participante

        if (in_array($sala->status, [StatusSalaTelemedicina::Encerrada, StatusSalaTelemedicina::Cancelada], true)
            || ! $sala->dentroDaJanela()) {
            throw new AuthorizationException('A sala não está disponível neste horário.');
        }

        $moderador = $papel === 'profissional';
        $token = $this->tokens->emitir($u, $sala, $moderador);

        if ($moderador && $sala->status === StatusSalaTelemedicina::Criada) {
            $sala->forceFill(['status' => StatusSalaTelemedicina::EmAndamento, 'iniciada_em' => now()])->save();
        }
        $this->registrar($sala, $u, EventoSessaoTelemedicina::TokenEmitido, ['papel' => $papel]);

        return [
            'sala' => $sala->nome_sala,
            'dominio' => (string) config('sghss.jitsi.domain'),
            'jwt' => $token['jwt'],
            'expira_em' => $token['expira_em'],
        ];
    }

    public function registrarEvento(SalaTelemedicina $sala, Usuario $u, EventoSessaoTelemedicina $evento, ?array $detalhes): void
    {
        $this->papelNaSala($sala, $u);
        $this->registrar($sala, $u, $evento, $detalhes);
    }

    public function encerrar(SalaTelemedicina $sala, Usuario $u): SalaTelemedicina
    {
        if ($this->papelNaSala($sala, $u) !== 'profissional') {
            throw new AuthorizationException('Somente o profissional encerra a sala.');
        }
        $sala->forceFill(['status' => StatusSalaTelemedicina::Encerrada, 'encerrada_em' => now()])->save();

        return $sala->refresh();
    }

    /** 'paciente' | 'profissional'; qualquer outra pessoa (inclusive administrador) => 403. */
    private function papelNaSala(SalaTelemedicina $sala, Usuario $u): string
    {
        $ag = $sala->agendamento()->withoutGlobalScopes()->with(['paciente', 'profissional'])->first();

        return match (true) {
            $ag?->profissional?->usuario_id === $u->id => 'profissional',
            $ag?->paciente?->usuario_id === $u->id => 'paciente',
            default => throw new AuthorizationException('Você não participa desta teleconsulta.'),
        };
    }

    private function registrar(SalaTelemedicina $sala, Usuario $u, EventoSessaoTelemedicina $evento, ?array $detalhes): void
    {
        LogSessaoTelemedicina::create([
            'sala_id' => $sala->id, 'usuario_id' => $u->id, 'evento' => $evento,
            'ip' => request()->ip(), 'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'detalhes' => $detalhes, 'ocorrido_em' => now(),
        ]);
    }
}

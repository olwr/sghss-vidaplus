<?php

namespace App\Domains\Agendamentos\Console;

use App\Domains\Agendamentos\Mail\LembreteConsultaMail;
use App\Domains\Agendamentos\Models\Agendamento;
use App\Shared\Enums\StatusAgendamento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarLembretes extends Command
{
    protected $signature = 'agendamentos:enviar-lembretes {--horas=24 : antecedência máxima}';

    protected $description = 'Envia e-mail de lembrete dos agendamentos das próximas horas (uma vez por agendamento).';

    public function handle(): int
    {
        $enviados = 0;
        $fuso = config('sghss.fuso_agenda');

        // job/CLI não tem usuário autenticado: o UnidadeScope não filtra (comportamento desejado)
        Agendamento::query()
            ->whereIn('status', [StatusAgendamento::Agendado->value, StatusAgendamento::Confirmado->value])
            ->whereNull('lembrete_enviado_em')
            ->whereBetween('inicio_em', [now(), now()->addHours((int) $this->option('horas'))])
            ->with(['paciente', 'profissional.usuario', 'unidade'])
            ->chunkById(100, function ($lote) use (&$enviados, $fuso) {
                foreach ($lote as $a) {
                    if ($a->paciente?->email) {
                        Mail::to($a->paciente->email)->queue(new LembreteConsultaMail([
                            'paciente' => $a->paciente->nome,
                            'unidade' => $a->unidade->nome,
                            'quando' => $a->inicio_em->setTimezone($fuso)->format('d/m/Y \à\s H:i'),
                        ]));
                    }
                    $a->forceFill(['lembrete_enviado_em' => now()])->saveQuietly();   // sem poluir a auditoria
                    $enviados++;
                }
            });

        $this->info("Lembretes enfileirados: {$enviados}");

        return self::SUCCESS;
    }
}

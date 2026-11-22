<?php

namespace App\Domains\Prontuarios\Policies;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Seguranca\Models\Usuario;

/**
 * Regra de acesso clínico compartilhada por Prontuário, Receita e Exame.
 * Paciente: só o próprio. Profissional de saúde: paciente da sua unidade OU com agendamento/equipe em comum.
 * Secretário e administrador: nunca (CT003) — nem têm as permissões clínicas.
 */
trait VinculoClinico
{
    protected function pacienteSemEscopo(int $pacienteId): ?Paciente
    {
        return Paciente::withoutGlobalScopes()->find($pacienteId);
    }

    protected function podeAcessarPaciente(Usuario $u, ?Paciente $paciente): bool
    {
        if (! $paciente) {
            return false;
        }
        if ($u->hasRole('paciente')) {
            return $paciente->usuario_id === $u->id;
        }
        if (! $u->hasRole('profissional_saude') || ! ($prof = $u->profissional)) {
            return false;
        }

        return $prof->unidade_id === $paciente->unidade_id
            || Agendamento::withoutGlobalScopes()->where('profissional_id', $prof->id)->where('paciente_id', $paciente->id)->exists()
            || $prof->equipes()->whereHas('pacientes', fn ($q) => $q->whereKey($paciente->id))->exists();
    }
}

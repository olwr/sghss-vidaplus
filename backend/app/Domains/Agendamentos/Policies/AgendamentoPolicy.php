<?php

namespace App\Domains\Agendamentos\Policies;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Seguranca\Models\Usuario;

class AgendamentoPolicy
{
    public function viewAny(Usuario $u): bool
    {
        return $u->can('agendamentos.listar');
    }

    public function view(Usuario $u, Agendamento $a): bool
    {
        if ($u->hasRole('paciente')) {
            return $a->paciente?->usuario_id === $u->id;
        }

        return $u->can('agendamentos.ver');
    }

    public function create(Usuario $u): bool
    {
        return $u->can('agendamentos.criar');
    }

    /** Confirmar: o próprio paciente ou a equipe que agenda. */
    public function confirmar(Usuario $u, Agendamento $a): bool
    {
        return $this->view($u, $a) && $u->can('agendamentos.criar');
    }

    public function cancelar(Usuario $u, Agendamento $a): bool
    {
        return $this->view($u, $a) && $u->can('agendamentos.cancelar');
    }

    public function remarcar(Usuario $u, Agendamento $a): bool
    {
        return $this->view($u, $a) && $u->can('agendamentos.remarcar');
    }

    /** Iniciar/concluir atendimento: somente o profissional do agendamento. */
    public function conduzir(Usuario $u, Agendamento $a): bool
    {
        return $u->can('registros_clinicos.criar') && $a->profissional?->usuario_id === $u->id;
    }
}

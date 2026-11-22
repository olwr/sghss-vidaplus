<?php

namespace App\Domains\Agendamentos\Policies;

use App\Domains\Agendamentos\Models\Agenda;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Seguranca\Models\Usuario;

class AgendaPolicy
{
    public function viewAny(Usuario $u): bool
    {
        return $u->can('agendas.ver');
    }

    public function create(Usuario $u): bool
    {
        return $u->can('agendas.gerenciar');
    }

    public function criarPara(Usuario $u, Profissional $p): bool
    {
        return $u->can('agendas.gerenciar')
            && (! $u->hasRole('profissional_saude') || $p->usuario_id === $u->id);
    }

    /** O profissional só altera a própria agenda; administrador/secretário conforme a permissão. */
    public function update(Usuario $u, Agenda $agenda): bool
    {
        if (! $u->can('agendas.gerenciar')) {
            return false;
        }

        return ! $u->hasRole('profissional_saude') || $agenda->profissional?->usuario_id === $u->id;
    }
}

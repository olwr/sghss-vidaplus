<?php

namespace App\Domains\Prontuarios\Policies;

use App\Domains\Prontuarios\Models\Prontuario;
use App\Domains\Seguranca\Models\Usuario;

class ProntuarioPolicy
{
    use VinculoClinico;

    public function view(Usuario $u, Prontuario $p): bool
    {
        return $u->can('prontuarios.ver') && $this->podeAcessarPaciente($u, $this->pacienteSemEscopo($p->paciente_id));
    }

    public function update(Usuario $u, Prontuario $p): bool
    {
        return $u->can('prontuarios.editar') && $this->podeAcessarPaciente($u, $this->pacienteSemEscopo($p->paciente_id));
    }

    public function registrar(Usuario $u, Prontuario $p): bool
    {
        return $u->can('registros_clinicos.criar') && $this->podeAcessarPaciente($u, $this->pacienteSemEscopo($p->paciente_id));
    }
}

<?php

namespace App\Domains\Prontuarios\Policies;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Prontuarios\Models\Receita;
use App\Domains\Seguranca\Models\Usuario;

class ReceitaPolicy
{
    use VinculoClinico;

    public function view(Usuario $u, Receita $r): bool
    {
        return $u->can('receitas.ver') && $this->podeAcessarPaciente($u, $this->pacienteSemEscopo($r->paciente_id));
    }

    public function emitir(Usuario $u, Paciente $paciente): bool
    {
        return $u->can('receitas.emitir') && $this->podeAcessarPaciente($u, $paciente);
    }

    /** Só quem emitiu cancela. */
    public function cancelar(Usuario $u, Receita $r): bool
    {
        return $u->can('receitas.cancelar') && $r->profissional?->usuario_id === $u->id;
    }
}

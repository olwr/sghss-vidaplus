<?php

namespace App\Domains\Pacientes\Policies;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Seguranca\Models\Usuario;

/**
 * O UnidadeScope já restringe a consulta à unidade do usuário (404 para outra unidade).
 * Aqui entram as regras por perfil: o paciente só enxerga a si mesmo.
 */
class PacientePolicy
{
    public function viewAny(Usuario $u): bool
    {
        return $u->can('pacientes.listar');
    }

    public function view(Usuario $u, Paciente $p): bool
    {
        if ($u->hasRole('paciente')) {
            return $p->usuario_id === $u->id;
        }

        return $u->can('pacientes.ver');
    }

    public function create(Usuario $u): bool
    {
        return $u->can('pacientes.criar');
    }

    public function update(Usuario $u, Paciente $p): bool
    {
        return $u->can('pacientes.editar');
    }

    public function delete(Usuario $u, Paciente $p): bool
    {
        return $u->can('pacientes.excluir');
    }
}

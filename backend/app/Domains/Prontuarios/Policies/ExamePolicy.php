<?php

namespace App\Domains\Prontuarios\Policies;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Prontuarios\Models\Exame;
use App\Domains\Seguranca\Models\Usuario;

class ExamePolicy
{
    use VinculoClinico;

    public function view(Usuario $u, Exame $e): bool
    {
        return $u->can('exames.ver') && $this->podeAcessarPaciente($u, $this->pacienteSemEscopo($e->paciente_id));
    }

    public function solicitar(Usuario $u, Paciente $paciente): bool
    {
        return $u->can('exames.solicitar') && $this->podeAcessarPaciente($u, $paciente);
    }

    public function registrarResultado(Usuario $u, Exame $e): bool
    {
        return $u->can('exames.registrar_resultado') && $this->podeAcessarPaciente($u, $this->pacienteSemEscopo($e->paciente_id));
    }
}

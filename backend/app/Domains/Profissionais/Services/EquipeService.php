<?php

namespace App\Domains\Profissionais\Services;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Equipe;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Exceptions\ConflictException;

class EquipeService
{
    public function criar(array $dados): Equipe
    {
        $unidade = Unidade::query()->where('uuid', $dados['unidade_id'])->firstOrFail();
        $equipe = new Equipe([...collect($dados)->except('unidade_id')->all(), 'unidade_id' => $unidade->id]);
        $equipe->save();

        return $equipe->refresh();
    }

    public function vincularProfissional(Equipe $equipe, string $profissionalUuid, ?string $funcao): Equipe
    {
        $profissional = Profissional::query()->where('uuid', $profissionalUuid)->firstOrFail();
        if ($equipe->profissionais()->whereKey($profissional->id)->exists()) {
            throw new ConflictException('O profissional já faz parte desta equipe.');
        }
        $equipe->profissionais()->attach($profissional->id, ['funcao' => $funcao]);

        return $equipe->load('profissionais.usuario');
    }

    public function vincularPaciente(Equipe $equipe, string $pacienteUuid, ?string $inicio): Equipe
    {
        $paciente = Paciente::query()->where('uuid', $pacienteUuid)->firstOrFail();
        if ($paciente->equipesAtivas()->whereKey($equipe->id)->exists()) {
            throw new ConflictException('O paciente já está vinculado a esta equipe.');
        }
        $equipe->pacientes()->attach($paciente->id, ['inicio_em' => $inicio ?? now()->toDateString()]);

        return $equipe->load('profissionais.usuario');
    }
}

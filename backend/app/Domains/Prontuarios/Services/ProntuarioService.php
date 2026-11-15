<?php

namespace App\Domains\Prontuarios\Services;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Prontuarios\Models\Prontuario;
use App\Domains\Prontuarios\Models\RegistroClinico;
use App\Domains\Seguranca\Models\Usuario;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\RegraDeNegocioException;
use Illuminate\Support\Facades\DB;

class ProntuarioService
{
    /** Nomes da API -> colunas (as colunas `_criptografado` guardam o dado cifrado via cast). */
    private const CAMPOS = [
        'alergias' => 'alergias_criptografado',
        'observacoes_gerais' => 'observacoes_gerais_criptografado',
    ];

    public function criar(Paciente $paciente, array $dados): Prontuario
    {
        if (Prontuario::query()->where('paciente_id', $paciente->id)->exists()) {
            throw new ConflictException('O paciente já possui prontuário.');
        }

        $prontuario = new Prontuario($this->mapear($dados) + ['paciente_id' => $paciente->id]);
        $prontuario->save();

        return $prontuario->refresh();
    }

    public function atualizar(Prontuario $prontuario, array $dados, Usuario $autor): Prontuario
    {
        $mapeado = $this->mapear($dados);
        $prontuario->fill($mapeado);
        // o log automático nunca guarda campo cifrado (sigilo); registramos QUAIS campos mudaram, sem o conteúdo (CT025)
        $alterados = array_keys($prontuario->getDirty());
        $prontuario->save();

        if ($alterados) {
            activity('prontuarios')->performedOn($prontuario)->causedBy($autor)->event('conteudo_alterado')
                ->withProperties(['campos_alterados' => $alterados])->log('Prontuário alterado');
        }

        return $prontuario->refresh();
    }

    public function registrar(Prontuario $prontuario, array $dados, Usuario $autor): RegistroClinico
    {
        $profissional = $autor->profissional ?? throw new RegraDeNegocioException('Usuário sem cadastro de profissional.');

        return DB::transaction(function () use ($prontuario, $dados, $profissional) {
            $agendamentoId = null;
            if (! empty($dados['agendamento_id'])) {
                $agendamentoId = Agendamento::withoutGlobalScopes()->where('uuid', $dados['agendamento_id'])
                    ->where('paciente_id', $prontuario->paciente_id)->firstOrFail()->id;
            }

            $registro = new RegistroClinico([
                'prontuario_id' => $prontuario->id,
                'profissional_id' => $profissional->id,
                'unidade_id' => $profissional->unidade_id,
                'agendamento_id' => $agendamentoId,
                'queixa_principal_criptografado' => $dados['queixa_principal'] ?? null,
                'diagnostico_criptografado' => $dados['diagnostico'] ?? null,
                'conduta_criptografado' => $dados['conduta'] ?? null,
                'observacoes_criptografado' => $dados['observacoes'] ?? null,
                'registrado_em' => now(),
            ]);
            $registro->save();

            return $registro->refresh();
        });
    }

    private function mapear(array $dados): array
    {
        $saida = array_intersect_key($dados, ['tipo_sanguineo' => 1]);
        foreach (self::CAMPOS as $api => $coluna) {
            if (array_key_exists($api, $dados)) {
                $saida[$coluna] = $dados[$api];
            }
        }

        return $saida;
    }
}

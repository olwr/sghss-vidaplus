<?php

namespace App\Domains\Internacoes\Services;

use App\Domains\Internacoes\Models\Internacao;
use App\Domains\Internacoes\Models\Leito;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Seguranca\Models\Usuario;
use App\Shared\Enums\StatusInternacao as S;
use App\Shared\Enums\StatusLeito;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\RegraDeNegocioException;
use Illuminate\Support\Facades\DB;

class InternacaoService
{
    public function solicitar(array $dados, Usuario $autor): Internacao
    {
        $solicitante = $autor->profissional ?? throw new RegraDeNegocioException('Usuário sem cadastro de profissional.');
        $paciente = Paciente::query()->where('uuid', $dados['paciente_id'])->firstOrFail();

        $emAndamento = Internacao::withoutGlobalScopes()->where('paciente_id', $paciente->id)
            ->whereIn('status', [S::Solicitada->value, S::Ativa->value])->exists();
        if ($emAndamento) {
            throw new ConflictException('O paciente já possui internação em andamento.');
        }

        $internacao = new Internacao([
            'paciente_id' => $paciente->id, 'unidade_id' => $paciente->unidade_id,
            'solicitante_id' => $solicitante->id, 'motivo_criptografado' => $dados['motivo'] ?? null,
        ]);
        $internacao->forceFill(['solicitada_em' => now()])->save();

        return $internacao->refresh();
    }

    /** Admissão: tranca o leito (FOR UPDATE), confere disponibilidade e ocupa; os índices parciais são a rede de segurança. */
    public function admitir(Internacao $internacao, array $dados): Internacao
    {
        return DB::transaction(function () use ($internacao, $dados) {
            $internacao = Internacao::query()->lockForUpdate()->findOrFail($internacao->id);
            if ($internacao->status !== S::Solicitada) {
                throw new RegraDeNegocioException('Somente internações solicitadas podem ser admitidas.');
            }

            $leito = Leito::query()->where('uuid', $dados['leito_id'])->lockForUpdate()->firstOrFail();
            if ($leito->unidade_id !== $internacao->unidade_id) {
                throw new RegraDeNegocioException('O leito pertence a outra unidade.');
            }
            if ($leito->status !== StatusLeito::Disponivel) {
                throw new ConflictException('O leito não está disponível.');
            }

            $responsavel = ! empty($dados['responsavel_id'])
                ? Profissional::query()->where('uuid', $dados['responsavel_id'])->firstOrFail()
                : null;

            $leito->forceFill(['status' => StatusLeito::Ocupado])->save();
            $internacao->forceFill([
                'status' => S::Ativa, 'leito_id' => $leito->id, 'admitida_em' => now(),
                'responsavel_id' => $responsavel?->id ?? $internacao->solicitante_id,
            ])->save();

            return $internacao->refresh()->load(['paciente', 'leito', 'unidade']);
        });
    }

    public function darAlta(Internacao $internacao): Internacao
    {
        return DB::transaction(function () use ($internacao) {
            $internacao = Internacao::query()->lockForUpdate()->findOrFail($internacao->id);
            if ($internacao->status !== S::Ativa) {
                throw new RegraDeNegocioException('Somente internações ativas recebem alta.');
            }

            $internacao->leito?->forceFill(['status' => StatusLeito::Disponivel])->save();    // libera o leito (RF006)
            $internacao->forceFill(['status' => S::Alta, 'alta_em' => now()])->save();

            return $internacao->refresh()->load(['paciente', 'leito', 'unidade']);
        });
    }

    public function cancelar(Internacao $internacao): Internacao
    {
        if ($internacao->status !== S::Solicitada) {
            throw new RegraDeNegocioException('Somente internações solicitadas podem ser canceladas.');
        }
        $internacao->forceFill(['status' => S::Cancelada])->save();

        return $internacao->refresh();
    }
}

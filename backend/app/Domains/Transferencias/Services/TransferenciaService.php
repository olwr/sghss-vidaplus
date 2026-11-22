<?php

namespace App\Domains\Transferencias\Services;

use App\Domains\Internacoes\Models\Internacao;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Transferencias\Models\Transferencia;
use App\Domains\Unidades\Models\Unidade;
use App\Domains\Unidades\Services\UnidadeService;
use App\Shared\Enums\StatusInternacao;
use App\Shared\Enums\StatusTransferencia;
use App\Shared\Enums\TipoTransferencia;
use App\Shared\Exceptions\RegraDeNegocioException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class TransferenciaService
{
    /** Registra e efetiva a transferência na mesma transação (MVP: sem fluxo de aprovação). */
    public function registrar(array $dados, Usuario $autor): Transferencia
    {
        $destino = UnidadeService::exigirOperacional(Unidade::query()->where('uuid', $dados['unidade_destino_id'])->firstOrFail());
        $tipo = TipoTransferencia::from($dados['tipo']);

        if ($tipo === TipoTransferencia::Profissional && ! $autor->can('transferencias.gerenciar')) {
            throw new AuthorizationException('Somente a administração transfere profissionais.');
        }

        return DB::transaction(function () use ($dados, $autor, $destino, $tipo) {
            $paciente = $profissional = null;
            if ($tipo === TipoTransferencia::Paciente) {
                $paciente = Paciente::query()->where('uuid', $dados['paciente_id'])->firstOrFail();
                $origemId = $paciente->unidade_id;
            } else {
                $profissional = Profissional::query()->where('uuid', $dados['profissional_id'])->firstOrFail();
                $origemId = $profissional->unidade_id;
            }

            if ($origemId === $destino->id) {
                throw new RegraDeNegocioException('A unidade de destino deve ser diferente da origem.');
            }

            $t = new Transferencia([
                'tipo' => $tipo, 'paciente_id' => $paciente?->id, 'profissional_id' => $profissional?->id,
                'unidade_origem_id' => $origemId, 'unidade_destino_id' => $destino->id, 'motivo' => $dados['motivo'] ?? null,
            ]);
            $t->forceFill(['solicitante_id' => $autor->id, 'status' => StatusTransferencia::Concluida, 'efetivada_em' => now()])->save();

            $paciente ? $this->moverPaciente($paciente, $destino) : $this->moverProfissional($profissional, $destino);

            return $t->refresh()->load(['paciente', 'profissional', 'origem', 'destino']);
        });
    }

    private function moverPaciente(Paciente $p, Unidade $destino): void
    {
        $internado = Internacao::withoutGlobalScopes()->where('paciente_id', $p->id)->whereIn('status', [StatusInternacao::Solicitada->value, StatusInternacao::Ativa->value])->exists();
        if ($internado) {
            throw new RegraDeNegocioException('Paciente com internação em andamento não pode ser transferido.');
        }
        $p->forceFill(['unidade_id' => $destino->id])->save();
    }

    private function moverProfissional(Profissional $p, Unidade $destino): void
    {
        $p->forceFill(['unidade_id' => $destino->id])->save();
        $p->usuario->forceFill(['unidade_id' => $destino->id])->save();
        $p->usuario->tokens()->delete();                                        // novo escopo => nova sessão
        $p->agendas()->withoutGlobalScopes()->update(['ativa' => false]);       // agendas da unidade antiga deixam de valer
    }
}

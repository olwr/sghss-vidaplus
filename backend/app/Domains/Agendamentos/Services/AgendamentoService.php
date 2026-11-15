<?php

namespace App\Domains\Agendamentos\Services;

use App\Domains\Agendamentos\Mail\AgendamentoCanceladoMail;
use App\Domains\Agendamentos\Mail\AgendamentoCriadoMail;
use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Telemedicina\Services\SalaTelemedicinaService;
use App\Domains\Unidades\Models\Unidade;
use App\Domains\Unidades\Services\UnidadeService;
use App\Shared\Enums\ModalidadeAgendamento;
use App\Shared\Enums\StatusSalaTelemedicina;
use App\Shared\Enums\StatusAgendamento as S;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\RegraDeNegocioException;
use App\Shared\Http\Consulta;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AgendamentoService
{
    /** Transições permitidas (qualquer outra => 422). Remarcar/cancelar têm métodos próprios. */
    private const TRANSICOES = [
        'agendado' => ['confirmado', 'cancelado', 'remarcado', 'faltou'],
        'confirmado' => ['em_atendimento', 'cancelado', 'remarcado', 'faltou'],
        'em_atendimento' => ['concluido'],
        'concluido' => [], 'cancelado' => [], 'remarcado' => [], 'faltou' => [],
    ];

    public function __construct(
        private readonly DisponibilidadeService $disponibilidade,
        private readonly SalaTelemedicinaService $salas,
    ) {
    }

    public function listar(Request $request, Usuario $u): LengthAwarePaginator
    {
        $consulta = Agendamento::query()->with(['paciente', 'profissional', 'unidade'])
            ->when($u->hasRole('paciente'), fn ($q) => $q->whereHas('paciente', fn ($p) => $p->where('usuario_id', $u->id)))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('de'), fn ($q, $d) => $q->where('inicio_em', '>=', $d))
            ->when($request->query('ate'), fn ($q, $d) => $q->where('inicio_em', '<=', $d))
            ->when($request->query('profissional'), fn ($q, $p) => $q->whereHas('profissional', fn ($x) => $x->where('uuid', $p)))
            ->when($request->query('paciente'), fn ($q, $p) => $q->whereHas('paciente', fn ($x) => $x->where('uuid', $p)));

        return Consulta::ordenar($consulta, $request, ['id', 'inicio_em', 'status', 'created_at'], 'inicio_em')
            ->paginate(Consulta::porPagina($request));
    }

    /** @return array{0: Agendamento, 1: bool} [agendamento, criado agora?] — false = reenvio idempotente */
    public function agendar(array $dados, Usuario $autor, ?string $chaveCliente): array
    {
        $chave = $chaveCliente ? hash('sha256', $autor->id.'|'.$chaveCliente) : null;

        if ($chave && ($existente = $this->porChave($chave))) {
            return [$existente, false];                                   // retry seguro (CT023)
        }

        try {
            $ag = DB::transaction(fn () => $this->criar($dados, $autor, $chave));
        } catch (QueryException $e) {
            $estado = $e->errorInfo[0] ?? null;
            if ($estado === '23P01') {                                    // exclusion_violation: concorrência
                throw new ConflictException('Horário indisponível para este profissional.');
            }
            if ($estado === '23505' && $chave && ($existente = $this->porChave($chave))) {
                return [$existente, false];                               // duas requisições simultâneas com a mesma chave
            }
            throw $e;
        }

        return [$ag, true];
    }

    public function confirmar(Agendamento $a): Agendamento
    {
        return $this->transicionar($a, S::Confirmado);
    }

    public function iniciar(Agendamento $a): Agendamento
    {
        return $this->transicionar($a, S::EmAtendimento);
    }

    public function concluir(Agendamento $a): Agendamento
    {
        return $this->transicionar($a, S::Concluido);
    }

    public function marcarFalta(Agendamento $a): Agendamento
    {
        return $this->transicionar($a, S::Faltou);
    }

    public function cancelar(Agendamento $a, Usuario $autor, ?string $motivo): Agendamento
    {
        $this->exigirTransicao($a, S::Cancelado);

        DB::transaction(function () use ($a, $autor, $motivo) {
            $a->forceFill([
                'status' => S::Cancelado, 'cancelado_por_id' => $autor->id,
                'cancelado_em' => now(), 'motivo_cancelamento' => $motivo,
            ])->save();
            $a->sala?->update(['status' => StatusSalaTelemedicina::Cancelada]);
            $this->aoConfirmarTransacao(fn () => $this->enviar(new AgendamentoCanceladoMail($this->dadosEmail($a)), $a));
        });

        return $a->refresh();
    }

    /** Remarcar = novo agendamento ligado ao anterior; o anterior vira `remarcado` e libera o horário. */
    public function remarcar(Agendamento $a, string $novoInicio, Usuario $autor): Agendamento
    {
        $this->exigirTransicao($a, S::Remarcado);

        try {
            return DB::transaction(function () use ($a, $novoInicio, $autor) {
                $a->forceFill(['status' => S::Remarcado])->save();        // libera o horário antes de ocupar o novo

                return $this->criar([
                    'tipo' => $a->tipo->value, 'modalidade' => $a->modalidade->value,
                    'paciente' => $a->paciente, 'profissional' => $a->profissional, 'unidade' => $a->unidade,
                    'inicio_em' => $novoInicio, 'duracao_min' => (int) $a->inicio_em->diffInMinutes($a->fim_em),
                    'motivo' => $a->motivo, 'valor' => $a->valor, 'remarcado_de_id' => $a->id,
                ], $autor, null);
            });
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23P01') {
                throw new ConflictException('Horário indisponível para este profissional.');
            }
            throw $e;
        }
    }

    private function criar(array $dados, Usuario $autor, ?string $chave): Agendamento
    {
        $paciente = $dados['paciente'] ?? $this->resolverPaciente($dados, $autor);
        $unidade = $dados['unidade'] ?? Unidade::query()->where('uuid', $dados['unidade_id'])->firstOrFail();
        $profissional = $dados['profissional']
            ?? Profissional::query()->where('uuid', $dados['profissional_id'])->firstOrFail();

        UnidadeService::exigirOperacional($unidade);
        if (! $profissional->ativo) {
            throw new RegraDeNegocioException('Profissional inativo.');
        }
        if ($autor->unidade_id !== null && $autor->unidade_id !== $unidade->id && ! $autor->hasRole('paciente')) {
            throw new RegraDeNegocioException('Você só pode agendar na sua unidade.');
        }

        $inicio = CarbonImmutable::parse($dados['inicio_em']);
        // a duração informada prevalece; senão vale a da grade semanal (padrão 30 min)
        $duracao = (int) ($dados['duracao_min'] ?? 0) ?: $this->duracaoPadrao($profissional, $unidade->id, $inicio);
        $fim = $inicio->addMinutes($duracao);
        $this->disponibilidade->validarDentroDaAgenda($profissional, $unidade->id, $inicio, $fim);

        // mensagem amigável; a exclusion constraint do banco é a garantia sob concorrência
        $conflito = Agendamento::withoutGlobalScopes()->ocupandoHorario()
            ->where('profissional_id', $profissional->id)
            ->where('inicio_em', '<', $fim)->where('fim_em', '>', $inicio)->exists();
        if ($conflito) {
            throw new ConflictException('Horário indisponível para este profissional.');
        }

        $ag = new Agendamento([
            'tipo' => $dados['tipo'], 'modalidade' => $dados['modalidade'],
            'paciente_id' => $paciente->id, 'profissional_id' => $profissional->id, 'unidade_id' => $unidade->id,
            'inicio_em' => $inicio, 'fim_em' => $fim, 'motivo' => $dados['motivo'] ?? null,
            'valor' => $autor->hasRole('paciente') ? null : ($dados['valor'] ?? null),
        ]);
        // campos de sistema não são mass-assignable (fora do $fillable de propósito)
        $ag->forceFill([
            'criado_por_id' => $autor->id,
            'remarcado_de_id' => $dados['remarcado_de_id'] ?? null,
            'chave_idempotencia' => $chave,
        ])->save();

        if ($ag->modalidade === ModalidadeAgendamento::Telemedicina) {
            $this->salas->criarParaAgendamento($ag);                       // RF012
        }

        $this->aoConfirmarTransacao(fn () => $this->enviar(new AgendamentoCriadoMail($this->dadosEmail($ag)), $ag));

        return $ag->refresh()->load(['paciente', 'profissional', 'unidade', 'sala']);
    }

    private function resolverPaciente(array $dados, Usuario $autor): Paciente
    {
        if ($autor->hasRole('paciente')) {
            return $autor->paciente ?? throw new RegraDeNegocioException('Usuário sem cadastro de paciente.');
        }
        if (empty($dados['paciente_id'])) {
            throw new RegraDeNegocioException('Informe o paciente.');
        }

        return Paciente::query()->where('uuid', $dados['paciente_id'])->firstOrFail();
    }

    private function duracaoPadrao(Profissional $p, int $unidadeId, CarbonImmutable $inicio): int
    {
        $agenda = $p->agendas()->withoutGlobalScopes()
            ->where('unidade_id', $unidadeId)->where('dia_semana', $inicio->setTimezone(config('sghss.fuso_agenda'))->dayOfWeek)
            ->where('ativa', true)->first();

        return $agenda?->duracao_atendimento_min ?? 30;
    }

    private function transicionar(Agendamento $a, S $para): Agendamento
    {
        $this->exigirTransicao($a, $para);
        $a->forceFill(['status' => $para])->save();

        return $a->refresh();
    }

    private function exigirTransicao(Agendamento $a, S $para): void
    {
        if (! in_array($para->value, self::TRANSICOES[$a->status->value], true)) {
            throw new RegraDeNegocioException("Não é possível passar de '{$a->status->value}' para '{$para->value}'.");
        }
    }

    private function porChave(string $chave): ?Agendamento
    {
        return Agendamento::withoutGlobalScopes()->where('chave_idempotencia', $chave)
            ->with(['paciente', 'profissional', 'unidade', 'sala'])->first();
    }

    /** E-mail só depois do COMMIT: nunca avisar o paciente de um agendamento que foi desfeito. */
    private function aoConfirmarTransacao(callable $acao): void
    {
        DB::afterCommit($acao);
    }

    private function enviar(object $mailable, Agendamento $a): void
    {
        $destino = $a->paciente?->email;
        if ($destino) {
            Mail::to($destino)->queue($mailable);
        }
    }

    /** Dados primitivos para o e-mail (a fila não precisa recarregar models). */
    private function dadosEmail(Agendamento $a): array
    {
        $a->loadMissing(['paciente', 'profissional.usuario', 'unidade']);
        $fuso = config('sghss.fuso_agenda');

        return [
            'paciente' => $a->paciente->nome,
            'profissional' => $a->profissional?->usuario?->nome,
            'unidade' => $a->unidade->nome,
            'modalidade' => $a->modalidade->value,
            'quando' => $a->inicio_em->setTimezone($fuso)->format('d/m/Y \à\s H:i'),
            'protocolo' => $a->uuid,
            'motivo_cancelamento' => $a->motivo_cancelamento,
        ];
    }
}

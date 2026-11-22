<?php

namespace App\Domains\Seguranca\Services;

use App\Domains\Pacientes\Http\Resources\PacienteResource;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Seguranca\Models\LogAcesso;
use App\Domains\Seguranca\Models\Usuario;
use App\Shared\Enums\AcaoAcesso;
use App\Shared\Exceptions\RegraDeNegocioException;

/** Direitos do titular (LGPD art. 18): acesso, portabilidade (exportação) e consentimento. */
class TitularService
{
    public function __construct(
        private readonly ConsentimentoService $consentimentos,
        private readonly LogAcessoService $acessos,
    ) {
    }

    public function pacienteDe(Usuario $u): Paciente
    {
        return $u->paciente ?? abort(404, 'Este usuário não possui cadastro de paciente.');
    }

    /** Todos os dados do titular, em JSON, inclusive QUEM acessou o prontuário dele (transparência). */
    public function exportar(Usuario $u): array
    {
        $p = $this->pacienteDe($u)->load([
            'consentimentos', 'agendamentos.profissional.usuario', 'receitas.itens', 'exames', 'prontuario.registros',
        ]);

        $this->acessos->registrar(AcaoAcesso::Exportacao, 'dados_titular', $p, $p->id);

        return [
            'gerado_em' => now()->toIso8601String(),
            'cadastro' => PacienteResource::make($p)->resolve(),
            'consentimentos' => $p->consentimentos->map(fn ($c) => [
                'finalidade' => $c->finalidade, 'versao_termo' => $c->versao_termo,
                'concedido_em' => $c->concedido_em?->toIso8601String(), 'revogado_em' => $c->revogado_em?->toIso8601String(),
            ]),
            'agendamentos' => $p->agendamentos->map(fn ($a) => [
                'id' => $a->uuid, 'tipo' => $a->tipo, 'status' => $a->status, 'inicio_em' => $a->inicio_em?->toIso8601String(),
                'profissional' => $a->profissional?->usuario?->nome,
            ]),
            'receitas' => $p->receitas->map(fn ($r) => [
                'id' => $r->uuid, 'emitida_em' => $r->emitida_em?->toIso8601String(),
                'itens' => $r->itens->map(fn ($i) => $i->only(['medicamento', 'dosagem', 'posologia', 'duracao', 'quantidade'])),
            ]),
            'exames' => $p->exames->map(fn ($e) => ['id' => $e->uuid, 'nome' => $e->nome, 'status' => $e->status]),
            'prontuario' => $p->prontuario ? [
                'tipo_sanguineo' => $p->prontuario->tipo_sanguineo,
                'alergias' => $p->prontuario->alergias_criptografado,
                'registros' => $p->prontuario->registros->map(fn ($r) => [
                    'registrado_em' => $r->registrado_em?->toIso8601String(),
                    'diagnostico' => $r->diagnostico_criptografado, 'conduta' => $r->conduta_criptografado,
                ]),
            ] : null,
            'acessos_aos_meus_dados' => LogAcesso::query()->where('paciente_id', $p->id)->latest('created_at')->limit(500)->get()
                ->map(fn ($l) => ['quando' => $l->created_at?->toIso8601String(), 'acao' => $l->acao, 'recurso' => $l->recurso_tipo]),
        ];
    }

    public function revogar(Usuario $u, string $finalidade): void
    {
        if ($finalidade === 'atendimento_saude') {
            // base legal de tutela da saúde/obrigação legal: não é revogável por aqui
            throw new RegraDeNegocioException('O consentimento para atendimento de saúde não pode ser revogado enquanto houver cadastro ativo.');
        }
        $this->consentimentos->revogar($this->pacienteDe($u), $finalidade);
    }
}

<?php

namespace App\Domains\Relatorios\Services;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Internacoes\Models\Internacao;
use App\Domains\Internacoes\Models\Leito;
use App\Domains\Internacoes\Models\Suprimento;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Enums\StatusAgendamento;
use App\Shared\Enums\StatusInternacao;
use App\Shared\Exceptions\RegraDeNegocioException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Relatórios gerenciais por agregação SQL (as queries passam pelo UnidadeScope do usuário logado).
 * Cada relatório devolve a mesma forma: titulo, colunas, linhas (matriz) — serve ao JSON e à exportação.
 */
class RelatorioService
{
    public const TIPOS = ['consultas', 'ocupacao-leitos', 'internacoes', 'financeiro', 'estoque-critico'];

    private const TTL = 300;

    /** @return array{titulo: string, colunas: list<string>, linhas: list<list<mixed>>} */
    public function gerar(string $tipo, array $filtros = []): array
    {
        if (! in_array($tipo, self::TIPOS, true)) {
            throw new RegraDeNegocioException('Relatório desconhecido.');
        }
        [$de, $ate] = $this->periodo($filtros);

        // o escopo faz parte da chave: dois usuários de unidades diferentes nunca compartilham o cache
        $chave = 'relatorio:'.$tipo.':'.(auth()->user()?->unidade_id ?? 'rede').':'.$de->toDateString().':'.$ate->toDateString();

        return Cache::remember($chave, self::TTL, fn () => match ($tipo) {
            'consultas' => $this->consultas($de, $ate),
            'ocupacao-leitos' => $this->ocupacaoLeitos(),
            'internacoes' => $this->internacoes($de, $ate),
            'financeiro' => $this->financeiro($de, $ate),
            'estoque-critico' => $this->estoqueCritico(),
        });
    }

    private function consultas(Carbon $de, Carbon $ate): array
    {
        $nomes = $this->nomesUnidades();
        $linhas = Agendamento::query()
            ->whereBetween('inicio_em', [$de, $ate])
            ->selectRaw('unidade_id, modalidade, status, count(*) as total')
            ->groupBy('unidade_id', 'modalidade', 'status')->orderBy('unidade_id')->get()
            ->map(fn ($r) => [$nomes[$r->unidade_id] ?? '-', $r->modalidade->value, $r->status->value, (int) $r->total])->all();

        return ['titulo' => 'Consultas por unidade, modalidade e status', 'colunas' => ['Unidade', 'Modalidade', 'Status', 'Total'], 'linhas' => $linhas];
    }

    private function ocupacaoLeitos(): array
    {
        $nomes = $this->nomesUnidades();
        $linhas = Leito::query()
            ->selectRaw("unidade_id, tipo, count(*) as total, count(*) filter (where status = 'ocupado') as ocupados")
            ->groupBy('unidade_id', 'tipo')->orderBy('unidade_id')->get()
            ->map(fn ($r) => [
                $nomes[$r->unidade_id] ?? '-', $r->tipo->value, (int) $r->total, (int) $r->ocupados,
                $r->total > 0 ? round($r->ocupados / $r->total * 100, 1) : 0.0,
            ])->all();

        return ['titulo' => 'Ocupação de leitos', 'colunas' => ['Unidade', 'Tipo', 'Leitos', 'Ocupados', 'Ocupação %'], 'linhas' => $linhas];
    }

    private function internacoes(Carbon $de, Carbon $ate): array
    {
        $nomes = $this->nomesUnidades();
        $linhas = Internacao::query()
            ->whereBetween('solicitada_em', [$de, $ate])
            ->selectRaw("unidade_id, status, count(*) as total,
                round(avg(extract(epoch from (alta_em - admitida_em)) / 86400)::numeric, 1) as dias_medios")
            ->groupBy('unidade_id', 'status')->orderBy('unidade_id')->get()
            ->map(fn ($r) => [$nomes[$r->unidade_id] ?? '-', $r->status->value, (int) $r->total, $r->status === StatusInternacao::Alta ? (float) $r->dias_medios : null])->all();

        return ['titulo' => 'Internações e permanência média', 'colunas' => ['Unidade', 'Status', 'Total', 'Dias médios (alta)'], 'linhas' => $linhas];
    }

    private function financeiro(Carbon $de, Carbon $ate): array
    {
        $nomes = $this->nomesUnidades();
        $linhas = Agendamento::query()
            ->where('status', StatusAgendamento::Concluido->value)->whereBetween('inicio_em', [$de, $ate])
            ->selectRaw("unidade_id, to_char(inicio_em, 'YYYY-MM') as mes, count(*) as atendimentos, coalesce(sum(valor), 0) as receita")
            ->groupBy('unidade_id', 'mes')->orderBy('mes')->orderBy('unidade_id')->get()
            ->map(fn ($r) => [$nomes[$r->unidade_id] ?? '-', $r->mes, (int) $r->atendimentos, (float) $r->receita])->all();

        return ['titulo' => 'Receita de atendimentos concluídos', 'colunas' => ['Unidade', 'Mês', 'Atendimentos', 'Receita (R$)'], 'linhas' => $linhas];
    }

    private function estoqueCritico(): array
    {
        $nomes = $this->nomesUnidades();
        $linhas = Suprimento::query()->whereColumn('quantidade_atual', '<', 'quantidade_minima')
            ->orderBy('unidade_id')->orderBy('nome')->get()
            ->map(fn ($s) => [$nomes[$s->unidade_id] ?? '-', $s->nome, $s->quantidade_atual, $s->quantidade_minima])->all();

        return ['titulo' => 'Suprimentos abaixo do mínimo', 'colunas' => ['Unidade', 'Suprimento', 'Atual', 'Mínimo'], 'linhas' => $linhas];
    }

    /** @return array{0: Carbon, 1: Carbon} período padrão: últimos 30 dias */
    private function periodo(array $filtros): array
    {
        $fuso = config('sghss.fuso_agenda');
        $ate = isset($filtros['ate']) ? Carbon::parse($filtros['ate'], $fuso)->endOfDay() : now($fuso)->endOfDay();
        $de = isset($filtros['de']) ? Carbon::parse($filtros['de'], $fuso)->startOfDay() : $ate->copy()->subDays(30)->startOfDay();

        if ($de->gt($ate)) {
            throw new RegraDeNegocioException('O período inicial é posterior ao final.');
        }

        return [$de, $ate];
    }

    /** @return array<int, string> */
    private function nomesUnidades(): array
    {
        return Unidade::query()->pluck('nome', 'id')->all();
    }
}

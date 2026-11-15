<?php

namespace App\Domains\Unidades\Services;

use App\Domains\Unidades\Models\Unidade;
use App\Shared\Enums\StatusUnidade;
use App\Shared\Exceptions\RegraDeNegocioException;
use Illuminate\Support\Facades\Cache;

class UnidadeService
{
    private const TTL = 300;

    /** Dados de referência em cache (RNF004), invalidado em toda escrita. */
    public function listar(?string $status): \Illuminate\Support\Collection
    {
        $linhas = Cache::remember('unidades:'.($status ?? 'todas'), self::TTL, function () use ($status) {
            return Unidade::query()
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderBy('nome')->get()
                ->map(fn (Unidade $u) => $u->getAttributes())->all();
        });

        return Unidade::hydrate($linhas);
    }

    public function criar(array $dados): Unidade
    {
        $unidade = new Unidade($dados);
        $unidade->save();
        $this->limparCache();

        return $unidade->refresh();
    }

    public function atualizar(Unidade $unidade, array $dados): Unidade
    {
        $unidade->update($dados);
        $this->limparCache();

        return $unidade->refresh();
    }

    public function alterarStatus(Unidade $unidade, StatusUnidade $novo, ?string $motivo): Unidade
    {
        $anterior = $unidade->status;
        $unidade->update(['status' => $novo]);
        $this->limparCache();

        activity('unidades')->performedOn($unidade)->causedBy(auth()->user())
            ->event('status_alterado')
            ->withProperties(['de' => $anterior?->value, 'para' => $novo->value, 'motivo' => $motivo])
            ->log('Status da unidade alterado');

        return $unidade->refresh();
    }

    /** Unidade que não está ativa não recebe agendamentos, transferências nem internações (CT011). */
    public static function exigirOperacional(Unidade $unidade): Unidade
    {
        if ($unidade->status !== StatusUnidade::Ativa) {
            throw new RegraDeNegocioException('A unidade não está operacional (status: '.$unidade->status->value.').');
        }

        return $unidade;
    }

    public function limparCache(): void
    {
        Cache::forget('unidades:todas');
        foreach (StatusUnidade::cases() as $caso) {
            Cache::forget('unidades:'.$caso->value);
        }
    }
}

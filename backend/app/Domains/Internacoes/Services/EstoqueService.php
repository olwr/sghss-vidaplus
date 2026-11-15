<?php

namespace App\Domains\Internacoes\Services;

use App\Domains\Internacoes\Models\Suprimento;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Exceptions\RegraDeNegocioException;
use Illuminate\Support\Facades\DB;

class EstoqueService
{
    public function criar(array $dados): Suprimento
    {
        $unidade = Unidade::query()->where('uuid', $dados['unidade_id'])->firstOrFail();
        $s = new Suprimento([...collect($dados)->except('unidade_id')->all(), 'unidade_id' => $unidade->id]);
        $s->save();

        return $s->refresh();
    }

    /** Entrada (+) ou saída (−) atômica: nunca deixa o estoque negativo, nem sob concorrência. */
    public function movimentar(Suprimento $suprimento, int $quantidade, ?string $motivo): Suprimento
    {
        return DB::transaction(function () use ($suprimento, $quantidade, $motivo) {
            $s = Suprimento::query()->lockForUpdate()->findOrFail($suprimento->id);
            $novo = $s->quantidade_atual + $quantidade;
            if ($novo < 0) {
                throw new RegraDeNegocioException("Estoque insuficiente (atual: {$s->quantidade_atual}).");
            }

            $estavaAbaixo = $s->abaixoDoMinimo();
            $s->update(['quantidade_atual' => $novo]);

            activity('suprimentos')->performedOn($s)->causedBy(auth()->user())->event('movimentacao')
                ->withProperties(['quantidade' => $quantidade, 'motivo' => $motivo])->log('Movimentação de estoque');

            if (! $estavaAbaixo && $s->abaixoDoMinimo()) {
                // alerta de estoque mínimo: fica no log e na listagem (?abaixo_do_minimo=1)
                activity('suprimentos')->performedOn($s)->event('abaixo_do_minimo')
                    ->withProperties(['atual' => $s->quantidade_atual, 'minimo' => $s->quantidade_minima])
                    ->log('Estoque abaixo do mínimo');
            }

            return $s->refresh();
        });
    }
}

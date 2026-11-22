<?php

namespace App\Domains\Internacoes\Http\Controllers;

use App\Domains\Internacoes\Http\Requests\LeitoRequest;
use App\Domains\Internacoes\Http\Resources\LeitoResource;
use App\Domains\Internacoes\Models\Leito;
use App\Domains\Unidades\Models\Unidade;
use App\Http\Controllers\Controller;
use App\Shared\Exceptions\RegraDeNegocioException;
use App\Shared\Enums\StatusLeito;
use App\Shared\Http\Consulta;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeitoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $consulta = Leito::query()->with('unidade')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('tipo'), fn ($q, $t) => $q->where('tipo', $t))
            ->when($request->query('unidade'), fn ($q, $u) => $q->whereHas('unidade', fn ($x) => $x->where('uuid', $u)));

        return LeitoResource::collection(
            Consulta::ordenar($consulta, $request, ['id', 'codigo', 'status'], 'codigo')->paginate(Consulta::porPagina($request))
        );
    }

    public function store(LeitoRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $unidade = Unidade::query()->where('uuid', $dados['unidade_id'])->firstOrFail();
        $leito = new Leito([...collect($dados)->except('unidade_id')->all(), 'unidade_id' => $unidade->id]);
        $leito->save();

        return Resposta::criado(LeitoResource::make($leito->refresh()), url("/api/v1/leitos/{$leito->uuid}"));
    }

    public function update(LeitoRequest $request, Leito $leito): LeitoResource
    {
        if ($leito->status === StatusLeito::Ocupado) {
            throw new RegraDeNegocioException('Leito ocupado não pode ser alterado; dê alta ao paciente antes.');
        }
        $dados = $request->validated();
        // `status` não é mass-assignable (só o serviço de internação ocupa o leito); aqui só manutenção/disponível
        $leito->fill(collect($dados)->except('status')->all());
        if (isset($dados['status'])) {
            $leito->forceFill(['status' => $dados['status']]);
        }
        $leito->save();

        return LeitoResource::make($leito->refresh());
    }
}

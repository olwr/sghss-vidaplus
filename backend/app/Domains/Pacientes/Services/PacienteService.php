<?php

namespace App\Domains\Pacientes\Services;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Seguranca\Services\ConsentimentoService;
use App\Domains\Unidades\Models\Unidade;
use App\Domains\Unidades\Services\UnidadeService;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Support\BlindIndex;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Shared\Http\Consulta;

class PacienteService
{
    public function __construct(private readonly ConsentimentoService $consentimentos)
    {
    }

    public function listar(Request $request): LengthAwarePaginator
    {
        $consulta = Paciente::query()
            ->when($request->query('nome'), fn ($q, $nome) => $q->where('nome', 'ilike', '%'.addcslashes((string) $nome, '%_\\').'%'))
            // o CPF não é pesquisável em claro: a busca usa o blind index (HMAC)
            ->when($request->query('cpf'), fn ($q, $cpf) => $q->where('cpf_hash', BlindIndex::cpf(preg_replace('/\D/', '', (string) $cpf))))
            ->when($request->query('unidade'), fn ($q, $uuid) => $q->whereHas('unidade', fn ($u) => $u->where('uuid', $uuid)));

        return Consulta::ordenar($consulta, $request, ['id', 'nome', 'created_at'], 'nome')
            ->paginate(Consulta::porPagina($request));
    }

    public function cadastrar(array $dados, Usuario $autor, ?string $ip): Paciente
    {
        $unidade = Unidade::query()->where('uuid', $dados['unidade_id'])->firstOrFail();

        // usuário de unidade só cadastra na própria unidade; usuário de rede (unidade_id nulo) em qualquer uma
        if ($autor->unidade_id !== null && $autor->unidade_id !== $unidade->id) {
            throw new AuthorizationException('Você só pode cadastrar pacientes na sua unidade.');
        }
        UnidadeService::exigirOperacional($unidade);

        return DB::transaction(function () use ($dados, $unidade, $ip) {
            if (Paciente::withoutGlobalScopes()->porCpf($dados['cpf'])->exists()) {
                throw new ConflictException('Já existe paciente com este CPF.');
            }

            $paciente = Paciente::create([...collect($dados)->except(['unidade_id', 'consentimento'])->all(), 'unidade_id' => $unidade->id]);
            $this->consentimentos->conceder($paciente, 'atendimento_saude', $ip);

            return $paciente->load('unidade');
        });
    }

    public function atualizar(Paciente $paciente, array $dados): Paciente
    {
        $paciente->update($dados);

        return $paciente->refresh()->load('unidade');
    }

    public function excluir(Paciente $paciente): void
    {
        $paciente->delete(); // soft delete; a anonimização é feita pelo job de retenção
    }
}

<?php

namespace App\Domains\Profissionais\Services;

use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Unidades\Models\Unidade;
use App\Domains\Unidades\Services\UnidadeService;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Support\BlindIndex;
use Illuminate\Support\Facades\DB;

class ProfissionalService
{
    /** Cria o Usuario (perfil profissional_saude) e o Profissional na mesma transação. */
    public function cadastrar(array $dados): Profissional
    {
        return DB::transaction(function () use ($dados) {
            if (Profissional::withoutGlobalScopes()->where('cpf_hash', BlindIndex::cpf($dados['cpf']))->exists()) {
                throw new ConflictException('Já existe profissional com este CPF.');
            }
            $unidade = UnidadeService::exigirOperacional(
                Unidade::query()->where('uuid', $dados['unidade_id'])->firstOrFail()
            );

            $usuario = Usuario::create([
                'unidade_id' => $unidade->id, 'nome' => $dados['nome'], 'email' => $dados['email'],
                'password' => $dados['password'], 'ativo' => true,
            ]);
            $usuario->assignRole('profissional_saude');

            $profissional = Profissional::create([
                'usuario_id' => $usuario->id, 'unidade_id' => $unidade->id,
                'tipo' => $dados['tipo'], 'especialidade' => $dados['especialidade'] ?? null,
                'conselho_sigla' => $dados['conselho_sigla'], 'conselho_numero' => $dados['conselho_numero'],
                'conselho_uf' => strtoupper($dados['conselho_uf']), 'cpf' => $dados['cpf'], 'ativo' => true,
            ]);

            return $profissional->load('usuario', 'unidade');
        });
    }

    public function desativar(Profissional $profissional): void
    {
        DB::transaction(function () use ($profissional) {
            $profissional->update(['ativo' => false]);
            $profissional->usuario->update(['ativo' => false]);
            $profissional->usuario->tokens()->delete();        // revoga as sessões (CT015)
        });
    }
}

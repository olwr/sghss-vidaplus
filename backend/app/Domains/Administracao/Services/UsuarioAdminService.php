<?php

namespace App\Domains\Administracao\Services;

use App\Domains\Seguranca\Models\Usuario;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Exceptions\RegraDeNegocioException;
use Illuminate\Support\Facades\DB;

class UsuarioAdminService
{
    public function atualizar(Usuario $alvo, array $dados, Usuario $admin): Usuario
    {
        if ($alvo->is($admin) && (($dados['ativo'] ?? true) === false || (isset($dados['perfil']) && $dados['perfil'] !== 'administrador'))) {
            throw new RegraDeNegocioException('Você não pode desativar nem rebaixar o próprio usuário.');
        }

        return DB::transaction(function () use ($alvo, $dados) {
            if (array_key_exists('unidade_id', $dados)) {
                $alvo->unidade_id = $dados['unidade_id'] ? Unidade::query()->where('uuid', $dados['unidade_id'])->firstOrFail()->id : null;
            }
            if (isset($dados['ativo'])) {
                $alvo->ativo = $dados['ativo'];
            }
            $alvo->save();

            if (isset($dados['perfil'])) {
                $alvo->syncRoles([$dados['perfil']]);
            }
            if (($dados['ativo'] ?? true) === false || isset($dados['perfil'])) {
                $alvo->tokens()->delete();           // desativou ou mudou o perfil: derruba as sessões abertas (CT015)
            }

            return $alvo->refresh();
        });
    }

    public function revogarSessoes(Usuario $alvo): void
    {
        $alvo->tokens()->delete();
        activity('auth')->performedOn($alvo)->causedBy(auth()->user())->event('sessoes_revogadas')->log('Sessões revogadas');
    }
}

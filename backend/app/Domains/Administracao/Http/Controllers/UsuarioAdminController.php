<?php

namespace App\Domains\Administracao\Http\Controllers;

use App\Domains\Administracao\Http\Requests\UsuarioAdminRequest;
use App\Domains\Administracao\Services\UsuarioAdminService;
use App\Domains\Seguranca\Http\Resources\UsuarioResource;
use App\Domains\Seguranca\Models\Usuario;
use App\Http\Controllers\Controller;
use App\Shared\Http\Consulta;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Role;

class UsuarioAdminController extends Controller
{
    public function __construct(private readonly UsuarioAdminService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $consulta = Usuario::query()->with('unidade')
            ->when($request->query('perfil'), fn ($q, $p) => $q->role($p))
            ->when($request->has('ativo'), fn ($q) => $q->where('ativo', $request->boolean('ativo')))
            ->when($request->query('busca'), fn ($q, $b) => $q->where(fn ($w) => $w
                ->where('nome', 'ilike', '%'.addcslashes((string) $b, '%_\\').'%')
                ->orWhere('email', 'ilike', '%'.addcslashes((string) $b, '%_\\').'%')));

        return UsuarioResource::collection(Consulta::ordenar($consulta, $request, ['id', 'nome', 'email'], 'nome')->paginate(Consulta::porPagina($request)));
    }

    public function show(Usuario $usuario): UsuarioResource
    {
        return UsuarioResource::make($usuario->load('unidade'));
    }

    public function update(UsuarioAdminRequest $request, Usuario $usuario): UsuarioResource
    {
        return UsuarioResource::make($this->service->atualizar($usuario, $request->validated(), $request->user())->load('unidade'));
    }

    public function revogarSessoes(Usuario $usuario): JsonResponse
    {
        $this->service->revogarSessoes($usuario);

        return Resposta::semConteudo();
    }

    /** Perfis e suas permissões (a matriz publicada no PDF). */
    public function perfis(): JsonResponse
    {
        $perfis = Role::query()->with('permissions')->orderBy('name')->get()
            ->map(fn ($r) => ['perfil' => $r->name, 'permissoes' => $r->permissions->pluck('name')->sort()->values()]);

        return response()->json(['data' => $perfis]);
    }
}

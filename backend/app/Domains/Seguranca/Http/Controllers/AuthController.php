<?php

namespace App\Domains\Seguranca\Http\Controllers;

use App\Domains\Seguranca\Http\Requests\LoginRequest;
use App\Domains\Seguranca\Http\Requests\RegistroRequest;
use App\Domains\Seguranca\Http\Resources\UsuarioResource;
use App\Domains\Seguranca\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Resposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthService $service): JsonResponse
    {
        return response()->json($service->autenticar($request->validated('email'), $request->validated('password')));
    }

    public function registro(RegistroRequest $request, AuthService $service): JsonResponse
    {
        $usuario = $service->registrar($request->validated(), $request->ip());

        return Resposta::criado(
            UsuarioResource::make($usuario->load('unidade')),
            url('/api/v1/auth/me'),
        );
    }

    /** Lista pública mínima (id e nome das unidades ativas) para o formulário de cadastro do paciente. */
    public function unidades(): JsonResponse
    {
        $itens = \App\Domains\Unidades\Models\Unidade::query()
            ->where('status', \App\Shared\Enums\StatusUnidade::Ativa->value)->orderBy('nome')->get(['uuid', 'nome', 'cidade', 'uf'])
            ->map(fn ($u) => ['id' => $u->uuid, 'nome' => $u->nome, 'cidade' => $u->cidade, 'uf' => $u->uf]);

        return response()->json(['data' => $itens]);
    }

    public function logout(Request $request, AuthService $service): JsonResponse
    {
        $service->sair($request->user());

        return Resposta::semConteudo();
    }

    public function me(Request $request): UsuarioResource
    {
        return UsuarioResource::make($request->user()->load('unidade'));
    }
}

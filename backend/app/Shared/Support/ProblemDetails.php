<?php

namespace App\Shared\Support;

use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\RegraDeNegocioException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ProblemDetails
{
    public static function from(Throwable $e, Request $request): JsonResponse
    {
        $extra = [];
        $sqlState = $e instanceof QueryException ? ($e->errorInfo[0] ?? null) : null;

        [$status, $titulo] = match (true) {
            $e instanceof ValidationException => [422, 'Dados inválidos'],
            $e instanceof RegraDeNegocioException => [422, 'Regra de negócio violada'],
            $e instanceof ConflictException => [409, 'Conflito'],
            $sqlState === '23P01', $sqlState === '23505' => [409, 'Conflito de dados'],
            $e instanceof AuthenticationException => [401, 'Não autenticado'],
            $e instanceof AccessDeniedHttpException => [403, 'Acesso negado'],
            $e instanceof NotFoundHttpException => [404, 'Recurso não encontrado'],
            $e instanceof ThrottleRequestsException => [429, 'Muitas requisições'],
            $e instanceof HttpExceptionInterface => [$e->getStatusCode(), 'Erro na requisição'],
            default => [500, 'Erro interno'],
        };

        if ($e instanceof ValidationException) {
            $extra['errors'] = $e->errors();
        }

        $detalhe = match (true) {
            $status >= 500 => 'Ocorreu um erro inesperado.', // nunca vazar detalhes internos
            $e instanceof ValidationException => 'Um ou mais campos são inválidos.',
            $sqlState !== null => 'A operação conflita com dados existentes.',
            default => $e->getMessage() ?: $titulo,
        };

        return response()->json([
            'type' => 'about:blank',
            'title' => $titulo,
            'status' => $status,
            'detail' => $detalhe,
            'instance' => $request->path(),
        ] + $extra, $status, ['Content-Type' => 'application/problem+json']);
    }
}

<?php

namespace App\Shared\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

final class Resposta
{
    /** 201 + Location (regra transversal 9). */
    public static function criado(JsonResource $recurso, string $location): JsonResponse
    {
        return $recurso->response()->setStatusCode(201)->header('Location', $location);
    }

    public static function semConteudo(): JsonResponse
    {
        return response()->json(null, 204);
    }
}

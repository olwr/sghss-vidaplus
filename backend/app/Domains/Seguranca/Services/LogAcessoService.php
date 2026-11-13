<?php

namespace App\Domains\Seguranca\Services;

use App\Domains\Seguranca\Models\LogAcesso;
use App\Shared\Enums\AcaoAcesso;
use Illuminate\Database\Eloquent\Model;

class LogAcessoService
{
    public function registrar(AcaoAcesso $acao, string $recursoTipo, Model $recurso, ?int $pacienteId = null): void
    {
        LogAcesso::create([
            'usuario_id' => auth()->id(),
            'unidade_id' => auth()->user()?->unidade_id,
            'paciente_id' => $pacienteId,
            'acao' => $acao,
            'recurso_tipo' => $recursoTipo,           // texto livre do DBML: 'prontuario', 'resultado_exame', 'exportacao'...
            'recurso_id' => $recurso->getKey(),
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'rota' => request()->path(),
        ]);
    }
}

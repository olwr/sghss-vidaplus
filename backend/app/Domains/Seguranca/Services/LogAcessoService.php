<?php

// namespace App\Domains\Seguranca\Services;

// use App\Domains\Seguranca\Models\LogAcesso;
// use Illuminate\Database\Eloquent\Model;

// class LogAcessoService
// {
//     public function registrar(string $acao, string $recursoTipo, Model $recurso, ?int $pacienteId = null): void
//     {
//         LogAcesso::create([
//             'usuario_id' => auth()->id(),
//             'unidade_id' => auth()->user()?->unidade_id,
//             'paciente_id' => $pacienteId,
//             'acao' => $acao, // leitura | exportacao | download
//             'recurso_tipo' => $recursoTipo,   // texto livre do DBML: 'prontuario', 'resultado_exame', 'exportacao'...
//             'recurso_id' => $recurso->getKey(),
//             'ip' => request()->ip(),
//             'user_agent' => substr((string) request()->userAgent(), 0, 255),
//             'rota' => request()->path(),
//         ]);
//     }
// }
// comentado pois referencia classes que ainda não existem

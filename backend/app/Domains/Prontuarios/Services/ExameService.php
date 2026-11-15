<?php

namespace App\Domains\Prontuarios\Services;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Prontuarios\Models\Exame;
use App\Domains\Prontuarios\Models\ResultadoExame;
use App\Domains\Seguranca\Models\Usuario;
use App\Shared\Enums\StatusExame as S;
use App\Shared\Exceptions\RegraDeNegocioException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExameService
{
    private const TRANSICOES = [
        'solicitado' => ['agendado', 'realizado', 'cancelado'],
        'agendado' => ['realizado', 'cancelado'],
        'realizado' => ['cancelado'],
        'resultado_disponivel' => [], 'cancelado' => [],
    ];

    public function solicitar(Paciente $paciente, array $dados, Usuario $autor): Exame
    {
        $profissional = $autor->profissional ?? throw new RegraDeNegocioException('Usuário sem cadastro de profissional.');

        $exame = new Exame([
            'paciente_id' => $paciente->id, 'solicitante_id' => $profissional->id, 'unidade_id' => $profissional->unidade_id,
            'nome' => $dados['nome'], 'instrucoes' => $dados['instrucoes'] ?? null, 'valor' => $dados['valor'] ?? null,
        ]);
        $exame->save();

        return $exame->refresh();
    }

    public function mudarStatus(Exame $exame, S $para): Exame
    {
        if (! in_array($para->value, self::TRANSICOES[$exame->status->value], true)) {
            throw new RegraDeNegocioException("Não é possível passar de '{$exame->status->value}' para '{$para->value}'.");
        }
        $exame->forceFill(['status' => $para, 'realizado_em' => $para === S::Realizado ? now() : $exame->realizado_em])->save();

        return $exame->refresh();
    }

    public function registrarResultado(Exame $exame, array $dados, ?UploadedFile $arquivo, Usuario $autor): ResultadoExame
    {
        $profissional = $autor->profissional ?? throw new RegraDeNegocioException('Usuário sem cadastro de profissional.');
        if ($exame->status === S::Cancelado) {
            throw new RegraDeNegocioException('Exame cancelado não recebe resultado.');
        }

        return DB::transaction(function () use ($exame, $dados, $arquivo, $profissional) {
            $campos = [];
            if ($arquivo) {
                // nome aleatório e extensão derivada do conteúdo: o nome enviado pelo cliente nunca vira caminho
                $ext = $arquivo->guessExtension() ?: 'bin';
                $caminho = 'exames/'.$exame->uuid.'/'.Str::uuid().'.'.$ext;
                Storage::disk(ReceitaService::DISCO)->putFileAs(dirname($caminho), $arquivo, basename($caminho));
                $campos = [
                    'arquivo_path' => $caminho,
                    'arquivo_nome_original' => Str::limit(preg_replace('/[^\w.\- ]/u', '_', $arquivo->getClientOriginalName()), 200, ''),
                    'arquivo_mime' => $arquivo->getMimeType(),
                    'arquivo_tamanho' => $arquivo->getSize(),
                ];
            }

            $resultado = new ResultadoExame($campos + [
                'exame_id' => $exame->id, 'registrado_por_id' => $profissional->id,
                'resumo_criptografado' => $dados['resumo'] ?? null, 'disponibilizado_em' => now(),
            ]);
            $resultado->save();

            $exame->forceFill(['status' => S::ResultadoDisponivel, 'realizado_em' => $exame->realizado_em ?? now()])->save();

            return $resultado->refresh();
        });
    }

    /** O resultado mais recente que já foi disponibilizado ao paciente. */
    public function resultadoAtual(Exame $exame): ?ResultadoExame
    {
        return $exame->resultados()->whereNotNull('disponibilizado_em')->latest('disponibilizado_em')->first();
    }
}

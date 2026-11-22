<?php

namespace App\Domains\Prontuarios\Services;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Prontuarios\Models\Receita;
use App\Domains\Seguranca\Models\Usuario;
use App\Shared\Enums\StatusReceita;
use App\Shared\Exceptions\RegraDeNegocioException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReceitaService
{
    public const DISCO = 's3';   // bucket privado (RustFS): nunca disco público

    public function emitir(Paciente $paciente, array $dados, Usuario $autor): Receita
    {
        $profissional = $autor->profissional ?? throw new RegraDeNegocioException('Usuário sem cadastro de profissional.');

        return DB::transaction(function () use ($paciente, $dados, $profissional) {
            $agendamentoId = ! empty($dados['agendamento_id'])
                ? Agendamento::withoutGlobalScopes()->where('uuid', $dados['agendamento_id'])
                    ->where('paciente_id', $paciente->id)->firstOrFail()->id
                : null;

            $receita = new Receita([
                'paciente_id' => $paciente->id, 'profissional_id' => $profissional->id,
                'unidade_id' => $profissional->unidade_id, 'agendamento_id' => $agendamentoId, 'emitida_em' => now(),
            ]);
            $receita->save();
            $receita->itens()->createMany($dados['itens']);

            $receita->load(['itens', 'paciente', 'profissional.usuario', 'unidade']);
            $caminho = 'receitas/'.$receita->unidade->uuid.'/'.$receita->uuid.'.pdf';
            Storage::disk(self::DISCO)->put($caminho, $this->gerarPdf($receita));
            $receita->forceFill(['arquivo_path' => $caminho])->save();

            return $receita;
        });
    }

    public function cancelar(Receita $receita): Receita
    {
        if ($receita->status === StatusReceita::Cancelada) {
            throw new RegraDeNegocioException('A receita já está cancelada.');
        }
        $receita->forceFill(['status' => StatusReceita::Cancelada, 'cancelada_em' => now()])->save();

        return $receita->refresh();
    }

    /** Conteúdo do PDF: usa o arquivo guardado e, se faltar (ex.: troca de disco), gera de novo. */
    public function pdf(Receita $receita): string
    {
        $disco = Storage::disk(self::DISCO);
        if ($receita->arquivo_path && $disco->exists($receita->arquivo_path)) {
            return $disco->get($receita->arquivo_path);
        }

        return $this->gerarPdf($receita->load(['itens', 'paciente', 'profissional.usuario', 'unidade']));
    }

    private function gerarPdf(Receita $receita): string
    {
        return Pdf::loadView('pdf.receita', ['receita' => $receita])->setPaper('a4')->output();
    }
}

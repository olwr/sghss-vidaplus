<?php

namespace App\Domains\Administracao\Services;

use App\Domains\Pacientes\Models\Paciente;
use App\Shared\Exceptions\RegraDeNegocioException;
use Illuminate\Support\Facades\DB;

/**
 * Anonimização (LGPD art. 16/18) respeitando a guarda legal do prontuário (config sghss.retencao_prontuario_anos).
 * Remove a identificação do titular; o conteúdo clínico permanece, já desvinculado de pessoa identificável.
 */
class AnonimizacaoService
{
    public function anonimizar(Paciente $paciente): Paciente
    {
        if ($paciente->anonimizado_em !== null) {
            throw new RegraDeNegocioException('O paciente já foi anonimizado.');
        }

        $anos = (int) config('sghss.retencao_prontuario_anos');
        $ultimo = $paciente->prontuario?->registros()->max('registrado_em');
        if ($ultimo && now()->subYears($anos)->lt($ultimo)) {
            throw new RegraDeNegocioException("Prontuário sob guarda legal por {$anos} anos desde o último registro.");
        }

        return DB::transaction(function () use ($paciente) {
            $paciente->forceFill([
                'nome' => 'ANONIMIZADO',
                'cpf_criptografado' => 'ANONIMIZADO',
                'cpf_hash' => hash('sha256', 'anon:'.$paciente->uuid),   // mantém a unicidade sem ser reversível ao CPF
                'email' => null, 'telefone' => null, 'cep' => null, 'logradouro' => null, 'numero' => null,
                'complemento' => null, 'bairro' => null, 'cidade' => null, 'uf' => null,
                'preferencias_acessibilidade' => null, 'anonimizado_em' => now(),
            ])->save();

            // sem identificação, o acesso do titular deixa de existir
            $paciente->usuario?->forceFill(['ativo' => false, 'nome' => 'ANONIMIZADO', 'email' => 'anon+'.$paciente->uuid.'@invalido.local', 'telefone' => null])->save();
            $paciente->usuario?->tokens()->delete();
            $paciente->consentimentos()->whereNull('revogado_em')->update(['revogado_em' => now()]);

            activity('lgpd')->performedOn($paciente)->causedBy(auth()->user())->event('anonimizado')->log('Paciente anonimizado');

            return $paciente->refresh();
        });
    }
}

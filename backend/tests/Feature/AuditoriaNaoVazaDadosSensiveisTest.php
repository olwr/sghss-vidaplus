<?php

namespace Tests\Feature;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Prontuarios\Models\RegistroClinico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Evidência do RNF010/LGPD: o log registra QUE mudou, nunca o valor sensível. */
class AuditoriaNaoVazaDadosSensiveisTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_de_paciente_nao_contem_cpf_exposto_nem_cifrado(): void
    {
        $paciente = Paciente::factory()->create(['cpf' => '529.982.247-25', 'nome' => 'Fulano de Tal']);
        $paciente->update(['nome' => 'Fuluano de Tal Junior']);

        $linhas = DB::table('activity_log')
            ->where('subject_type', $paciente->getMorphClass())
            ->where('subject_id', $paciente->id)
            ->get();

        $this->assertGreaterThanOrEqual(2, $linhas->count()); // created + updated

        $json = json_encode($linhas);
        $this->assertStringNotContainsString('52998224725', $json);
        $this->assertStringNotContainsString((string) DB::table('pacientes')->value('cpf_criptografado'), $json);
        $this->assertStringNotContainsString((string) DB::table('pacientes')->value('cpf_hash'), $json);
        $this->assertStringNotContainsString('Fulano de Tal Junior', $json); // campos comuns continuam auditados
    }

    public function test_log_de_registro_clinico_nao_contem_texto_clinico(): void
    {
        $registro = RegistroClinico::factory()->create(['diagnostico_criptografado' => 'DIAGNOSTICO-SECRETO-XYZ']);

        $json = json_encode(DB::table('activity_log')->where('subject_id', $registro->id)->get());

        $this->assertStringNotContainsString('DIAGNOSTICO-SECRETO-XYZ', $json);
    }
}

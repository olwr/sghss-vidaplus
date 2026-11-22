<?php

namespace Tests\Feature;

use App\Domains\Pacientes\Models\Paciente;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CpfProtegidoTest extends TestCase
{
    use RefreshDatabase;

    private const CPF = '529.982.247-25';

    public function test_cpf_nunca_fica_exposto_no_banco(): void
    {
        $paciente = Paciente::factory()->create(['cpf' => self::CPF]);

        $bruto = (string) DB::table('pacientes')->where('id', $paciente->id)->value('cpf_criptografado');

        $this->assertStringNotContainsString('52998224725', $bruto);
        $this->assertStringNotContainsString(self::CPF, $bruto);
        $this->assertSame('52998224725', $paciente->fresh()->cpf);   // o model descriptografa
    }

    public function test_busca_por_cpf_usa_o_blind_index(): void
    {
        Paciente::factory()->create(['cpf' => self::CPF]);

        $this->assertTrue(Paciente::porCpf(self::CPF)->exists());
        $this->assertTrue(Paciente::porCpf('52998224725')->exists());   // com ou sem máscara
        $this->assertFalse(Paciente::porCpf('111.444.777-35')->exists());
    }

    public function test_campos_sensiveis_nao_saem_na_serializacao(): void
    {
        $dados = Paciente::factory()->create(['cpf' => self::CPF])->toArray();

        $this->assertArrayNotHasKey('cpf_criptografado', $dados);
        $this->assertArrayNotHasKey('cpf_hash', $dados);
    }

    public function test_cpf_duplicado_viola_a_unicidade(): void
    {
        Paciente::factory()->create(['cpf' => self::CPF]);

        $this->expectException(QueryException::class);
        Paciente::factory()->create(['cpf' => '529.982.247-25']);
    }
}

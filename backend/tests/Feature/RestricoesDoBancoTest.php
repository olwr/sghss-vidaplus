<?php

namespace Tests\Feature;

use App\Domains\Agendamentos\Models\Agendamento;
use App\Domains\Internacoes\Models\Internacao;
use App\Domains\Internacoes\Models\Leito;
use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Profissionais\Models\Profissional;
use App\Domains\Seguranca\Models\LogAcesso;
use App\Domains\Transferencias\Models\Transferencia;
use App\Domains\Unidades\Models\Unidade;
use App\Shared\Enums\StatusAgendamento;
use App\Shared\Enums\StatusInternacao;
use App\Shared\Enums\TipoTransferencia;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Evidência das garantias "no banco" (integridade que não depende do código da aplicação). */
class RestricoesDoBancoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Executa em savepoint e devolve o SQLSTATE do erro (ou null). Necessário porque, no PostgreSQL,
     * uma falha aborta a transação do teste e invalidaria as consultas seguintes.
     */
    private function sqlstate(callable $acao): ?string
    {
        try {
            DB::transaction($acao);
        } catch (QueryException $e) {
            return $e->errorInfo[0] ?? null;
        }

        return null;
    }

    public function test_exclusion_constraint_impede_agendamentos_sobrepostos_do_mesmo_profissional(): void
    {
        $profissional = Profissional::factory()->create();
        $inicio = now()->addDay()->setTime(10, 0);
        Agendamento::factory()->em($inicio)->create(['profissional_id' => $profissional->id]);

        $estado = $this->sqlstate(fn () => Agendamento::factory()
            ->em($inicio->copy()->addMinutes(15))
            ->create(['profissional_id' => $profissional->id]));

        $this->assertSame('23P01', $estado);   // exclusion_violation
    }

    public function test_horarios_adjacentes_sao_permitidos(): void
    {
        $profissional = Profissional::factory()->create();
        $inicio = now()->addDay()->setTime(10, 0);
        Agendamento::factory()->em($inicio)->create(['profissional_id' => $profissional->id]);

        $estado = $this->sqlstate(fn () => Agendamento::factory()
            ->em($inicio->copy()->addMinutes(30))
            ->create(['profissional_id' => $profissional->id]));

        $this->assertNull($estado);
    }

    public function test_agendamento_cancelado_libera_o_horario(): void
    {
        $profissional = Profissional::factory()->create();
        $inicio = now()->addDay()->setTime(10, 0);
        Agendamento::factory()->em($inicio)->status(StatusAgendamento::Cancelado)
            ->create(['profissional_id' => $profissional->id]);

        $estado = $this->sqlstate(fn () => Agendamento::factory()->em($inicio)
            ->create(['profissional_id' => $profissional->id]));

        $this->assertNull($estado);
    }

    public function test_fim_deve_ser_posterior_ao_inicio(): void
    {
        $inicio = now()->addDay()->setTime(10, 0);

        $estado = $this->sqlstate(fn () => Agendamento::factory()->state([
            'inicio_em' => $inicio,
            'fim_em' => $inicio->copy()->subMinutes(5),
        ])->create());

        $this->assertSame('23514', $estado);   // check_violation
    }

    public function test_leito_nao_pode_ter_duas_internacoes_ativas(): void
    {
        $leito = Leito::factory()->create();
        Internacao::factory()->create(['leito_id' => $leito->id, 'status' => StatusInternacao::Ativa]);

        $estado = $this->sqlstate(fn () => Internacao::factory()
            ->create(['leito_id' => $leito->id, 'status' => StatusInternacao::Ativa]));

        $this->assertSame('23505', $estado);   // unique_violation (índice parcial)
    }

    public function test_paciente_nao_pode_ter_duas_internacoes_ativas(): void
    {
        $paciente = Paciente::factory()->create();
        Internacao::factory()->create(['paciente_id' => $paciente->id, 'status' => StatusInternacao::Ativa]);

        $estado = $this->sqlstate(fn () => Internacao::factory()
            ->create(['paciente_id' => $paciente->id, 'status' => StatusInternacao::Ativa]));

        $this->assertSame('23505', $estado);
    }

    public function test_internacao_encerrada_nao_bloqueia_nova_internacao(): void
    {
        $paciente = Paciente::factory()->create();
        Internacao::factory()->create(['paciente_id' => $paciente->id, 'status' => StatusInternacao::Alta]);

        $estado = $this->sqlstate(fn () => Internacao::factory()
            ->create(['paciente_id' => $paciente->id, 'status' => StatusInternacao::Ativa]));

        $this->assertNull($estado);
    }

    public function test_transferencia_de_paciente_nao_aceita_profissional(): void
    {
        $profissional = Profissional::factory()->create();

        $estado = $this->sqlstate(fn () => Transferencia::factory()->create([
            'tipo' => TipoTransferencia::Paciente,
            'profissional_id' => $profissional->id,   // paciente_id também preenchido pela factory
        ]));

        $this->assertSame('23514', $estado);
    }

    public function test_transferencia_exige_unidades_diferentes(): void
    {
        $unidade = Unidade::factory()->create();
        $paciente = Paciente::factory()->create(['unidade_id' => $unidade->id]);

        $estado = $this->sqlstate(fn () => Transferencia::factory()->create([
            'paciente_id' => $paciente->id,
            'unidade_origem_id' => $unidade->id,
            'unidade_destino_id' => $unidade->id,
        ]));

        $this->assertSame('23514', $estado);
    }

    public function test_tabelas_de_auditoria_sao_append_only(): void
    {
        LogAcesso::factory()->create();   // INSERT continua permitido

        $this->assertSame('42501', $this->sqlstate(fn () => DB::table('logs_acesso')->update(['rota' => 'x'])));
        $this->assertSame('42501', $this->sqlstate(fn () => DB::table('logs_acesso')->delete()));
        $this->assertSame('42501', $this->sqlstate(fn () => DB::table('activity_log')->delete()));
    }
}

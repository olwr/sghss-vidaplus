<?php

namespace Tests\Feature\Api;

use App\Domains\Pacientes\Models\Paciente;
use App\Domains\Prontuarios\Models\Exame;
use App\Domains\Prontuarios\Models\Prontuario;
use App\Domains\Prontuarios\Models\Receita;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ComApi;
use Tests\TestCase;

class ProntuariosTest extends TestCase
{
    use ComApi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararApi();
    }

    public function test_profissional_cria_prontuario_e_secretario_nao_acessa(): void
    {
        $prof = $this->profissional();
        $pac = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);

        $this->como($this->secretario())->postJson('/api/v1/prontuarios', ['paciente_id' => $pac->uuid])->assertForbidden();   // CT003
        $this->como($this->admin())->postJson('/api/v1/prontuarios', ['paciente_id' => $pac->uuid])->assertForbidden();
        $this->como($prof->usuario)->postJson('/api/v1/prontuarios', ['paciente_id' => $pac->uuid, 'tipo_sanguineo' => 'O+'])->assertCreated();
        $this->como($prof->usuario)->postJson('/api/v1/prontuarios', ['paciente_id' => $pac->uuid])->assertStatus(409);       // um por paciente
    }

    public function test_leitura_do_prontuario_gera_log_de_acesso(): void
    {
        $prof = $this->profissional();
        $pac = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        Prontuario::factory()->create(['paciente_id' => $pac->id]);

        $this->como($prof->usuario)->getJson("/api/v1/pacientes/{$pac->uuid}/prontuario")->assertOk();

        $this->assertDatabaseHas('logs_acesso', ['usuario_id' => $prof->usuario_id, 'paciente_id' => $pac->id, 'acao' => 'leitura', 'recurso_tipo' => 'prontuario']);
    }

    public function test_paciente_le_o_proprio_prontuario_mas_nao_o_de_outro(): void
    {
        $eu = $this->pacienteComAcesso();
        $outro = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        Prontuario::factory()->create(['paciente_id' => $eu->id]);
        Prontuario::factory()->create(['paciente_id' => $outro->id]);

        $this->como($eu->usuario)->getJson("/api/v1/pacientes/{$eu->uuid}/prontuario")->assertOk();
        $this->como($eu->usuario)->getJson("/api/v1/pacientes/{$outro->uuid}/prontuario")->assertForbidden();
    }

    public function test_registro_clinico_fica_cifrado_no_banco(): void
    {
        $prof = $this->profissional();
        $pac = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        $pront = Prontuario::factory()->create(['paciente_id' => $pac->id]);

        $this->como($prof->usuario)->postJson("/api/v1/prontuarios/{$pront->uuid}/registros", [
            'queixa_principal' => 'Dor torácica', 'diagnostico' => 'Hipertensão arterial', 'conduta' => 'Losartana 50 mg',
        ])->assertCreated();

        $cru = json_encode(DB::table('registros_clinicos')->first());
        $this->assertStringNotContainsString('Hipertensão', $cru);
        $this->assertStringNotContainsString('Losartana', $cru);
        $this->como($prof->usuario)->getJson("/api/v1/pacientes/{$pac->uuid}/prontuario")->assertJsonPath('data.registros.0.diagnostico', 'Hipertensão arterial');
    }

    public function test_alterar_prontuario_audita_os_campos_sem_vazar_o_conteudo(): void
    {
        $prof = $this->profissional();
        $pront = Prontuario::factory()->create(['paciente_id' => Paciente::factory()->create(['unidade_id' => $this->unidade->id])->id]);

        $this->como($prof->usuario)->patchJson("/api/v1/prontuarios/{$pront->uuid}", ['alergias' => 'Dipirona grave'])->assertOk();   // CT025

        $this->assertDatabaseHas('activity_log', ['log_name' => 'prontuarios', 'event' => 'conteudo_alterado']);
        $this->assertStringNotContainsString('Dipirona', json_encode(DB::table('activity_log')->get()));
    }

    public function test_receita_e_emitida_guardada_e_baixada_em_pdf(): void
    {
        $prof = $this->profissional();
        $pac = $this->pacienteComAcesso();

        $r = $this->como($prof->usuario)->postJson('/api/v1/receitas', [
            'paciente_id' => $pac->uuid, 'itens' => [['medicamento' => 'Amoxicilina 500 mg', 'posologia' => '8/8 h', 'duracao' => '7 dias', 'quantidade' => 21]],
        ]);
        $r->assertCreated()->assertHeader('Location')->assertJsonCount(1, 'data.itens');

        $receita = Receita::withoutGlobalScopes()->firstOrFail();
        \Illuminate\Support\Facades\Storage::disk('s3')->assertExists($receita->arquivo_path);

        $pdf = $this->como($prof->usuario)->get("/api/v1/receitas/{$receita->uuid}/pdf");
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->como($pac->usuario)->get("/api/v1/receitas/{$receita->uuid}/pdf")->assertOk();           // o titular baixa a própria
        $this->como($this->secretario())->get("/api/v1/receitas/{$receita->uuid}/pdf")->assertForbidden();
        $this->assertDatabaseHas('logs_acesso', ['acao' => 'download', 'recurso_tipo' => 'receita']);
    }

    public function test_so_quem_emitiu_cancela_a_receita(): void
    {
        $prof = $this->profissional();
        $pac = Paciente::factory()->create(['unidade_id' => $this->unidade->id]);
        $id = $this->como($prof->usuario)->postJson('/api/v1/receitas', ['paciente_id' => $pac->uuid, 'itens' => [['medicamento' => 'Dipirona']]])->json('data.id');

        $this->como($this->profissional()->usuario)->postJson("/api/v1/receitas/{$id}/cancelar")->assertForbidden();
        $this->como($prof->usuario)->postJson("/api/v1/receitas/{$id}/cancelar")->assertOk()->assertJsonPath('data.status', 'cancelada');
        $this->como($prof->usuario)->postJson("/api/v1/receitas/{$id}/cancelar")->assertStatus(422);
    }

    public function test_resultado_de_exame_so_chega_ao_titular_depois_de_disponibilizado(): void
    {
        $prof = $this->profissional();
        $pac = $this->pacienteComAcesso();
        $exame = Exame::factory()->create(['paciente_id' => $pac->id, 'solicitante_id' => $prof->id]);

        $this->como($pac->usuario)->getJson("/api/v1/exames/{$exame->uuid}/resultado")->assertNotFound();      // ainda sem resultado

        $arquivo = UploadedFile::fake()->create('resultado.pdf', 120, 'application/pdf');
        $this->como($prof->usuario)->post("/api/v1/exames/{$exame->uuid}/resultado", ['arquivo' => $arquivo, 'resumo' => 'Dentro da normalidade'], ['Accept' => 'application/json'])
            ->assertCreated();

        $this->assertDatabaseHas('exames', ['id' => $exame->id, 'status' => 'resultado_disponivel']);
        $this->como($pac->usuario)->get("/api/v1/exames/{$exame->uuid}/resultado")->assertOk();
        $this->como($this->secretario())->get("/api/v1/exames/{$exame->uuid}/resultado")->assertForbidden();
        $this->assertDatabaseHas('logs_acesso', ['acao' => 'download', 'recurso_tipo' => 'resultado_exame']);
    }

    public function test_upload_de_tipo_invalido_e_recusado(): void
    {
        $prof = $this->profissional();
        $exame = Exame::factory()->create(['paciente_id' => Paciente::factory()->create(['unidade_id' => $this->unidade->id])->id, 'solicitante_id' => $prof->id]);

        $this->como($prof->usuario)->post("/api/v1/exames/{$exame->uuid}/resultado", ['arquivo' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('arquivo');
    }
}

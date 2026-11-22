<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->string('tipo');                                   // TipoAgendamento
            $t->string('modalidade')->default('presencial');      // ModalidadeAgendamento
            $t->string('status')->default('agendado');            // StatusAgendamento
            $t->foreignId('paciente_id')->constrained('pacientes');
            $t->foreignId('profissional_id')->nullable()->constrained('profissionais'); // exame de laboratório pode não ter
            $t->foreignId('unidade_id')->constrained('unidades');
            $t->timestampTz('inicio_em');
            $t->timestampTz('fim_em');
            $t->string('motivo')->nullable();
            $t->decimal('valor', 12, 2)->nullable();              // base do relatório financeiro (RF010)
            $t->foreignId('retorno_de_id')->nullable()->constrained('agendamentos');
            $t->foreignId('remarcado_de_id')->nullable()->constrained('agendamentos');
            $t->foreignId('criado_por_id')->constrained('usuarios');
            $t->foreignId('cancelado_por_id')->nullable()->constrained('usuarios');
            $t->timestampTz('cancelado_em')->nullable();
            $t->string('motivo_cancelamento')->nullable();
            $t->timestampTz('lembrete_enviado_em')->nullable();
            $t->string('chave_idempotencia', 64)->nullable()->unique();   // sha256(usuario|chave) — único GLOBAL
            $t->timestampsTz();                                           // sem soft delete: cancela por status

            $t->index(['profissional_id', 'inicio_em']);
            $t->index(['paciente_id', 'inicio_em']);
            $t->index(['unidade_id', 'inicio_em']);
            $t->index('status');
        });

        DB::statement('ALTER TABLE agendamentos ADD CONSTRAINT agendamentos_fim_apos_inicio CHECK (fim_em > inicio_em)');
        DB::statement("
            ALTER TABLE agendamentos ADD CONSTRAINT agendamentos_sem_sobreposicao
            EXCLUDE USING gist (profissional_id WITH =, tstzrange(inicio_em, fim_em) WITH &&)
            WHERE (profissional_id IS NOT NULL AND status NOT IN ('cancelado','remarcado','faltou'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};

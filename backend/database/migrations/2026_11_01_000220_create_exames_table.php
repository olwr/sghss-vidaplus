<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('exames', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('paciente_id')->constrained('pacientes');
            $t->foreignId('solicitante_id')->constrained('profissionais');
            $t->foreignId('unidade_id')->nullable()->constrained('unidades');           // onde será realizado
            $t->foreignId('agendamento_id')->nullable()->unique()->constrained('agendamentos');
            $t->foreignId('registro_clinico_id')->nullable()->constrained('registros_clinicos');
            $t->string('nome', 150);
            $t->string('instrucoes')->nullable();
            $t->decimal('valor', 12, 2)->nullable();              // com agendamento, prevalece o valor do Agendamento
            $t->string('status')->default('solicitado');          // StatusExame
            $t->timestampTz('solicitado_em')->useCurrent();
            $t->timestampTz('realizado_em')->nullable();
            $t->timestampsTz();
            $t->softDeletesTz();

            $t->index(['paciente_id', 'status']);
            $t->index('unidade_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exames');
    }
};

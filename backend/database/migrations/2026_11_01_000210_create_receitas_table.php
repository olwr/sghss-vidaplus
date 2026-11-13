<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('receitas', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('paciente_id')->constrained('pacientes');
            $t->foreignId('profissional_id')->constrained('profissionais');
            $t->foreignId('unidade_id')->constrained('unidades');
            $t->foreignId('registro_clinico_id')->nullable()->constrained('registros_clinicos');
            $t->foreignId('agendamento_id')->nullable()->constrained('agendamentos');
            $t->string('status')->default('emitida');             // StatusReceita
            $t->string('arquivo_path')->nullable();               // PDF no storage S3 (URL assinada temporária)
            $t->timestampTz('emitida_em')->useCurrent();
            $t->timestampTz('cancelada_em')->nullable();
            $t->timestampsTz();
            $t->softDeletesTz();

            $t->index(['paciente_id', 'emitida_em']);
            $t->index('profissional_id');
        });

        Schema::create('receita_itens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('receita_id')->constrained('receitas')->cascadeOnDelete();
            $t->string('medicamento', 200);
            $t->string('dosagem', 100)->nullable();
            $t->string('posologia')->nullable();
            $t->string('duracao', 100)->nullable();
            $t->smallInteger('quantidade')->nullable();           // sem timestamps (DBML)

            $t->index('receita_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receita_itens');
        Schema::dropIfExists('receitas');
    }
};

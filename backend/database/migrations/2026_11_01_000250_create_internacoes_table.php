<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('internacoes', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('paciente_id')->constrained('pacientes');
            $t->foreignId('unidade_id')->constrained('unidades');
            $t->foreignId('leito_id')->nullable()->constrained('leitos');           // NULL enquanto "solicitada"
            $t->foreignId('solicitante_id')->constrained('profissionais');
            $t->foreignId('responsavel_id')->nullable()->constrained('profissionais');
            $t->string('status')->default('solicitada');                            // StatusInternacao
            $t->text('motivo_criptografado')->nullable();
            $t->timestampTz('solicitada_em')->useCurrent();
            $t->timestampTz('admitida_em')->nullable();
            $t->timestampTz('alta_em')->nullable();
            $t->timestampsTz();
            $t->softDeletesTz();

            $t->index(['unidade_id', 'status']);
            $t->index('paciente_id');
            $t->index('leito_id');
        });

        // última barreira contra leito duplicado e paciente com duas internações ativas
        DB::statement("CREATE UNIQUE INDEX internacoes_leito_ativo_uq ON internacoes (leito_id) WHERE status = 'ativa'");
        DB::statement("CREATE UNIQUE INDEX internacoes_paciente_ativa_uq ON internacoes (paciente_id) WHERE status = 'ativa'");
    }

    public function down(): void
    {
        Schema::dropIfExists('internacoes');
    }
};

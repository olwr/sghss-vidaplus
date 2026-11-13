<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('transferencias', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->string('tipo');                                   // TipoTransferencia
            $t->foreignId('paciente_id')->nullable()->constrained('pacientes');
            $t->foreignId('profissional_id')->nullable()->constrained('profissionais');
            $t->foreignId('unidade_origem_id')->constrained('unidades');
            $t->foreignId('unidade_destino_id')->constrained('unidades');
            $t->foreignId('solicitante_id')->constrained('usuarios');
            $t->string('status')->default('pendente');            // StatusTransferencia
            $t->string('motivo')->nullable();
            $t->timestampTz('efetivada_em')->nullable();
            $t->timestampsTz();

            $t->index(['unidade_destino_id', 'status']);
            $t->index('paciente_id');
            $t->index('profissional_id');
        });

        DB::statement("ALTER TABLE transferencias ADD CONSTRAINT transferencias_alvo_ck CHECK (
            (tipo = 'paciente' AND paciente_id IS NOT NULL AND profissional_id IS NULL)
         OR (tipo = 'profissional' AND profissional_id IS NOT NULL AND paciente_id IS NULL))");
        DB::statement('ALTER TABLE transferencias ADD CONSTRAINT transferencias_origem_destino_ck CHECK (unidade_origem_id <> unidade_destino_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias');
    }
};

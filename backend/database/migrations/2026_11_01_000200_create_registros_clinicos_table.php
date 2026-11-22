<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('registros_clinicos', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('prontuario_id')->constrained('prontuarios');
            $t->foreignId('profissional_id')->constrained('profissionais');
            $t->foreignId('unidade_id')->constrained('unidades');                 // origem do registro
            $t->foreignId('agendamento_id')->nullable()->constrained('agendamentos');
            $t->text('queixa_principal_criptografado')->nullable();
            $t->text('diagnostico_criptografado')->nullable();
            $t->text('conduta_criptografado')->nullable();
            $t->text('observacoes_criptografado')->nullable();
            $t->timestampTz('registrado_em')->useCurrent();
            $t->timestampsTz();
            $t->softDeletesTz();

            $t->index(['prontuario_id', 'registrado_em']);
            $t->index('profissional_id');
            $t->index('agendamento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_clinicos');
    }
};

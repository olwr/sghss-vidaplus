<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('consentimentos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('paciente_id')->constrained('pacientes');
            $t->string('finalidade', 150);
            $t->string('versao_termo', 20);
            $t->timestampTz('concedido_em');
            $t->timestampTz('revogado_em')->nullable();
            $t->string('ip_origem', 45)->nullable();
            $t->timestampsTz();

            $t->index(['paciente_id', 'finalidade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimentos');
    }
};

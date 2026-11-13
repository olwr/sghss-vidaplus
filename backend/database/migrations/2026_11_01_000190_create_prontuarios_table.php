<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('prontuarios', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('paciente_id')->unique()->constrained('pacientes');   // 1:1
            $t->string('tipo_sanguineo', 3)->nullable();
            $t->text('alergias_criptografado')->nullable();
            $t->text('observacoes_gerais_criptografado')->nullable();
            $t->timestampsTz();
            $t->softDeletesTz();                                  // sem unidade_id (DBML): a Policy restringe via paciente
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prontuarios');
    }
};

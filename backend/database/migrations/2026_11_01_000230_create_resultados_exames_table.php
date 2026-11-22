<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('resultados_exames', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('exame_id')->constrained('exames');
            $t->foreignId('registrado_por_id')->constrained('profissionais');
            $t->text('resumo_criptografado')->nullable();
            $t->string('arquivo_path')->nullable();               // laudo no storage privado, nunca em pasta pública
            $t->string('arquivo_nome_original')->nullable();
            $t->string('arquivo_mime', 100)->nullable();
            $t->unsignedBigInteger('arquivo_tamanho')->nullable();
            $t->timestampTz('disponibilizado_em')->nullable();    // a partir daqui o paciente enxerga
            $t->timestampsTz();
            $t->softDeletesTz();

            $t->index('exame_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_exames');
    }
};

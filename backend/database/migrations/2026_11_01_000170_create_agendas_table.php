<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('agendas', function (Blueprint $t) {
            $t->id();                                             // sem uuid (DBML)
            $t->foreignId('profissional_id')->constrained('profissionais');
            $t->foreignId('unidade_id')->constrained('unidades');
            $t->smallInteger('dia_semana');                       // 0 = domingo ... 6 = sábado
            $t->time('hora_inicio');
            $t->time('hora_fim');
            $t->smallInteger('duracao_atendimento_min')->default(30);
            $t->date('vigencia_inicio');
            $t->date('vigencia_fim')->nullable();
            $t->boolean('ativa')->default(true);
            $t->timestampsTz();

            $t->index(['profissional_id', 'dia_semana']);
            $t->index('unidade_id');
        });

        // [extra, não consta no DBML] integridade barata da janela semanal
        DB::statement('ALTER TABLE agendas ADD CONSTRAINT agendas_janela_ck CHECK (
            dia_semana BETWEEN 0 AND 6 AND hora_fim > hora_inicio AND duracao_atendimento_min > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};

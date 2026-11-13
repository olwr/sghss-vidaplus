<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('leitos', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('unidade_id')->constrained('unidades');
            $t->string('codigo', 20);
            $t->string('setor', 100)->nullable();
            $t->string('tipo');                                   // TipoLeito
            $t->decimal('valor_diaria', 12, 2)->nullable();       // receita = dias internados x valor_diaria (RF010)
            $t->string('status')->default('disponivel');          // StatusLeito (mantido sob lock de linha)
            $t->timestampsTz();                                   // sem soft delete

            $t->unique(['unidade_id', 'codigo']);
            $t->index(['unidade_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leitos');
    }
};

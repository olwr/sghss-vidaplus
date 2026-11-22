<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('equipes', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('unidade_id')->constrained('unidades');
            $t->string('nome', 100);
            $t->string('descricao')->nullable();
            $t->boolean('ativa')->default(true);
            $t->timestampsTz();                                   // sem soft delete (DBML)

            $t->unique(['unidade_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipes');
    }
};

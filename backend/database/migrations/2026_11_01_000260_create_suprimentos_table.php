<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('suprimentos', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('unidade_id')->constrained('unidades');
            $t->string('nome', 150);
            $t->string('codigo', 50)->nullable();
            $t->string('unidade_medida', 20);                     // un, cx, ml...
            $t->integer('quantidade_atual')->default(0);
            $t->integer('quantidade_minima')->default(0);
            $t->timestampsTz();

            $t->unique(['unidade_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suprimentos');
    }
};

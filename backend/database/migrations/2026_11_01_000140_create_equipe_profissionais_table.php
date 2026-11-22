<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('equipe_profissionais', function (Blueprint $t) {
            $t->foreignId('equipe_id')->constrained('equipes')->cascadeOnDelete();
            $t->foreignId('profissional_id')->constrained('profissionais')->cascadeOnDelete();
            $t->string('funcao', 60)->nullable();                 // coordenador, enfermeiro responsável...
            $t->timestampTz('created_at')->useCurrent();          // só created_at (DBML)

            $t->primary(['equipe_id', 'profissional_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipe_profissionais');
    }
};

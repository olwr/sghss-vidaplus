<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('paciente_equipes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('paciente_id')->constrained('pacientes');
            $t->foreignId('equipe_id')->constrained('equipes');
            $t->date('inicio_em');
            $t->date('fim_em')->nullable();                       // NULL = vínculo ativo
            $t->timestampTz('created_at')->useCurrent();

            $t->index(['paciente_id', 'fim_em']);
            $t->index('equipe_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente_equipes');
    }
};

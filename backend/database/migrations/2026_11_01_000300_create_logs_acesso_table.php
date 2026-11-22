<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('logs_acesso', function (Blueprint $t) {
            $t->id();
            $t->foreignId('usuario_id')->constrained('usuarios');
            $t->foreignId('unidade_id')->nullable()->constrained('unidades');
            $t->foreignId('paciente_id')->nullable()->constrained('pacientes');   // "quem acessou o quê"
            $t->string('acao');                                   // AcaoAcesso
            $t->string('recurso_tipo', 100);                      // prontuario, resultado_exame, exportacao...
            $t->unsignedBigInteger('recurso_id')->nullable();
            $t->string('ip', 45)->nullable();
            $t->string('user_agent')->nullable();
            $t->string('rota')->nullable();
            $t->timestampTz('created_at')->useCurrent();          // append-only: só created_at

            $t->index(['usuario_id', 'created_at']);
            $t->index(['paciente_id', 'created_at']);
            $t->index(['recurso_tipo', 'recurso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_acesso');
    }
};

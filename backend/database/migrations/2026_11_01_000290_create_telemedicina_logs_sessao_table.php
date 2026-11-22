<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('telemedicina_logs_sessao', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sala_id')->constrained('telemedicina_salas');
            $t->foreignId('usuario_id')->constrained('usuarios');
            $t->string('evento');                                 // EventoSessaoTelemedicina
            $t->string('ip', 45)->nullable();
            $t->string('user_agent')->nullable();
            $t->jsonb('detalhes')->nullable();                    // resultado da verificação de câmera/microfone/rede
            $t->timestampTz('ocorrido_em')->useCurrent();         // sem updated_at: append-only

            $t->index(['sala_id', 'ocorrido_em']);
            $t->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemedicina_logs_sessao');
    }
};

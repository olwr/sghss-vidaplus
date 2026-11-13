<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('telemedicina_salas', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('agendamento_id')->unique()->constrained('agendamentos');
            $t->string('nome_sala', 100)->unique();               // identificador da sala no Jitsi
            $t->string('status')->default('criada');              // StatusSalaTelemedicina
            $t->timestampTz('valida_a_partir_de');
            $t->timestampTz('valida_ate');
            $t->timestampTz('iniciada_em')->nullable();
            $t->timestampTz('encerrada_em')->nullable();
            $t->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemedicina_salas');
    }
};

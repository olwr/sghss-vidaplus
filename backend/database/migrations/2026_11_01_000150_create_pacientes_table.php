<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('usuario_id')->nullable()->unique()->constrained('usuarios'); // NULL = cadastrado pela recepção
            $t->foreignId('unidade_id')->constrained('unidades');                       // unidade atual (global scope)
            $t->string('nome', 150);
            $t->text('cpf_criptografado');
            $t->char('cpf_hash', 64)->unique();                                         // blind index
            $t->date('data_nascimento');
            $t->string('sexo')->default('nao_informado');                               // Sexo
            $t->string('telefone', 20)->nullable();
            $t->string('email', 150)->nullable();
            $t->string('cep', 8)->nullable();
            $t->string('logradouro', 150)->nullable();
            $t->string('numero', 10)->nullable();
            $t->string('complemento', 100)->nullable();
            $t->string('bairro', 100)->nullable();
            $t->string('cidade', 100)->nullable();
            $t->char('uf', 2)->nullable();
            $t->jsonb('preferencias_acessibilidade')->nullable();
            $t->timestampTz('anonimizado_em')->nullable();                              // LGPD
            $t->timestampsTz();
            $t->softDeletesTz();

            $t->index('unidade_id');
            $t->index('nome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};

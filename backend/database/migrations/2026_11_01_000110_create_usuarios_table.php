<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('unidade_id')->nullable()->constrained('unidades'); // NULL = acesso global à rede
            $t->string('nome', 150);
            $t->string('email', 150)->unique();
            $t->string('telefone', 20)->nullable();
            $t->string('password');                                           // Argon2id (cast 'hashed')
            $t->boolean('ativo')->default(true);
            $t->timestampTz('email_verificado_em')->nullable();
            $t->text('two_factor_secret_criptografado')->nullable();
            $t->timestampTz('two_factor_confirmado_em')->nullable();
            $t->timestampTz('ultimo_login_em')->nullable();
            $t->timestampsTz();
            $t->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};

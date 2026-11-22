<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('profissionais', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->foreignId('usuario_id')->unique()->constrained('usuarios');   // nome/e-mail/telefone ficam em usuarios
            $t->foreignId('unidade_id')->constrained('unidades');
            $t->string('tipo');                                               // TipoProfissional
            $t->string('especialidade', 100)->nullable();
            $t->string('conselho_sigla', 10);                                 // CRM, COREN, CRBM...
            $t->string('conselho_numero', 20);
            $t->char('conselho_uf', 2);
            $t->text('cpf_criptografado');
            $t->char('cpf_hash', 64)->unique();                               // blind index (HMAC-SHA256)
            $t->boolean('ativo')->default(true);
            $t->timestampsTz();
            $t->softDeletesTz();

            $t->unique(['conselho_sigla', 'conselho_numero', 'conselho_uf']);
            $t->index('unidade_id');
            $t->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profissionais');
    }
};

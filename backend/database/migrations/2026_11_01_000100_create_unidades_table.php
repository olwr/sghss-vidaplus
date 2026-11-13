<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('unidades', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
            $t->string('tipo');                                   // TipoUnidade
            $t->string('nome', 150);
            $t->string('cnpj', 14)->nullable()->unique();         // opcional (home care pode usar o da matriz)
            $t->string('status')->default('ativa');               // StatusUnidade
            $t->string('telefone', 20)->nullable();
            $t->string('email', 150)->nullable();
            $t->string('cep', 8)->nullable();
            $t->string('logradouro', 150)->nullable();
            $t->string('numero', 10)->nullable();
            $t->string('complemento', 100)->nullable();
            $t->string('bairro', 100)->nullable();
            $t->string('cidade', 100)->nullable();
            $t->char('uf', 2)->nullable();
            $t->timestampsTz();
            $t->softDeletesTz();

            $t->index(['tipo', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades');
    }
};

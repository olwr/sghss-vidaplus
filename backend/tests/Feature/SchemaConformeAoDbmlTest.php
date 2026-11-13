<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Dbml;
use Tests\TestCase;

class SchemaConformeAoDbmlTest extends TestCase
{
    use RefreshDatabase;

    /** Diferenças conscientes entre o DBML e o pacote instalado (documente no PDF). */
    private const DIVERGENCIAS_CONHECIDAS = [
        'activity_log' => ['batch_uuid'],   // Activitylog 5 não usa batch_uuid
    ];

    public function test_todas_as_tabelas_e_colunas_do_dbml_existem_no_banco(): void
    {
        if (!Dbml::existe()) {
            $this->markTestSkipped('docs/db/sghss_vidaplus.dbml não encontrado (monte ./docs no contêiner da API).');
        }

        foreach (Dbml::tabelas() as $tabela => $colunas) {
            $this->assertTrue(Schema::hasTable($tabela), "Tabela {$tabela} não existe.");

            $esperadas = array_diff($colunas, self::DIVERGENCIAS_CONHECIDAS[$tabela] ?? []);
            $faltando = array_diff($esperadas, Schema::getColumnListing($tabela));
            $this->assertSame([], array_values($faltando), "Colunas ausentes em {$tabela}: ".implode(', ', $faltando));
        }
    }
}

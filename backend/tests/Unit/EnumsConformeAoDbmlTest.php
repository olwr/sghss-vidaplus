<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\Dbml;

class EnumsConformeAoDbmlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Dbml::existe()) {
            $this->markTestSkipped('docs/db/sghss_vidaplus.dbml não encontrado (monte ./docs no contêiner da API).');
        }
    }

    public function test_cada_enum_do_dbml_existe_com_os_mesmos_valores(): void
    {
        foreach (Dbml::enums() as $classe => $valores) {
            $this->assertTrue(enum_exists($classe), "Enum {$classe} não existe em app/Shared/Enums.");
            $this->assertSame($valores, array_map(fn ($c) => $c->value, $classe::cases()), "Valores divergentes em {$classe}.");
        }
    }

    public function test_todo_caso_tem_rotulo(): void
    {
        foreach (array_keys(Dbml::enums()) as $classe) {
            foreach ($classe::cases() as $caso) {
                $this->assertNotSame('', $caso->label(), "{$classe}::{$caso->name} sem rótulo.");
            }
        }
    }
}

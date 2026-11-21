<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Shared\Backup\RestauracaoDeBackup;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

class RestauracaoDeBackupTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/rb-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/db-dumps', 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/{*,*/*}', GLOB_BRACE) ?: [] as $f) {
            is_file($f) && unlink($f);
        }
    }

    private function servico(): RestauracaoDeBackup
    {
        return new RestauracaoDeBackup(['host' => 'x', 'port' => 5432, 'usuario' => 'x', 'senha' => 'x']);
    }

    private function zip(string $senha): string
    {
        $sql = $this->dir.'/db-dumps/postgresql-sghss.sql';
        file_put_contents($sql, "SELECT 1;\n");
        $caminho = $this->dir.'/backup.zip';
        $z = new ZipArchive();
        $z->open($caminho, ZipArchive::CREATE);
        $z->addFile($sql, 'db-dumps/postgresql-sghss.sql');
        $z->setEncryptionName('db-dumps/postgresql-sghss.sql', ZipArchive::EM_AES_256, $senha);
        $z->close();

        return $caminho;
    }

    public function test_extrai_o_dump_com_a_senha_correta(): void
    {
        $sql = $this->servico()->extrairDump($this->zip('segredo'), 'segredo', $this->dir.'/out');

        $this->assertSame("SELECT 1;\n", file_get_contents($sql));
    }

    public function test_senha_errada_falha_sem_gerar_arquivo(): void
    {
        $this->expectException(RuntimeException::class);

        $this->servico()->extrairDump($this->zip('segredo'), 'errada', $this->dir.'/out');
    }

    public function test_restaurado_nunca_pode_ter_mais_linhas_que_o_banco_vivo(): void
    {
        $r = $this->servico();

        $this->assertSame([], $r->comparar(['pacientes' => 8], ['pacientes' => 10]));
        $this->assertSame(['pacientes'], $r->comparar(['pacientes' => 11], ['pacientes' => 10]));
    }

    public function test_rejeita_identificador_com_injecao_de_sql(): void
    {
        $this->expectException(RuntimeException::class);

        $this->servico()->contar('banco', ['usuarios; DROP TABLE usuarios']);
    }
}

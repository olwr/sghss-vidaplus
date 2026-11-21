<?php

declare(strict_types=1);

namespace App\Shared\Backup;

use RuntimeException;
use ZipArchive;

/**
 * Teste de restauração do backup.
 *
 * Classe propositalmente SEM dependência do framework: só PHP + psql/ZipArchive.
 * Assim ela é testável isoladamente e o mesmo fluxo vale para um restore manual.
 */
final class RestauracaoDeBackup
{
    /**
     * @param  array{host:string,port:int|string,usuario:string,senha:string}  $conexao
     */
    public function __construct(private readonly array $conexao) {}

    /** Extrai o primeiro .sql do zip (layout Spatie: db-dumps/postgresql-<banco>.sql). */
    public function extrairDump(string $zip, ?string $senha, string $destino): string
    {
        $arquivo = new ZipArchive;
        if ($arquivo->open($zip) !== true) {
            throw new RuntimeException('Não foi possível abrir o arquivo de backup.');
        }
        if ($senha !== null && $senha !== '') {
            $arquivo->setPassword($senha);
        }

        $entrada = null;
        for ($i = 0; $i < $arquivo->numFiles; $i++) {
            $nome = (string) $arquivo->getNameIndex($i);
            if (str_ends_with($nome, '.sql')) {
                $entrada = $nome;
                break;
            }
        }
        if ($entrada === null) {
            $arquivo->close();
            throw new RuntimeException('O backup não contém dump .sql.');
        }

        $conteudo = $arquivo->getFromName($entrada);
        $arquivo->close();
        if ($conteudo === false) {
            throw new RuntimeException('Falha ao ler o dump (senha incorreta ou arquivo corrompido).');
        }

        if (! is_dir($destino) && ! mkdir($destino, 0700, true) && ! is_dir($destino)) {
            throw new RuntimeException('Não foi possível criar o diretório temporário.');
        }
        $caminho = rtrim($destino, '/').'/restauracao.sql';
        file_put_contents($caminho, $conteudo);

        return $caminho;
    }

    /** Cria o banco temporário e carrega o dump nele. Aborta no primeiro erro SQL. */
    public function restaurar(string $sql, string $bancoTemp): void
    {
        $this->validarNome($bancoTemp);
        $this->psql('postgres', ['-c', sprintf('CREATE DATABASE "%s"', $bancoTemp)]);
        $this->psql($bancoTemp, ['-v', 'ON_ERROR_STOP=1', '-f', $sql]);
    }

    /**
     * @param  list<string>  $tabelas
     * @return array<string,int>
     */
    public function contar(string $banco, array $tabelas): array
    {
        $this->validarNome($banco);
        $resultado = [];
        foreach ($tabelas as $tabela) {
            $this->validarNome($tabela);
            $saida = $this->psql($banco, ['-At', '-c', sprintf('SELECT count(*) FROM "%s"', $tabela)]);
            $resultado[$tabela] = (int) trim($saida);
        }

        return $resultado;
    }

    public function descartar(string $bancoTemp): void
    {
        $this->validarNome($bancoTemp);
        $this->psql('postgres', ['-c', sprintf('DROP DATABASE IF EXISTS "%s"', $bancoTemp)]);
    }

    /**
     * Restaurado pode ter MENOS linhas que o banco vivo (o backup é anterior),
     * nunca MAIS. Devolve as tabelas divergentes.
     *
     * @param  array<string,int>  $restaurado
     * @param  array<string,int>  $vivo
     * @return list<string>
     */
    public function comparar(array $restaurado, array $vivo): array
    {
        $divergentes = [];
        foreach ($restaurado as $tabela => $qtd) {
            if ($qtd > ($vivo[$tabela] ?? 0)) {
                $divergentes[] = $tabela;
            }
        }

        return $divergentes;
    }

    private function validarNome(string $nome): void
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $nome)) {
            throw new RuntimeException("Identificador inválido: {$nome}");
        }
    }

    /** @param list<string> $args */
    private function psql(string $banco, array $args): string
    {
        $cmd = array_merge([
            'psql', '-h', (string) $this->conexao['host'], '-p', (string) $this->conexao['port'],
            '-U', $this->conexao['usuario'], '-d', $banco, '-X', '-q',
        ], $args);

        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes,
            null, array_merge(getenv(), ['PGPASSWORD' => $this->conexao['senha']]));
        if (! is_resource($proc)) {
            throw new RuntimeException('psql não pôde ser executado.');
        }
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $codigo = proc_close($proc);

        if ($codigo !== 0) {
            throw new RuntimeException('psql falhou: '.trim((string) $err));
        }

        return (string) $out;
    }
}

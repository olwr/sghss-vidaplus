<?php

namespace Tests\Support;

use Illuminate\Support\Str;

/** Leitor mínimo do DBML versionado em docs/db (fonte da verdade do modelo de dados). */
final class Dbml
{
    public static function caminho(): string
    {
        // backend/tests/Support -> raiz do repositório (no contêiner: /var/www, com ./docs montado)
        return dirname(__DIR__, 3).'/docs/db/sghss_vidaplus.dbml';
    }

    public static function existe(): bool
    {
        return is_file(self::caminho());
    }

    /** @return array<string, list<string>> classe do enum => valores, na ordem do DBML */
    public static function enums(): array
    {
        preg_match_all('/^Enum\s+(\w+)\s*\{(.*?)^\}/ms', (string) file_get_contents(self::caminho()), $m, PREG_SET_ORDER);

        $enums = [];
        foreach ($m as [, $nome, $corpo]) {
            $valores = array_values(array_filter(array_map('trim', explode("\n", $corpo))));
            $enums['App\\Shared\\Enums\\'.Str::studly($nome)] = $valores;
        }

        return $enums;
    }

    /** @return array<string, list<string>> tabela => colunas */
    public static function tabelas(): array
    {
        preg_match_all('/^Table\s+(\w+)\s*\{(.*?)^\}/ms', (string) file_get_contents(self::caminho()), $m, PREG_SET_ORDER);

        $tabelas = [];
        foreach ($m as [, $nome, $corpo]) {
            $corpo = preg_replace('/indexes\s*\{.*?\n  \}/s', '', $corpo);
            $corpo = preg_replace("/Note:\s*'''.*?'''/s", '', $corpo);
            preg_match_all('/^  (\w+)\s+\w+/m', $corpo, $c);
            $tabelas[$nome] = array_values(array_diff($c[1], ['Note', 'indexes']));
        }

        return $tabelas;
    }
}

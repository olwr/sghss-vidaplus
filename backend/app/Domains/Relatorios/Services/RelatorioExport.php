<?php

namespace App\Domains\Relatorios\Services;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RelatorioExport implements FromArray, WithHeadings
{
    /** @param array{titulo: string, colunas: list<string>, linhas: list<list<mixed>>} $dados */
    public function __construct(private readonly array $dados)
    {
    }

    public function array(): array
    {
        return $this->dados['linhas'];
    }

    public function headings(): array
    {
        return $this->dados['colunas'];
    }
}

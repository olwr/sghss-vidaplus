<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shared\Backup\RestauracaoDeBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TestarRestauracaoBackup extends Command
{
    protected $signature = 'backup:testar-restauracao
        {--arquivo= : caminho local de um .zip (padrão: o mais recente do disco s3_backup)}
        {--tabelas=usuarios,pacientes,agendamentos,prontuarios : tabelas a conferir}';

    protected $description = 'Restaura o último backup em um banco temporário, confere contagens e mede o RTO.';

    public function handle(): int
    {
        $inicio = microtime(true);
        $temp = sys_get_temp_dir().'/restauracao-'.bin2hex(random_bytes(4));
        $banco = 'restauracao_'.bin2hex(random_bytes(4));
        $zip = (string) $this->option('arquivo');
        $baixado = false;

        $cfg = config('database.connections.pgsql');
        $restauracao = new RestauracaoDeBackup([
            'host' => (string) $cfg['host'], 'port' => $cfg['port'],
            'usuario' => (string) $cfg['username'], 'senha' => (string) $cfg['password'],
        ]);

        try {
            if ($zip === '') {
                $zip = $this->baixarMaisRecente($temp);
                $baixado = true;
            }
            $this->line("Backup: {$zip}");

            $sql = $restauracao->extrairDump($zip, config('backup.backup.password'), $temp);
            $restauracao->restaurar($sql, $banco);

            $tabelas = array_map('trim', explode(',', (string) $this->option('tabelas')));
            $restaurado = $restauracao->contar($banco, $tabelas);
            $vivo = [];
            foreach ($tabelas as $t) {
                $vivo[$t] = (int) DB::table($t)->count();
            }

            $this->table(
                ['tabela', 'restaurado', 'vivo'],
                array_map(fn ($t) => [$t, $restaurado[$t], $vivo[$t]], $tabelas)
            );

            $divergentes = $restauracao->comparar($restaurado, $vivo);
            $rto = round(microtime(true) - $inicio, 1);
            $this->info("RTO medido: {$rto} s");

            if ($divergentes !== []) {
                $this->error('Divergência em: '.implode(', ', $divergentes));

                return self::FAILURE;
            }
            $this->info('Restauração conferida.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            try {
                $restauracao->descartar($banco);
            } catch (Throwable) {
            }
            @array_map('unlink', glob($temp.'/*') ?: []);
            @rmdir($temp);
            if ($baixado && is_file($zip)) {
                @unlink($zip);
            }
        }
    }

    private function baixarMaisRecente(string $temp): string
    {
        $disco = Storage::disk('s3_backup');
        $arquivos = collect($disco->allFiles())
            ->filter(fn ($f) => str_ends_with($f, '.zip'))
            ->sortByDesc(fn ($f) => $disco->lastModified($f));
        $mais = $arquivos->first();
        if (! $mais) {
            throw new \RuntimeException('Nenhum backup encontrado no disco s3_backup.');
        }
        @mkdir($temp, 0700, true);
        $local = $temp.'/backup.zip';
        file_put_contents($local, $disco->get($mais));

        return $local;
    }
}

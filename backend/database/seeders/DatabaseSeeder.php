<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // perfis e permissões: sempre (inclusive produção)
        $this->call(PerfisPermissoesSeeder::class);

        // dados fictícios: SOMENTE local e homologação
        if (app()->environment(['local', 'staging'])) {
            $this->call(DemoSeeder::class);
        }
    }
}

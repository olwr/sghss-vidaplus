<?php

namespace App\Shared\Providers;

use App\Domains\Administracao\Console\AnonimizarExpirados;
use App\Domains\Agendamentos\Console\EnviarLembretes;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Comandos dos domínios (o Laravel só descobre sozinho os de app/Console/Commands)
        if ($this->app->runningInConsole()) {
            $this->commands([EnviarLembretes::class, AnonimizarExpirados::class]);
        }
    }
}

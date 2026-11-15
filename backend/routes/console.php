<?php

use Illuminate\Support\Facades\Schedule;

// Backup entra aqui: Schedule::command('backup:run')->dailyAt('02:00');
Schedule::command('agendamentos:enviar-lembretes --horas=24')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('lgpd:anonimizar-expirados')->dailyAt('03:30')->withoutOverlapping()->onOneServer();

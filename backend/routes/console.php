<?php

use Illuminate\Support\Facades\Schedule;

// Backup: só o banco (o bucket de arquivos tem durabilidade própria). Horários fora do expediente.
Schedule::command('backup:run --only-db')->dailyAt('02:00')->withoutOverlapping()->onOneServer();
Schedule::command('backup:clean')->dailyAt('02:30')->withoutOverlapping()->onOneServer();
Schedule::command('backup:monitor')->dailyAt('03:00')->onOneServer();
Schedule::command('agendamentos:enviar-lembretes --horas=24')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('lgpd:anonimizar-expirados')->dailyAt('03:30')->withoutOverlapping()->onOneServer();

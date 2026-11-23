<?php

use Illuminate\Support\Facades\Schedule;

// Backup diário às 03:00 (precisa do cron: * * * * * php artisan schedule:run)
Schedule::command('moz:backup')->dailyAt('03:00');

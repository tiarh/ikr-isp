<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule jobs
Schedule::command('queue:prune-failed --hours=72')->daily();
Schedule::command('queue:prune-jobs --hours=48')->hourly();

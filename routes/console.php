<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Runs synchronously (--now) rather than dispatching to the queue: the
// only moving part this then depends on is the server's cron-driven
// scheduler itself, not a separately-managed queue worker daemon that can
// silently die and leave dispatched sync jobs stuck unprocessed forever.
Schedule::command('salesplay:sync', ['--now' => true])
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

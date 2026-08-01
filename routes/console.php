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

// Drains the database queue every minute instead of relying on a
// permanently-running `queue:work` daemon (same reasoning as above) — used
// for full-history SalesPlay syncs (an account's true first sync, or an
// admin's Resync Penuh) that are queued rather than run inline because
// they can take longer than a web request allows. --stop-when-empty exits
// once nothing's left, so it's safe to fire every minute without piling up
// processes; --max-time is a safety cap on picking up *new* jobs, it does
// not interrupt one already running.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=1')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

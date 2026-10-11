<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:prune-staged-images --older-than=60 --limit=1000')
    ->hourly()
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('app:prune-staged-upload-records --limit=10000')
    ->hourly()
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('media:process-operations --limit=100')
    ->everyMinute()
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('queue:prune-failed --hours=168')
    ->daily()
    ->onOneServer()
    ->withoutOverlapping()
    ->environments(['production']);

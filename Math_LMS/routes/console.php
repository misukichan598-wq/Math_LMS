<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule hall of fame ranking updates
Schedule::call(function () {
    \App\Models\HallOfFame::updateRankings();
})->everyFifteenMinutes();

// Clean up old sessions
Schedule::command('session:gc')->daily();

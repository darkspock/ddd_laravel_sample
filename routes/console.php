<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:process-remove-events')->everySecond();

// Process automatic/recurring marketing campaigns every minute
Schedule::command('campaigns:process-automatic')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Process autotags every hour to apply tags to matching clients
Schedule::command('autotags:process')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();


<?php

use App\Jobs\Hrm\AssignEmployeeLeavesJob;
use Illuminate\Support\Facades\Schedule;


Schedule::command('queue:work --queue=high-priority,high,default --memory=512 --tries=3 --timeout=600 --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('app:subscriber-expire-reminder')
    ->dailyAt('00:01')
    ->withoutOverlapping();

Schedule::command('app:subscriber-expire')
    ->dailyAt('00:01')
    ->withoutOverlapping();

Schedule::command('coupons:expire')
    ->dailyAt('00:01')
    ->withoutOverlapping();

// Leave entitlements are handed out one month at a time, so each new month
// needs its own run. The job is idempotent — it skips months already assigned.
// Schedule::job(new AssignEmployeeLeavesJob, 'hrm-leave-assign')
//     ->monthlyOn(1, '00:05')
//     ->withoutOverlapping();    

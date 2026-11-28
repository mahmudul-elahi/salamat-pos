<?php

declare(strict_types=1);

use App\Jobs\Hrm\AssignEmployeeLeavesJob;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --queue=high-priority,high,default --memory=512 --tries=3 --timeout=600 --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();

// Safety net for the storefront search index: re-sends products changed recently in case
// their queued sync never ran (for example while the queue worker was down).
Schedule::command('products:sync-search-index --minutes=15')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('coupons:expire')
    ->dailyAt('00:01')
    ->withoutOverlapping();

// Leave entitlements are handed out one month at a time, so each new month
// needs its own run. The job is idempotent — it skips months already assigned.
// Schedule::job(new AssignEmployeeLeavesJob, 'hrm-leave-assign')
//     ->monthlyOn(1, '00:05')
//     ->withoutOverlapping();

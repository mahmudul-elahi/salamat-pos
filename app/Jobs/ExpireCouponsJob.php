<?php

namespace App\Jobs;

use App\Models\Coupon\NewCoupon;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireCouponsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $today = Carbon::now()->startOfDay();

        NewCoupon::where('status', 1)
            ->whereDate('end_date', '<', $today)
            ->update(['status' => 0]);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ExpireCouponsJob;

class ExpireCouponsCommand extends Command
{
    protected $signature = 'coupons:expire';

    protected $description = 'Expire coupons that have passed their end date';

    public function handle()
    {

        (new ExpireCouponsJob)->handle();

        $this->info('Coupons expired successfully.');
    }
}

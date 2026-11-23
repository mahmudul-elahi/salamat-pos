<?php

namespace App\Console\Commands;

use App\Jobs\ExpireCouponsJob;
use Illuminate\Console\Command;

class ExpireCouponsCommand extends Command
{
    #[\Override]
    protected $signature = 'coupons:expire';

    #[\Override]
    protected $description = 'Expire coupons that have passed their end date';

    public function handle()
    {

        (new ExpireCouponsJob)->handle();

        $this->info('Coupons expired successfully.');
    }
}

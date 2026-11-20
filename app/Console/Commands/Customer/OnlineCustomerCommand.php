<?php

declare(strict_types=1);

namespace App\Console\Commands\Customer;

use App\Models\Pos\Contact\Contact;
use Illuminate\Console\Command;

class OnlineCustomerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:online-customer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Start processing...');
        Contact::query()
            ->where('online', false)
            ->where('type', 'consumer')
            ->whereNotNull('email')
            ->update(['online' => true]);

        $this->info('Done!');
    }
}

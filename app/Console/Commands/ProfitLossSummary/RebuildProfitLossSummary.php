<?php

declare(strict_types=1);

namespace App\Console\Commands\ProfitLossSummary;

use App\Models\Pos\ProfitLoss\ProfitLossSummary;
use App\Services\Pos\Report\ProfitLoss\ProfitLossSummaryService;
use Illuminate\Console\Command;

class RebuildProfitLossSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profit-loss:rebuild {--full : Full rebuild from scratch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild profit loss summary table from scratch';

    /**
     * Execute the console command.
     */
    public function handle(ProfitLossSummaryService $service): void
    {
        if ($this->option('full')) {
            // ⚠️ Only use once (first time setup)
            $this->warn('Full rebuild started — this may take a while...');
            ProfitLossSummary::query()->truncate();
            $service->rebuildAll();
            $this->info('Full rebuild done!');
        } else {
            // ✅ Daily scheduled sync (only missing + updated)
            $this->info('Syncing missing records...');
            $service->rebuildAll();

            $this->info('Syncing updated records...');
            $service->syncUpdated();

            $this->info('Incremental sync done!');
        }
    }
}

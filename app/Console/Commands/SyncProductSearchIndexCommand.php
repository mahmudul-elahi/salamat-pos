<?php

namespace App\Console\Commands;

use App\Models\Pos\Product\Product;
use App\Services\Product\ProductSearchIndexService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * Re-sends products to the storefront search index.
 *
 * Scheduled with --minutes as a safety net for changes whose queued sync never ran, and
 * run with --all to rebuild the index from scratch, instead of `scout:import` in tekzo-api.
 */
class SyncProductSearchIndexCommand extends Command
{
    private const int CHUNK_SIZE = 200;

    #[\Override]
    protected $signature = 'products:sync-search-index
        {--minutes=15 : Re-sync products changed in the last N minutes}
        {--all : Re-sync every product}';

    #[\Override]
    protected $description = 'Send products to the storefront Meilisearch index';

    public function handle(ProductSearchIndexService $searchIndex): int
    {
        if (! $searchIndex->isConfigured()) {
            $this->error('MEILISEARCH_HOST is not configured, so nothing was sent.');

            return self::FAILURE;
        }

        $minutes = (int) $this->option('minutes');
        $synced = 0;

        try {
            Product::withTrashed()
                ->when(! $this->option('all'), fn ($query) => $query->where('updated_at', '>=', now()->subMinutes($minutes)))
                ->chunkById(self::CHUNK_SIZE, function (Collection $products) use ($searchIndex, &$synced): void {
                    $searchIndex->syncMany($products);
                    $synced += $products->count();
                });
        } catch (ConnectionException|RequestException $exception) {
            $this->error("Meilisearch could not be reached after {$synced} products: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Synced {$synced} products to the search index.");

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\Product\SyncProductSearchIndexJob;
use App\Models\Pos\Product\Product;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Keeps the storefront (tekzo) Meilisearch index in sync whenever a product changes in the POS.
 * Runs after the DB transaction commits so rolled-back saves never reach the index, and
 * queues the update so it is retried when Meilisearch is unreachable.
 */
class ProductObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Product $product): void
    {
        SyncProductSearchIndexJob::dispatch($product->getKey());
    }

    public function deleted(Product $product): void
    {
        SyncProductSearchIndexJob::dispatch($product->getKey());
    }

    public function restored(Product $product): void
    {
        SyncProductSearchIndexJob::dispatch($product->getKey());
    }

    public function forceDeleted(Product $product): void
    {
        SyncProductSearchIndexJob::dispatch($product->getKey());
    }
}

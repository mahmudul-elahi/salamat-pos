<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Pos\Product\Product;
use App\Services\Product\ProductSearchIndexService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Keeps the storefront (tekzo) Meilisearch index in sync whenever a product changes in the POS.
 * Runs after the DB transaction commits so rolled-back saves never reach the index.
 */
class ProductObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly ProductSearchIndexService $searchIndex) {}

    public function saved(Product $product): void
    {
        $this->searchIndex->sync($product);
    }

    public function deleted(Product $product): void
    {
        $this->searchIndex->remove($product->getKey());
    }

    public function restored(Product $product): void
    {
        $this->searchIndex->sync($product);
    }

    public function forceDeleted(Product $product): void
    {
        $this->searchIndex->remove($product->getKey());
    }
}

<?php

declare(strict_types=1);

namespace App\Jobs\Product;

use App\Models\Pos\Product\Product;
use App\Services\Product\ProductSearchIndexService;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Brings one product's entry in the storefront search index up to date.
 *
 * The job reads the product when it runs, so it always sends the latest state: the
 * product is indexed, or removed when it is deleted or not an e-commerce product. When
 * Meilisearch cannot be reached the job tries again later instead of losing the change.
 */
class SyncProductSearchIndexJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    /**
     * Seconds to wait before each retry: about 20 minutes in all.
     *
     * @var list<int>
     */
    public const array RETRY_DELAYS = [30, 120, 300, 900];

    public int $tries = 5;

    public function __construct(public readonly int $productId) {}

    public function uniqueId(): string
    {
        return (string) $this->productId;
    }

    public function handle(ProductSearchIndexService $searchIndex): void
    {
        $product = Product::withTrashed()->find($this->productId);

        try {
            $product instanceof Product
                ? $searchIndex->sync($product)
                : $searchIndex->remove($this->productId);
        } catch (ConnectionException|RequestException $exception) {
            $this->retryLater($exception);
        }
    }

    /**
     * Retry by releasing the job rather than throwing, so a Meilisearch outage can never
     * surface as an error where the product was saved.
     */
    private function retryLater(ConnectionException|RequestException $exception): void
    {
        if ($this->attempts() >= $this->tries) {
            Log::error("Meilisearch: gave up syncing product {$this->productId} after {$this->tries} attempts", [
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        Log::warning("Meilisearch: syncing product {$this->productId} failed, retrying", [
            'attempt' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        $this->release(self::RETRY_DELAYS[min($this->attempts(), count(self::RETRY_DELAYS)) - 1]);
    }
}

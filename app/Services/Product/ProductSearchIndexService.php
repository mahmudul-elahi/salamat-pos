<?php

namespace App\Services\Product;

use App\Models\Pos\Product\Product;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pushes POS products into the Meilisearch index that the tekzo storefront searches.
 *
 * The document shape must stay identical to Product::toSearchableArray() in tekzo-api.
 * Failures are logged and swallowed so a search outage never blocks saving a product.
 */
class ProductSearchIndexService
{
    /**
     * Add/replace the product in the index, or remove it when it should not be searchable.
     */
    public function sync(Product $product): void
    {
        if (! $this->shouldBeSearchable($product)) {
            $this->remove($product->getKey());

            return;
        }

        $this->send(
            fn (PendingRequest $request) => $request->post($this->documentsUrl().'?primaryKey=id', [
                $this->toDocument($product->loadMissing(['category:id,name', 'brand:id,name'])),
            ]),
            "index product {$product->getKey()}"
        );
    }

    public function remove(int|string $productId): void
    {
        $this->send(
            fn (PendingRequest $request) => $request->delete($this->documentsUrl().'/'.$productId),
            "remove product {$productId}"
        );
    }

    /**
     * Only live e-commerce products are shown by the storefront search.
     */
    public function shouldBeSearchable(Product $product): bool
    {
        return ! $product->trashed() && (bool) $product->is_ecommerce;
    }

    /**
     * @return array{id: int, name: ?string, slug: ?string, headline: ?string, product_image: ?string, short_description: ?string, category: ?string, brand: ?string, status: mixed, name_words: string}
     */
    public function toDocument(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->product_name,
            'slug' => $product->slug,
            'headline' => $product->headline,
            'product_image' => $product->product_image,
            'short_description' => $product->short_description,
            'category' => $product->category?->name,
            'brand' => $product->brand?->name,
            'status' => $product->status,
            'name_words' => implode(' ', $this->tokenize($product->product_name ?? '')),
        ];
    }

    /**
     * "Find My iPhone Case" → ["find", "my", "iphone", "case"]
     *
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        return array_filter(
            explode(' ', strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $text)))
        );
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     */
    private function send(callable $call, string $action): void
    {
        if (blank(config('services.meilisearch.host'))) {
            Log::warning("Meilisearch: skipped {$action}, MEILISEARCH_HOST is not configured");

            return;
        }

        try {
            $request = Http::withToken((string) config('services.meilisearch.key'))
                ->acceptJson()
                ->connectTimeout(2)
                ->timeout(5);

            $call($request)->throw();
        } catch (Throwable $exception) {
            Log::warning("Meilisearch: failed to {$action}", ['error' => $exception->getMessage()]);
        }
    }

    private function documentsUrl(): string
    {
        return rtrim((string) config('services.meilisearch.host'), '/')
            .'/indexes/'.config('services.meilisearch.products_index').'/documents';
    }
}

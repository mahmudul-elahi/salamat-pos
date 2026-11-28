<?php

namespace App\Services\Product;

use App\Models\Pos\Product\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pushes POS products into the Meilisearch index that the tekzo storefront searches.
 *
 * The document shape must stay identical to Product::toSearchableArray() in tekzo-api.
 * A failed request throws, so the caller (SyncProductSearchIndexJob) can retry it; when
 * Meilisearch is not configured at all, nothing is sent.
 */
class ProductSearchIndexService
{
    /**
     * Add/replace the product in the index, or remove it when it should not be searchable.
     *
     * @throws RequestException|ConnectionException
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

    /**
     * @throws RequestException|ConnectionException
     */
    public function remove(int|string $productId): void
    {
        $this->send(
            fn (PendingRequest $request) => $request->delete($this->documentsUrl().'/'.$productId),
            "remove product {$productId}"
        );
    }

    /**
     * Index the searchable products and remove the rest, in two requests.
     *
     * @param  Collection<int, Product>  $products
     *
     * @throws RequestException|ConnectionException
     */
    public function syncMany(Collection $products): void
    {
        [$searchable, $hidden] = $products->partition(fn (Product $product): bool => $this->shouldBeSearchable($product));

        if ($searchable->isNotEmpty()) {
            $searchable->loadMissing(['category:id,name', 'brand:id,name']);

            $this->send(
                fn (PendingRequest $request) => $request->post(
                    $this->documentsUrl().'?primaryKey=id',
                    $searchable->map(fn (Product $product): array => $this->toDocument($product))->values()->all(),
                ),
                "index {$searchable->count()} products"
            );
        }

        if ($hidden->isNotEmpty()) {
            $this->send(
                fn (PendingRequest $request) => $request->post(
                    $this->documentsUrl().'/delete-batch',
                    $hidden->map(fn (Product $product): int => $product->getKey())->values()->all(),
                ),
                "remove {$hidden->count()} products"
            );
        }
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

    public function isConfigured(): bool
    {
        return filled(config('services.meilisearch.host'));
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     *
     * @throws RequestException|ConnectionException
     */
    private function send(callable $call, string $action): void
    {
        if (! $this->isConfigured()) {
            Log::warning("Meilisearch: skipped {$action}, MEILISEARCH_HOST is not configured");

            return;
        }

        $request = Http::withToken((string) config('services.meilisearch.key'))
            ->acceptJson()
            ->connectTimeout(2)
            ->timeout(10);

        $call($request)->throw();
    }

    private function documentsUrl(): string
    {
        return rtrim((string) config('services.meilisearch.host'), '/')
            .'/indexes/'.config('services.meilisearch.products_index').'/documents';
    }
}

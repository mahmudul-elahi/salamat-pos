<?php

namespace Tests\Feature;

use App\Jobs\Product\SyncProductSearchIndexJob;
use App\Models\Pos\Product\Product;
use App\Services\Product\ProductSearchIndexService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductSearchIndexTest extends TestCase
{
    private const string DOCUMENTS_URL = 'http://meili.test/indexes/products/documents';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('slug')->nullable();
            $table->string('headline')->nullable();
            $table->string('product_image')->nullable();
            $table->text('short_description')->nullable();
            $table->string('status')->default('1');
            $table->boolean('is_ecommerce')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('categories')->insert(['id' => 1, 'name' => 'Phones']);
        DB::table('brands')->insert(['id' => 1, 'name' => 'Apple']);

        config([
            'services.meilisearch.host' => 'http://meili.test',
            'services.meilisearch.key' => 'secret',
            'services.meilisearch.products_index' => 'products',
        ]);

        Http::fake(['meili.test/*' => Http::response(['taskUid' => 1], 202)]);
    }

    public function test_creating_an_ecommerce_product_adds_it_to_the_search_index(): void
    {
        $product = $this->createProduct();

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === self::DOCUMENTS_URL.'?primaryKey=id'
            && $request->hasHeader('Authorization', 'Bearer secret')
            && $request->data() === [[
                'id' => $product->id,
                'name' => 'iPhone 16 Pro-Max',
                'slug' => 'iphone-16-pro-max',
                'headline' => null,
                'product_image' => null,
                'short_description' => null,
                'category' => 'Phones',
                'brand' => 'Apple',
                'status' => '1',
                'name_words' => 'iphone 16 promax',
            ]]);
    }

    public function test_updating_a_product_reindexes_it(): void
    {
        $product = $this->createProduct();

        $product->update(['product_name' => 'iPhone 17']);

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->data()[0]['name'] === 'iPhone 17');
    }

    public function test_disabling_ecommerce_removes_the_product_from_the_index(): void
    {
        $product = $this->createProduct();

        $product->update(['is_ecommerce' => false]);

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && $request->url() === self::DOCUMENTS_URL.'/'.$product->id);
    }

    public function test_deleting_a_product_removes_it_from_the_index(): void
    {
        $product = $this->createProduct();

        $product->delete();

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && $request->url() === self::DOCUMENTS_URL.'/'.$product->id);
    }

    public function test_a_rolled_back_product_is_never_indexed(): void
    {
        DB::beginTransaction();
        $this->createProduct();
        DB::rollBack();

        Http::assertNothingSent();
    }

    public function test_a_meilisearch_outage_does_not_break_saving_the_product(): void
    {
        $this->meilisearchIsDown();

        $product = $this->createProduct();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_a_sync_that_cannot_reach_meilisearch_is_retried_later(): void
    {
        $product = $this->createProduct();
        $this->meilisearchIsDown();

        $job = (new SyncProductSearchIndexJob($product->id))->withFakeQueueInteractions();
        $job->handle(app(ProductSearchIndexService::class));

        $job->assertReleased(delay: SyncProductSearchIndexJob::RETRY_DELAYS[0]);
    }

    public function test_saving_a_product_queues_its_search_index_sync(): void
    {
        Queue::fake();

        $product = $this->createProduct();

        Queue::assertPushed(SyncProductSearchIndexJob::class, fn (SyncProductSearchIndexJob $job): bool => $job->productId === $product->id);
    }

    public function test_the_safety_net_resends_recently_changed_products(): void
    {
        $recent = $this->createProduct();
        $hidden = $this->createProduct(['product_name' => 'Hidden case', 'is_ecommerce' => false]);
        $old = $this->createProduct(['product_name' => 'Old charger']);
        DB::table('products')->where('id', $old->id)->update(['updated_at' => now()->subHour()]);
        Http::fake(['meili.test/*' => Http::response(['taskUid' => 1], 202)]);

        $this->artisan('products:sync-search-index', ['--minutes' => 15])->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/indexes/products/documents?primaryKey=id')
            && collect($request->data())->pluck('id')->all() === [$recent->id]);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/documents/delete-batch')
            && $request->data() === [$hidden->id]);
    }

    public function test_the_full_sync_resends_every_product(): void
    {
        $products = collect([$this->createProduct(), $this->createProduct(['product_name' => 'Old charger'])]);
        DB::table('products')->where('id', $products->last()->id)->update(['updated_at' => now()->subYear()]);
        Http::fake(['meili.test/*' => Http::response(['taskUid' => 1], 202)]);

        $this->artisan('products:sync-search-index', ['--all' => true])->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/documents?primaryKey=id')
            && collect($request->data())->pluck('id')->all() === $products->pluck('id')->all());
    }

    public function test_the_safety_net_reports_failure_when_meilisearch_is_down(): void
    {
        $this->createProduct();
        $this->meilisearchIsDown();

        $this->artisan('products:sync-search-index')->assertFailed();
    }

    /**
     * Replace the healthy Meilisearch fake from setUp(); a second Http::fake() would not,
     * because the first matching stub wins.
     */
    private function meilisearchIsDown(): void
    {
        Http::swap(new Factory);
        Http::fake(['meili.test/*' => Http::response('down', 503)]);
    }

    private function createProduct(array $attributes = []): Product
    {
        return Product::create([
            'product_name' => 'iPhone 16 Pro-Max',
            'slug' => 'iphone-16-pro-max',
            'category_id' => 1,
            'brand_id' => 1,
            'status' => '1',
            'is_ecommerce' => true,
            ...$attributes,
        ]);
    }
}

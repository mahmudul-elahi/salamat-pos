<?php

namespace Tests\Feature;

use App\Models\Pos\Product\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
        Http::fake(['meili.test/*' => Http::response('down', 503)]);

        $product = $this->createProduct();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    private function createProduct(): Product
    {
        return Product::create([
            'product_name' => 'iPhone 16 Pro-Max',
            'slug' => 'iphone-16-pro-max',
            'category_id' => 1,
            'brand_id' => 1,
            'status' => '1',
            'is_ecommerce' => true,
        ]);
    }
}

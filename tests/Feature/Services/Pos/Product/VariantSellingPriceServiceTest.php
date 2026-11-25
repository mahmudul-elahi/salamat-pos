<?php

namespace Tests\Feature\Services\Pos\Product;

use App\Models\Pos\Product\ProductVariant;
use App\Services\Pos\Product\VariantSellingPriceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VariantSellingPriceServiceTest extends TestCase
{
    private const int VARIANT_ID = 7224;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('variant_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_variant_id');
            $table->decimal('max_retail_price', 12, 2)->nullable();
            $table->decimal('discount_fixed', 12, 2)->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->integer('priority')->default(0);
        });

        Schema::create('offer_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offer_id');
            $table->unsignedBigInteger('product_variant_id');
            $table->string('discount_type');
            $table->decimal('discount_amount', 12, 2);
        });

        $this->travelTo('2026-11-25 10:00:00');
        DB::table('product_variants')->insert(['id' => self::VARIANT_ID]);
    }

    public function test_applies_the_variants_fixed_discount(): void
    {
        $this->setVariantPrice(['discount_fixed' => 700]);

        $this->assertSame(3299.0, $this->sellingPrice());
    }

    public function test_a_percent_discount_wins_over_a_fixed_one(): void
    {
        $this->setVariantPrice(['discount_percent' => 10, 'discount_fixed' => 700]);

        $this->assertSame(3599.1, $this->sellingPrice());
    }

    public function test_an_active_offer_wins_over_the_variants_own_discount(): void
    {
        $this->setVariantPrice(['discount_fixed' => 700]);
        $this->addOffer(['start_at' => '2026-11-01 00:00:00', 'end_at' => '2026-11-30 00:00:00'], 'percentage', 20);

        $this->assertSame(3199.2, $this->sellingPrice());
    }

    public function test_ignores_an_expired_offer(): void
    {
        $this->setVariantPrice(['discount_fixed' => 700]);
        $this->addOffer(['start_at' => '2026-09-28 10:53:00', 'end_at' => '2026-11-10 12:00:00'], 'fixed', 1000);

        $this->assertSame(3299.0, $this->sellingPrice());
    }

    public function test_returns_the_regular_price_when_there_is_no_discount(): void
    {
        $this->setVariantPrice([]);

        $this->assertSame(3999.0, $this->sellingPrice());
    }

    /**
     * @param  array<string, mixed>  $discount
     */
    private function setVariantPrice(array $discount): void
    {
        DB::table('variant_prices')->insert(['product_variant_id' => self::VARIANT_ID, 'max_retail_price' => 3999, ...$discount]);
    }

    /**
     * @param  array{start_at: string, end_at: string}  $period
     */
    private function addOffer(array $period, string $discountType, float $discountAmount): void
    {
        $offerId = DB::table('offers')->insertGetId(['is_active' => true, ...$period]);
        DB::table('offer_products')->insert([
            'offer_id' => $offerId,
            'product_variant_id' => self::VARIANT_ID,
            'discount_type' => $discountType,
            'discount_amount' => $discountAmount,
        ]);
    }

    private function sellingPrice(): float
    {
        $variants = ProductVariant::query()->with('price')->get();

        return app(VariantSellingPriceService::class)->forVariants($variants)[self::VARIANT_ID];
    }
}

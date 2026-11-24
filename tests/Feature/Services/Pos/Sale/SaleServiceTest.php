<?php

namespace Tests\Feature\Services\Pos\Sale;

use App\Services\Pos\Sale\SaleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    private const int TENANT_ID = 7;

    private const string SERIAL_NUMBER = '1351812542000441';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number');
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('status')->nullable();
            $table->decimal('max_retail_price', 12, 2)->default(0);
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_product_serials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_serial_id');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('sale_product_temps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id');
            $table->string('serial_number');
            $table->unsignedBigInteger('tenant_id');
            $table->timestamps();
        });
    }

    public function test_product_sale_of_an_in_stock_serial_sells_it_from_stock_without_a_backorder(): void
    {
        DB::table('product_serials')->insert(['id' => 1, 'serial_number' => self::SERIAL_NUMBER, 'product_variant_id' => 1631, 'product_id' => 366]);
        DB::table('tenant_product_serials')->insert(['product_serial_id' => 1, 'tenant_id' => self::TENANT_ID, 'status' => 'purchase']);

        app(SaleService::class)->tenantProductSerialLog($this->saleRequest(), true);

        $this->assertDatabaseHas('tenant_product_serials', ['product_serial_id' => 1, 'tenant_id' => self::TENANT_ID, 'status' => 'sale']);
        $this->assertDatabaseCount('sale_product_temps', 0);
        $this->assertDatabaseCount('product_serials', 1);
    }

    public function test_product_sale_of_a_serial_not_in_stock_creates_a_backorder(): void
    {
        app(SaleService::class)->tenantProductSerialLog($this->saleRequest(), true);

        $this->assertDatabaseHas('product_serials', ['serial_number' => self::SERIAL_NUMBER, 'status' => 'generated']);
        $this->assertDatabaseHas('sale_product_temps', ['serial_number' => self::SERIAL_NUMBER, 'tenant_id' => self::TENANT_ID]);
        $this->assertDatabaseCount('tenant_product_serials', 0);
    }

    /**
     * @return array{tenant_id: int, items: array<int, array<string, mixed>>}
     */
    private function saleRequest(): array
    {
        return [
            'tenant_id' => self::TENANT_ID,
            'items' => [[
                'product_id' => 366,
                'variant_id' => 1631,
                'serial_number' => self::SERIAL_NUMBER,
                'unit_price' => '5999.00',
                'sale_type' => 'product',
            ]],
        ];
    }
}

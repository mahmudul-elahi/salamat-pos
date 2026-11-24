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

    public function test_in_stock_product_item_is_converted_to_a_regular_sale(): void
    {
        $this->putSerialInStock(self::TENANT_ID);

        $items = app(SaleService::class)->convertInStockProductItemsToRegular($this->saleRequest()['items'], self::TENANT_ID, true);

        $this->assertNull($items[0]['sale_type']);
    }

    public function test_product_item_stays_a_backorder_when_its_serial_is_only_in_another_tenants_stock(): void
    {
        $this->putSerialInStock(8);

        $items = app(SaleService::class)->convertInStockProductItemsToRegular($this->saleRequest()['items'], self::TENANT_ID, true);

        $this->assertSame('product', $items[0]['sale_type']);
    }

    public function test_exchange_item_is_not_converted_even_when_its_serial_is_in_stock(): void
    {
        $this->putSerialInStock(self::TENANT_ID);
        $items = $this->saleRequest()['items'];
        $items[0]['sale_type'] = 'exchange';

        $items = app(SaleService::class)->convertInStockProductItemsToRegular($items, self::TENANT_ID, true);

        $this->assertSame('exchange', $items[0]['sale_type']);
    }

    public function test_converted_in_stock_item_marks_its_serial_sold_without_a_backorder(): void
    {
        $this->putSerialInStock(self::TENANT_ID);
        $service = app(SaleService::class);
        $request = $this->saleRequest();
        $request['items'] = $service->convertInStockProductItemsToRegular($request['items'], self::TENANT_ID, true);

        $service->tenantProductSerialLog($request, true);

        $this->assertDatabaseHas('tenant_product_serials', ['product_serial_id' => 1, 'tenant_id' => self::TENANT_ID, 'status' => 'sale']);
        $this->assertDatabaseCount('sale_product_temps', 0);
    }

    public function test_product_sale_of_a_serial_not_in_stock_creates_a_backorder(): void
    {
        app(SaleService::class)->tenantProductSerialLog($this->saleRequest(), true);

        $this->assertDatabaseHas('product_serials', ['serial_number' => self::SERIAL_NUMBER, 'status' => 'generated']);
        $this->assertDatabaseHas('sale_product_temps', ['serial_number' => self::SERIAL_NUMBER, 'tenant_id' => self::TENANT_ID]);
        $this->assertDatabaseCount('tenant_product_serials', 0);
    }

    private function putSerialInStock(int $tenantId): void
    {
        DB::table('product_serials')->insert(['id' => 1, 'serial_number' => self::SERIAL_NUMBER, 'product_variant_id' => 1631, 'product_id' => 366]);
        DB::table('tenant_product_serials')->insert(['product_serial_id' => 1, 'tenant_id' => $tenantId, 'status' => 'purchase']);
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

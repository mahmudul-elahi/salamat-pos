<?php

namespace Tests\Feature;

use App\Http\Controllers\Pos\Sale\SaleController;
use App\Http\Controllers\Pos\Sale\SaleDownloadController;
use App\Models\Pos\Care\Care;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductCare;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Pos\Sale\Sale;
use App\Services\Pos\Sale\SaleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SaleCareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('business_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('new_district_id')->nullable();
            $table->unsignedBigInteger('area_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('product_name');
            $table->string('product_image')->nullable();
            $table->integer('warranty_day')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('sku')->nullable();
            $table->string('status')->default('1');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_id')->nullable();
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('variant_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('attribute_value_id');
            $table->timestamps();
        });

        Schema::create('cares', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('value', 8, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_cares', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('care_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_serials', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->string('serial_number');
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('new_district_id')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('seller_id')->nullable();
            $table->string('address')->nullable();
            $table->date('date');
            $table->integer('invoice_number')->nullable();
            $table->string('full_invoice_number')->nullable();
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->decimal('order_tax', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->string('additional_charge_name')->nullable();
            $table->decimal('additional_charge', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('status')->default('due');
            $table->string('coupon_code')->nullable();
            $table->decimal('coupon_amount', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->string('serial_number')->nullable();
            $table->string('sale_type')->default('regular');
            $table->string('discount_type')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->uuid('uuid')->nullable();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable();
            $table->timestamps();
        });
    }

    public function test_sale_care_is_persisted_in_note_and_additional_charge_without_db_changes(): void
    {
        $contact = Contact::query()->create(['name' => 'Test Customer']);
        $product = Product::query()->create(['product_name' => 'iPhone 16 Pro']);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'IP16P-256',
            'status' => '1',
        ]);
        $care = Care::query()->create([
            'name' => 'TEKZO Care for Apple',
            'value' => 10.00,
        ]);
        ProductCare::query()->create([
            'product_id' => $product->id,
            'care_id' => $care->id,
        ]);

        $saleService = app(SaleService::class);
        $saleService->create([
            'contact_id' => $contact->id,
            'date' => '2026-11-26',
            'sub_total' => 100000,
            'order_tax' => 0,
            'shipping_cost' => 0,
            'additional_charge_name' => 'TEKZO Care for Apple',
            'additional_charge' => 10000,
            'grand_total' => 110000,
        ])->createItem([
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                    'unit_price' => 100000,
                    'serial_number' => 'SN-CARE-001',
                    'discount_type' => 'fixed',
                    'discount_amount' => 0,
                    'note' => 'Product Care: TEKZO Care for Apple (৳10,000.00)',
                ],
            ],
        ]);

        $sale = Sale::query()->withoutGlobalScopes()->findOrFail($saleService->getModelId());
        $sale->update(['invoice_number' => 1, 'full_invoice_number' => 'PS-000001']);

        $invoiceResponse = app(SaleDownloadController::class)->invoiceData($sale)->getData(true);

        $this->assertEquals(10000.00, (float) $invoiceResponse['sale']['additional_charge']);
        $this->assertSame('TEKZO Care for Apple', $invoiceResponse['sale']['additional_charge_name']);
        $this->assertSame(['Product Care: TEKZO Care for Apple (৳10,000.00)'], $invoiceResponse['items'][0]['notes']);

        $showData = app(SaleController::class)->show($sale);

        $this->assertSame('Product Care: TEKZO Care for Apple (৳10,000.00)', $showData['items'][0]['note']);
        $this->assertCount(1, $showData['items'][0]['cares']);
    }
}

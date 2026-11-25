<?php

namespace Tests\Feature\Http\Controllers\Pos\OrderConfirmation;

use App\Enums\StatusEnum;
use App\Http\Middleware\AuthorizeMiddleware;
use App\Http\Middleware\EnsureUserBelongsToTenant;
use App\Http\Middleware\PermissionMiddleware;
use App\Models\Ecommerce\Order\Order;
use App\Models\Pos\OrderConfirmation\OrderConfirmation;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderConfirmationControllerTest extends TestCase
{
    private const int CONTACT_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            Authenticate::class,
            AuthorizeMiddleware::class,
            PermissionMiddleware::class,
            EnsureUserBelongsToTenant::class,
        ]);

        Schema::dropAllTables();

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->softDeletes();
        });

        Schema::create('order_confirmations', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->date('confirmation_date')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('contact_id');
            $table->string('customer_name')->nullable();
            $table->string('mobile')->nullable();
            $table->text('address')->nullable();
            $table->string('source_media')->nullable();
            $table->string('order_type')->nullable();
            $table->decimal('advance_amount', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('shipping_charge', 12, 2)->default(0);
            $table->decimal('cod', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_confirmation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_confirmation_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->string('custom_product')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('address')->nullable();
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('payable_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->decimal('shipping_charge', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('invoice_number')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('custom_value')->nullable();
            $table->unsignedInteger('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->decimal('product_price', 12, 2)->default(0);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->unsignedInteger('purchase_point')->default(0);
            $table->unsignedInteger('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('variant_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_variant_id');
            $table->decimal('max_retail_price', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('contacts')->insert(['id' => self::CONTACT_ID, 'name' => 'Zinat Sharmin Anne', 'phone' => '01795201064']);
    }

    public function test_store_rejects_an_order_without_a_delivery_address_with_422(): void
    {
        $response = $this->postJson(route('order-confirmations.store'), $this->orderPayload(['address' => '']));

        $response->assertUnprocessable()->assertOnlyJsonValidationErrors(['address']);
    }

    public function test_store_raises_an_order_whose_due_is_the_total_minus_the_advance_even_when_cod_is_blank(): void
    {
        $response = $this->postJson(route('order-confirmations.store'), $this->orderPayload(['advance_amount' => 120, 'shipping_charge' => 70]));

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'grand_total' => 3440,
            'payable_amount' => 120,
            'due_amount' => 3320,
            'status' => StatusEnum::Confirmed->value,
        ]);
    }

    public function test_store_records_a_catalogue_line_at_its_regular_price_with_the_agreed_discount(): void
    {
        DB::table('products')->insert(['id' => 1]);
        DB::table('product_variants')->insert(['id' => 1, 'product_id' => 1]);
        DB::table('variant_prices')->insert(['product_variant_id' => 1, 'max_retail_price' => 3999]);

        $response = $this->postJson(route('order-confirmations.store'), $this->orderPayload([
            'shipping_charge' => 70,
            'items' => [['product_id' => 1, 'product_variant_id' => 1, 'quantity' => 1, 'unit_price' => 3300]],
        ]));

        $response->assertOk();
        $this->assertDatabaseHas('order_lists', ['product_price' => 3999, 'discount_type' => 'fixed', 'discount_amount' => 699]);
        $this->assertDatabaseHas('orders', ['grand_total' => 3370, 'due_amount' => 3370]);
    }

    public function test_store_records_no_discount_when_the_agreed_price_is_not_below_the_regular_price(): void
    {
        DB::table('products')->insert(['id' => 1]);
        DB::table('product_variants')->insert(['id' => 1, 'product_id' => 1]);

        $response = $this->postJson(route('order-confirmations.store'), $this->orderPayload([
            'items' => [['product_id' => 1, 'product_variant_id' => 1, 'quantity' => 2, 'unit_price' => 1500]],
        ]));

        $response->assertOk();
        $this->assertDatabaseHas('order_lists', ['product_price' => 1500, 'discount_type' => null, 'discount_amount' => 0]);
    }

    public function test_update_carries_new_totals_and_address_over_to_a_confirmed_order(): void
    {
        $orderConfirmation = $this->storeOrderConfirmation();

        $response = $this->patchJson(
            route('order-confirmations.update', $orderConfirmation),
            $this->orderPayload(['shipping_charge' => 70, 'address' => 'House 12, Uttara, Dhaka']),
        );

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $orderConfirmation->order_id,
            'address' => 'House 12, Uttara, Dhaka',
            'shipping_charge' => 70,
            'grand_total' => 3440,
            'due_amount' => 3440,
        ]);
    }

    public function test_update_leaves_an_order_alone_once_it_has_been_approved(): void
    {
        $orderConfirmation = $this->storeOrderConfirmation();
        Order::query()->whereKey($orderConfirmation->order_id)->update(['status' => StatusEnum::Approved->value]);

        $response = $this->patchJson(
            route('order-confirmations.update', $orderConfirmation),
            $this->orderPayload(['shipping_charge' => 70]),
        );

        $response->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $orderConfirmation->order_id, 'grand_total' => 3370, 'due_amount' => 3370]);
    }

    private function storeOrderConfirmation(): OrderConfirmation
    {
        $this->postJson(route('order-confirmations.store'), $this->orderPayload([]))->assertOk();

        return OrderConfirmation::query()->sole();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function orderPayload(array $overrides): array
    {
        return [
            'date' => '2026-11-24',
            'contact_id' => self::CONTACT_ID,
            'address' => 'Road 5, Mirpur 1, Dhaka',
            'items' => [['custom_product' => 'Power bank', 'quantity' => 1, 'unit_price' => 3370]],
            ...$overrides,
        ];
    }
}

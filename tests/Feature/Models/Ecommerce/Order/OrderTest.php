<?php

namespace Tests\Feature\Models\Ecommerce\Order;

use App\Models\Ecommerce\Order\Order;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderTest extends TestCase
{
    private const int GAZIPUR_ID = 1;

    private const int GAZIPUR_SADAR_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('new_districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('thana')->nullable();
            $table->string('postcode')->nullable();
            $table->unsignedBigInteger('new_district_id')->nullable();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('street')->nullable();
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('new_district_id')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('new_district_id')->nullable();
            $table->unsignedBigInteger('shipping_address_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('new_districts')->insert(['id' => self::GAZIPUR_ID, 'name' => 'Gazipur']);
        DB::table('areas')->insert(['id' => self::GAZIPUR_SADAR_ID, 'name' => 'Gazipur Sadar', 'thana' => 'Joydebpur', 'postcode' => '1703', 'new_district_id' => self::GAZIPUR_ID]);
    }

    public function test_delivery_address_uses_the_saved_shipping_address_picked_at_checkout(): void
    {
        $shippingAddressId = DB::table('customer_addresses')->insertGetId(['street' => 'House 12', 'area_id' => self::GAZIPUR_SADAR_ID, 'new_district_id' => self::GAZIPUR_ID]);
        $order = $this->createOrder(['address' => 'Typed on order', 'shipping_address_id' => $shippingAddressId]);

        $this->assertSame('House 12, Gazipur Sadar, Joydebpur, Gazipur, 1703', $order->delivery_address);
    }

    public function test_delivery_address_joins_the_typed_address_with_area_thana_district_and_postcode(): void
    {
        $order = $this->createOrder(['address' => 'House 12', 'area_id' => self::GAZIPUR_SADAR_ID]);

        $this->assertSame('House 12, Gazipur Sadar, Joydebpur, Gazipur, 1703', $order->delivery_address);
    }

    public function test_delivery_address_does_not_repeat_parts_typed_in_the_address(): void
    {
        $order = $this->createOrder(['address' => 'House 12, Joydebpur, Gazipur-1703', 'area_id' => self::GAZIPUR_SADAR_ID]);

        $this->assertSame('House 12, Joydebpur, Gazipur-1703, Gazipur Sadar', $order->delivery_address);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createOrder(array $attributes): Order
    {
        $id = DB::table('orders')->insertGetId($attributes);

        return Order::query()->findOrFail($id);
    }
}

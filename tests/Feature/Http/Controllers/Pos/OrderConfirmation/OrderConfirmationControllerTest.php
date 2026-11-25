<?php

namespace Tests\Feature\Http\Controllers\Pos\OrderConfirmation;

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

        $this->withoutMiddleware();

        Schema::dropAllTables();

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        DB::table('contacts')->insert(['id' => self::CONTACT_ID, 'name' => 'ShareTrip']);
    }

    public function test_store_rejects_an_order_without_a_delivery_address_with_422(): void
    {
        $response = $this->postJson(route('order-confirmations.store'), $this->orderPayload(['address' => '']));

        $response->assertUnprocessable()->assertOnlyJsonValidationErrors(['address']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function orderPayload(array $overrides): array
    {
        return [
            'date' => '2026-11-25',
            'contact_id' => self::CONTACT_ID,
            'items' => [['custom_product' => 'Power bank', 'quantity' => 1, 'unit_price' => 2600]],
            ...$overrides,
        ];
    }
}

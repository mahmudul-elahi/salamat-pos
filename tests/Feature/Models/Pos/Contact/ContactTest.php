<?php

namespace Tests\Feature\Models\Pos\Contact;

use App\Models\Pos\Contact\Contact;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContactTest extends TestCase
{
    private const int DHAKA_ID = 1;

    private const int MIRPUR_ID = 1;

    private const int BANGLADESH_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

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

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('new_district_id')->nullable();
            $table->string('price_document')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('street')->nullable();
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('new_district_id')->nullable();
            $table->boolean('is_default_shipping')->default(false);
            $table->timestamps();
        });

        DB::table('countries')->insert(['id' => self::BANGLADESH_ID, 'name' => 'Bangladesh']);
        DB::table('new_districts')->insert(['id' => self::DHAKA_ID, 'name' => 'Dhaka']);
        DB::table('areas')->insert(['id' => self::MIRPUR_ID, 'name' => 'Mirpur 1', 'thana' => 'Mirpur Model', 'postcode' => '1216', 'new_district_id' => self::DHAKA_ID]);
    }

    public function test_invoice_address_lists_all_six_customer_address_fields_in_order(): void
    {
        $contact = $this->createContact(['address' => 'Shop C-39/B', 'area_id' => self::MIRPUR_ID, 'new_district_id' => self::DHAKA_ID, 'country_id' => self::BANGLADESH_ID]);

        $this->assertSame('Shop C-39/B, Mirpur 1, Mirpur Model, Dhaka, 1216, Bangladesh', $contact->invoice_address);
    }

    public function test_invoice_address_does_not_repeat_area_or_district_typed_in_the_street(): void
    {
        $contact = $this->createContact(['address' => 'Shop C-39/B, Mirpur 1, Mirpur Model, Dhaka-1216', 'area_id' => self::MIRPUR_ID, 'new_district_id' => self::DHAKA_ID]);

        $this->assertSame('Shop C-39/B, Mirpur 1, Mirpur Model, Dhaka-1216', $contact->invoice_address);
    }

    public function test_invoice_address_falls_back_to_the_default_saved_shipping_address(): void
    {
        $contact = $this->createContact();
        DB::table('customer_addresses')->insert([
            ['contact_id' => $contact->id, 'street' => 'Default house', 'area_id' => self::MIRPUR_ID, 'is_default_shipping' => true],
            ['contact_id' => $contact->id, 'street' => 'Newer office', 'area_id' => null, 'is_default_shipping' => false],
        ]);

        $this->assertSame('Default house, Mirpur 1, Mirpur Model, Dhaka, 1216', $contact->invoice_address);
    }

    public function test_invoice_address_falls_back_to_the_latest_saved_address_when_none_is_default(): void
    {
        $contact = $this->createContact();
        DB::table('customer_addresses')->insert([
            ['contact_id' => $contact->id, 'street' => 'Older house', 'is_default_shipping' => false],
            ['contact_id' => $contact->id, 'street' => 'Newer office', 'is_default_shipping' => false],
        ]);

        $this->assertSame('Newer office', $contact->invoice_address);
    }

    public function test_invoice_address_prefers_the_contacts_own_address_over_saved_addresses(): void
    {
        $contact = $this->createContact(['address' => 'Counter address']);
        DB::table('customer_addresses')->insert(['contact_id' => $contact->id, 'street' => 'Saved house', 'is_default_shipping' => true]);

        $this->assertSame('Counter address', $contact->invoice_address);
    }

    public function test_invoice_address_is_empty_when_the_contact_has_no_address_anywhere(): void
    {
        $contact = $this->createContact();

        $this->assertSame('', $contact->invoice_address);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createContact(array $attributes = []): Contact
    {
        $id = DB::table('contacts')->insertGetId(['name' => 'ShareTrip', ...$attributes]);

        return Contact::query()->findOrFail($id);
    }
}

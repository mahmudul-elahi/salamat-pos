<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthorizeMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Models\Ecommerce\Outlet\Outlet;
use App\Models\Ecommerce\Outlet\OutletImage;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OutletApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address', 500)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('division', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 10)->default('BD');
            $table->string('google_map_url', 500)->nullable();
            $table->string('image', 500)->nullable();
            $table->time('opens');
            $table->time('closes');
            $table->string('close_day');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('outlet_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->string('image_url', 500);
            $table->string('type')->default('gallery');
            $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $this->withoutMiddleware([
            Authenticate::class,
            AuthorizeMiddleware::class,
            PermissionMiddleware::class,
        ]);
    }

    public function test_it_can_create_an_outlet_with_images(): void
    {
        $payload = [
            'name' => 'Banani Outlet',
            'slug' => 'banani-outlet',
            'description' => 'Main branch',
            'phone' => '01700000000',
            'email' => 'banani@example.com',
            'address' => 'Road 11, Banani',
            'city' => 'Dhaka',
            'division' => 'Dhaka',
            'postal_code' => '1213',
            'country' => 'BD',
            'google_map_url' => 'https://maps.google.com/?q=banani',
            'image' => 'https://cdn.test/outlets/cover.jpg',
            'opens' => '09:00',
            'closes' => '21:00',
            'close_day' => 'friday',
            'status' => true,
            'images' => [
                [
                    'image_url' => 'https://cdn.test/outlets/banner.jpg',
                    'type' => 'banner',
                    'alt_text' => 'Banner image',
                    'sort_order' => 1,
                ],
                [
                    'image_url' => 'https://cdn.test/outlets/gallery.jpg',
                    'type' => 'gallery',
                    'alt_text' => 'Gallery image',
                    'sort_order' => 2,
                ],
            ],
        ];

        $response = $this->postJson('/api/admin/ecommerce/outlets', $payload);

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Outlet created successfully']);

        $this->assertDatabaseHas('outlets', [
            'name' => 'Banani Outlet',
            'slug' => 'banani-outlet',
            'close_day' => 'friday',
        ]);

        $outlet = Outlet::query()->where('slug', 'banani-outlet')->firstOrFail();

        $this->assertCount(2, $outlet->images);
        $this->assertDatabaseHas('outlet_images', [
            'outlet_id' => $outlet->id,
            'type' => 'banner',
            'sort_order' => 1,
        ]);
    }

    public function test_it_can_update_and_delete_an_outlet(): void
    {

        $outlet = Outlet::query()->create([
            'name' => 'Initial Outlet',
            'slug' => 'initial-outlet',
            'description' => 'Initial description',
            'phone' => '01711111111',
            'email' => 'initial@example.com',
            'address' => 'Initial address',
            'city' => 'Dhaka',
            'division' => 'Dhaka',
            'postal_code' => '1200',
            'country' => 'BD',
            'google_map_url' => 'https://maps.google.com/?q=initial',
            'image' => 'https://cdn.test/outlets/initial.jpg',
            'opens' => '10:00',
            'closes' => '20:00',
            'close_day' => 'sunday',
            'status' => true,
        ]);

        OutletImage::query()->create([
            'outlet_id' => $outlet->id,
            'image_url' => 'https://cdn.test/outlets/old-gallery.jpg',
            'type' => 'gallery',
            'alt_text' => 'Old image',
            'sort_order' => 1,
        ]);

        $updatePayload = [
            'name' => 'Updated Outlet',
            'slug' => 'updated-outlet',
            'description' => 'Updated description',
            'phone' => '01822222222',
            'email' => 'updated@example.com',
            'address' => 'Updated address',
            'city' => 'Chattogram',
            'division' => 'Chattogram',
            'postal_code' => '4000',
            'country' => 'BD',
            'google_map_url' => 'https://maps.google.com/?q=updated',
            'image' => 'https://cdn.test/outlets/updated.jpg',
            'opens' => '08:00',
            'closes' => '22:00',
            'close_day' => 'monday',
            'status' => false,
            'images' => [
                [
                    'image_url' => 'https://cdn.test/outlets/new-thumb.jpg',
                    'type' => 'thumbnail',
                    'alt_text' => 'New thumb',
                    'sort_order' => 3,
                ],
            ],
        ];

        $updateResponse = $this->putJson("/api/admin/ecommerce/outlets/{$outlet->id}", $updatePayload);

        $updateResponse->assertOk()
            ->assertJsonFragment(['message' => 'Outlet updated successfully']);

        $this->assertDatabaseHas('outlets', [
            'id' => $outlet->id,
            'name' => 'Updated Outlet',
            'slug' => 'updated-outlet',
            'status' => 0,
            'close_day' => 'monday',
        ]);

        $this->assertDatabaseMissing('outlet_images', [
            'outlet_id' => $outlet->id,
            'image_url' => 'https://cdn.test/outlets/old-gallery.jpg',
        ]);

        $this->assertDatabaseHas('outlet_images', [
            'outlet_id' => $outlet->id,
            'image_url' => 'https://cdn.test/outlets/new-thumb.jpg',
            'type' => 'thumbnail',
        ]);

        $deleteResponse = $this->deleteJson("/api/admin/ecommerce/outlets/{$outlet->id}");

        $deleteResponse->assertOk()
            ->assertJsonFragment(['message' => 'Outlet deleted successfully']);

        $this->assertDatabaseMissing('outlets', [
            'id' => $outlet->id,
        ]);
        $this->assertDatabaseMissing('outlet_images', [
            'outlet_id' => $outlet->id,
        ]);
    }
}

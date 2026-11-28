<?php

namespace Tests\Feature\Http\Controllers\Pos\AiAssistant;

use App\Models\Permission\Permission;
use App\Models\Role\Role;
use App\Models\User;
use App\Services\AiAssistant\AiAssistant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiAssistantControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        Schema::dropAllTables();
        $this->createAccessTables();
        $this->createCatalogueTables();

        (require database_path('migrations/2026_11_28_080717_create_agent_conversations_table.php'))->up();
    }

    public function test_a_guest_cannot_use_the_assistant(): void
    {
        $this->postJson(route('ai_assistant.messages'), ['message' => 'Hello'])->assertUnauthorized();
    }

    public function test_a_message_starts_a_conversation_and_returns_the_reply(): void
    {
        AiAssistant::fake(['You have 3 products.']);
        Sanctum::actingAs($this->userWithPermissions([]));

        $response = $this->postJson(route('ai_assistant.messages'), ['message' => 'How many products do I have?'])
            ->assertOk()
            ->assertJsonPath('reply', 'You have 3 products.')
            ->assertJsonPath('pending_approvals', []);

        $this->getJson(route('ai_assistant.conversations.show', $response->json('conversation_id')))
            ->assertOk()
            ->assertJsonPath('messages.0.content', 'How many products do I have?')
            ->assertJsonPath('messages.1.content', 'You have 3 products.');
    }

    public function test_a_user_cannot_read_or_continue_another_users_conversation(): void
    {
        AiAssistant::fake(['Hi there.']);
        Sanctum::actingAs($this->userWithPermissions([]));
        $conversationId = $this->postJson(route('ai_assistant.messages'), ['message' => 'Hi'])->json('conversation_id');

        Sanctum::actingAs($this->userWithPermissions([]));

        $this->getJson(route('ai_assistant.conversations.show', $conversationId))->assertNotFound();
        $this->postJson(route('ai_assistant.messages'), ['message' => 'Hi', 'conversation_id' => $conversationId])
            ->assertNotFound();
        $this->postJson(route('ai_assistant.approvals', $conversationId), ['decisions' => [['id' => 'call_1', 'approved' => true]]])
            ->assertNotFound();
    }

    public function test_creating_a_category_waits_for_approval_and_runs_once_approved(): void
    {
        AiAssistant::fake([
            new ToolCall('call_category', 'create_category', ['name' => 'Smart Watches']),
            'Smart Watches has been created.',
        ]);
        Sanctum::actingAs($this->userWithPermissions(['create_categories']));

        $conversationId = $this->postJson(route('ai_assistant.messages'), ['message' => 'Create a Smart Watches category'])
            ->assertOk()
            ->assertJsonPath('pending_approvals.0.id', 'call_category')
            ->assertJsonPath('pending_approvals.0.tool', 'create_category')
            ->assertJsonPath('pending_approvals.0.summary', 'Create the category "Smart Watches".')
            ->json('conversation_id');

        $this->assertDatabaseMissing('categories', ['name' => 'Smart Watches']);

        $this->postJson(route('ai_assistant.messages'), ['message' => 'Anything else?', 'conversation_id' => $conversationId])
            ->assertConflict();

        $this->postJson(route('ai_assistant.approvals', $conversationId), [
            'decisions' => [['id' => 'call_category', 'approved' => true]],
        ])
            ->assertOk()
            ->assertJsonPath('reply', 'Smart Watches has been created.')
            ->assertJsonPath('pending_approvals', []);

        $this->assertDatabaseHas('categories', ['name' => 'Smart Watches', 'slug' => 'smart-watches', 'parent_id' => null]);
    }

    public function test_a_rejected_action_is_not_performed(): void
    {
        AiAssistant::fake([
            new ToolCall('call_category', 'create_category', ['name' => 'Smart Watches']),
            'Okay, I did not create it.',
        ]);
        Sanctum::actingAs($this->userWithPermissions(['create_categories']));

        $conversationId = $this->postJson(route('ai_assistant.messages'), ['message' => 'Create a Smart Watches category'])
            ->json('conversation_id');

        $this->postJson(route('ai_assistant.approvals', $conversationId), [
            'decisions' => [['id' => 'call_category', 'approved' => false]],
        ])->assertOk();

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_approvals_must_answer_exactly_the_pending_actions(): void
    {
        AiAssistant::fake([new ToolCall('call_category', 'create_category', ['name' => 'Smart Watches'])]);
        Sanctum::actingAs($this->userWithPermissions(['create_categories']));

        $conversationId = $this->postJson(route('ai_assistant.messages'), ['message' => 'Create a Smart Watches category'])
            ->json('conversation_id');

        $this->postJson(route('ai_assistant.approvals', $conversationId), [
            'decisions' => [['id' => 'call_somebody_else', 'approved' => true]],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_a_user_without_permission_is_refused_without_being_asked_to_approve(): void
    {
        AiAssistant::fake([
            new ToolCall('call_category', 'create_category', ['name' => 'Smart Watches']),
            'You need the create_categories permission.',
        ]);
        Sanctum::actingAs($this->userWithPermissions(['view_categories']));

        $this->postJson(route('ai_assistant.messages'), ['message' => 'Create a Smart Watches category'])
            ->assertOk()
            ->assertJsonPath('pending_approvals', [])
            ->assertJsonPath('reply', 'You need the create_categories permission.');

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_reading_products_needs_the_view_products_permission(): void
    {
        DB::table('products')->insert(['product_name' => 'Pixel 10', 'category_id' => 1, 'brand_id' => 1]);

        AiAssistant::fake([
            new ToolCall('call_search', 'search_products', ['search' => 'Pixel']),
            'Done.',
        ]);

        Sanctum::actingAs($this->userWithPermissions([]));
        $this->postJson(route('ai_assistant.messages'), ['message' => 'Find Pixel'])->assertOk();

        $conversationId = DB::table('agent_conversations')->value('id');
        $toolResult = $this->toolResultFor($conversationId, 'call_search');

        $this->assertStringContainsString('permission_denied', $toolResult);
        $this->assertStringNotContainsString('Pixel 10', $toolResult);
    }

    public function test_an_app_admin_can_use_every_tool_without_individual_permissions(): void
    {
        AiAssistant::fake([
            new ToolCall('call_category', 'create_category', ['name' => 'Smart Watches']),
            'Approve to continue.',
        ]);
        Sanctum::actingAs($this->appAdmin());

        $this->postJson(route('ai_assistant.messages'), ['message' => 'Create a Smart Watches category'])
            ->assertOk()
            ->assertJsonPath('pending_approvals.0.id', 'call_category');
    }

    public function test_creating_a_product_builds_its_variants_from_attribute_values(): void
    {
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Phones', 'slug' => 'phones']);
        $brandId = DB::table('brands')->insertGetId(['name' => 'Google', 'slug' => 'google']);
        $colorId = DB::table('attributes')->insertGetId(['name' => 'Color', 'status' => 1]);
        $storageId = DB::table('attributes')->insertGetId(['name' => 'Storage', 'status' => 1]);
        $black = DB::table('attribute_values')->insertGetId(['attribute_id' => $colorId, 'value' => 'Black', 'status' => 1]);
        $gb128 = DB::table('attribute_values')->insertGetId(['attribute_id' => $storageId, 'value' => '128GB', 'status' => 1]);
        $gb256 = DB::table('attribute_values')->insertGetId(['attribute_id' => $storageId, 'value' => '256GB', 'status' => 1]);

        AiAssistant::fake([
            new ToolCall('call_product', 'create_product', [
                'name' => 'Pixel 10',
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'variants' => [
                    ['attribute_value_ids' => [$black, $gb128], 'sku' => 'PX10-BLK-128', 'max_retail_price' => 90000],
                    ['attribute_value_ids' => [$black, $gb256], 'sku' => 'PX10-BLK-256', 'max_retail_price' => 99000, 'in_stock' => false],
                ],
            ]),
            'Pixel 10 has been created.',
        ]);
        Sanctum::actingAs($this->userWithPermissions(['create_products']));

        $conversationId = $this->postJson(route('ai_assistant.messages'), ['message' => 'Create Pixel 10'])
            ->assertOk()
            ->assertJsonPath('pending_approvals.0.summary', 'Create the product "Pixel 10" (Google, in Phones) with 2 variants: PX10-BLK-128, PX10-BLK-256.')
            ->json('conversation_id');

        $this->postJson(route('ai_assistant.approvals', $conversationId), [
            'decisions' => [['id' => 'call_product', 'approved' => true]],
        ])->assertOk();

        $productId = DB::table('products')->where('product_name', 'Pixel 10')->value('id');
        $this->assertNotNull($productId);
        $this->assertDatabaseHas('product_attributes', ['product_id' => $productId, 'attribute_id' => $colorId]);
        $this->assertDatabaseHas('product_attributes', ['product_id' => $productId, 'attribute_id' => $storageId]);
        $this->assertDatabaseHas('product_variants', ['product_id' => $productId, 'sku' => 'PX10-BLK-128', 'stock' => 1]);
        $this->assertDatabaseHas('product_variants', ['product_id' => $productId, 'sku' => 'PX10-BLK-256', 'stock' => 0]);

        $variantId = DB::table('product_variants')->where('sku', 'PX10-BLK-256')->value('id');
        $this->assertDatabaseHas('variant_prices', ['product_variant_id' => $variantId, 'max_retail_price' => 99000]);
        $this->assertDatabaseHas('variant_attribute_values', ['product_variant_id' => $variantId, 'attribute_value_id' => $gb256]);
        $this->assertDatabaseHas('variant_attribute_values', ['product_variant_id' => $variantId, 'attribute_value_id' => $black]);
    }

    public function test_a_product_with_an_existing_sku_is_refused_before_approval(): void
    {
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Phones', 'slug' => 'phones']);
        $brandId = DB::table('brands')->insertGetId(['name' => 'Google', 'slug' => 'google']);
        $colorId = DB::table('attributes')->insertGetId(['name' => 'Color', 'status' => 1]);
        $black = DB::table('attribute_values')->insertGetId(['attribute_id' => $colorId, 'value' => 'Black', 'status' => 1]);
        DB::table('product_variants')->insert(['product_id' => 99, 'sku' => 'PX10-BLK', 'stock' => 1]);

        AiAssistant::fake([
            new ToolCall('call_product', 'create_product', [
                'name' => 'Pixel 10',
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'variants' => [['attribute_value_ids' => [$black], 'sku' => 'PX10-BLK', 'max_retail_price' => 90000]],
            ]),
            'That SKU is already used.',
        ]);
        Sanctum::actingAs($this->userWithPermissions(['create_products']));

        $this->postJson(route('ai_assistant.messages'), ['message' => 'Create Pixel 10'])
            ->assertOk()
            ->assertJsonPath('pending_approvals', []);

        $this->assertDatabaseMissing('products', ['product_name' => 'Pixel 10']);
        $this->assertStringContainsString(
            'validation_failed',
            $this->toolResultFor(DB::table('agent_conversations')->value('id'), 'call_product'),
        );
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::query()->create([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
        ]);

        $role = Role::query()->forceCreate(['name' => 'Staff '.$user->id, 'is_admin' => 0, 'is_default' => 0]);
        $role->permissions()->attach(collect($permissions)->map(
            fn (string $name): int => Permission::query()->firstOrCreate(['name' => $name], ['group_name' => 'test'])->id,
        )->all());
        $user->roles()->attach($role);

        return $user;
    }

    private function appAdmin(): User
    {
        $user = $this->userWithPermissions([]);
        $user->roles()->attach(Role::query()->forceCreate(['name' => 'App Admin', 'is_admin' => 1, 'is_default' => 1]));

        return $user;
    }

    private function toolResultFor(string $conversationId, string $toolCallId): string
    {
        $toolCall = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->where('role', 'assistant')
            ->pluck('steps')
            ->flatMap(fn (string $steps): array => json_decode($steps, true))
            ->flatMap(fn (array $step): array => $step['tool_calls'] ?? [])
            ->firstWhere('id', $toolCallId);

        return json_encode($toolCall['result'] ?? null);
    }

    private function createAccessTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('alias')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label')->nullable();
            $table->string('group_name')->nullable();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id');
            $table->foreignId('user_id');
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id');
            $table->foreignId('role_id');
        });
    }

    private function createCatalogueTables(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('logo')->nullable();
            $table->string('secondary_logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('info_banner')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->default('1');
            $table->unsignedInteger('serial')->nullable();
            $table->string('tags')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('seo', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->longText('description')->nullable();
            $table->string('title')->nullable();
            $table->string('image')->nullable();
            $table->string('author')->nullable();
            $table->string('robots')->nullable();
            $table->string('canonical_url')->nullable();
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('status')->default('1');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id');
            $table->string('value');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('brand_id')->nullable();
            $table->string('product_name');
            $table->string('headline')->nullable();
            $table->string('tag')->nullable();
            $table->string('slug')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->decimal('purchase_point')->nullable();
            $table->integer('warranty_day')->nullable();
            $table->text('warranty_text')->nullable();
            $table->string('service_warranty')->nullable();
            $table->string('product_image')->nullable();
            $table->decimal('booking_amount')->nullable();
            $table->string('status')->default('1');
            $table->boolean('is_ecommerce')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->foreignId('attribute_id');
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->string('sku');
            $table->tinyInteger('stock')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('variant_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id');
            $table->decimal('dealer_price', 12)->nullable();
            $table->decimal('retail_price', 12)->nullable();
            $table->decimal('b2b_price', 12)->nullable();
            $table->decimal('max_retail_price', 12)->nullable();
            $table->decimal('discount_percent', 5)->nullable();
            $table->decimal('discount_fixed', 12)->nullable();
            $table->timestamps();
        });

        Schema::create('variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id');
            $table->foreignId('attribute_value_id');
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->foreignId('attribute_value_id')->nullable();
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('product_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->foreignId('attribute_value_id')->nullable();
            $table->string('video_link');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }
}

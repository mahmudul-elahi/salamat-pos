<?php

namespace Tests\Feature\Http\Controllers\Pos\Brand;

use App\Http\Middleware\AuthorizeMiddleware;
use App\Http\Middleware\EnsureUserBelongsToTenant;
use App\Http\Middleware\PermissionMiddleware;
use App\Models\Landlord\Brand\Brand;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BrandControllerTest extends TestCase
{
    private const string ANDROID_APP = 'https://play.google.com/store/apps/details?id=com.momsmart.lamp&pcampaignid=web_share';

    private const string IOS_APP = 'https://apps.apple.com/sa/app/momax-smart/id1474913815';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            Authenticate::class,
            AuthorizeMiddleware::class,
            PermissionMiddleware::class,
            EnsureUserBelongsToTenant::class,
        ]);

        Http::fake();

        Schema::dropAllTables();

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->string('primary_logo')->nullable();
            $table->string('status')->default('1');
            $table->unsignedInteger('order')->default(0);
            $table->string('slug', 300)->nullable();
            $table->string('tags')->nullable();
            $table->longText('description')->nullable();
            $table->string('shop_banner')->nullable();
            $table->string('secondary_logo')->nullable();
            $table->string('info_banner')->nullable();
            $table->string('android_app')->nullable();
            $table->string('ios_app')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_it_saves_app_links_when_updating_a_brand(): void
    {
        $brand = Brand::query()->create(['name' => 'Momax', 'slug' => 'momax', 'tagline' => 'Recharge for Life']);

        $this->patchJson(route('brands.update', $brand), [
            'name' => 'Momax',
            'tagline' => 'Recharge for Life',
            'android_app' => self::ANDROID_APP,
            'ios_app' => self::IOS_APP,
        ])->assertOk();

        $this->getJson(route('brands.show', $brand))
            ->assertOk()
            ->assertJsonPath('android_app', self::ANDROID_APP)
            ->assertJsonPath('ios_app', self::IOS_APP);
    }

    public function test_it_saves_app_links_when_creating_a_brand(): void
    {
        $this->postJson(route('brands.store'), [
            'name' => 'Momax',
            'android_app' => self::ANDROID_APP,
            'ios_app' => self::IOS_APP,
        ])->assertOk();

        $this->assertDatabaseHas('brands', [
            'name' => 'Momax',
            'android_app' => self::ANDROID_APP,
            'ios_app' => self::IOS_APP,
        ]);
    }
}

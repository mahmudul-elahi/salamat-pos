<?php

declare(strict_types=1);

use App\Http\Controllers\Ecommerce\SslCommerz\SslCommerzController;
use App\Http\Controllers\Landlord\Setting\General\SettingController;
use App\Http\Controllers\Pos\Chat\MetaWebhookController;
use App\Http\Controllers\Pos\Courier\PathaoController;
use App\Http\Controllers\Pos\Courier\RedxController;
use App\Http\Controllers\Pos\Courier\SteadfastController;
use App\Http\Controllers\Pos\Page\PageBlockController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Auth routes

include_route_files(__DIR__.'/auth/');

// API routes

include_route_files(__DIR__.'/common/');

// Landlord routes
Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum', 'tenant.check']], function () {

    include_route_files(__DIR__.'/landlord/');

    Broadcast::routes(['middleware' => ['auth:sanctum']]);
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum', 'tenant.check']], function () {

    include_route_files(__DIR__.'/ecommerce/');
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum', 'tenant.check']], function () {

    include_route_files(__DIR__.'/hrm/');
});

Route::get('app/public/settings', [SettingController::class, 'index'])
    ->name('settings_view');

Route::get('app/public/pages/{tenantSlug}/{pageSlug}/blocks', [PageBlockController::class, 'publicShow'])
    ->name('page_blocks_public_view');

Route::post('ipn', [SslCommerzController::class, 'ipn'])->name('sslcommerz.ipn');

Route::post('webhooks/pathao', [PathaoController::class, 'webhook'])->name('pathao.webhook');

Route::post('webhooks/steadfast', [SteadfastController::class, 'webhook'])->name('steadfast.webhook');
Route::post('webhooks/redx', [RedxController::class, 'webhook'])->name('redx.webhook');

// Meta only allows ONE registered Callback URL per App - WhatsApp, Messenger,
// and Instagram events all arrive here (see MetaWebhookController docblock).
// Named "webhooks/meta" (not "webhooks/whatsapp") since it's shared across
// all three products - re-register this URL in the Meta App dashboard for
// each product (WhatsApp, Messenger, Instagram) after this path change.

Route::get('webhooks/meta', [MetaWebhookController::class, 'verify'])->name('meta.webhook.verify');
Route::post('webhooks/meta', [MetaWebhookController::class, 'handle'])->name('meta.webhook.handle');

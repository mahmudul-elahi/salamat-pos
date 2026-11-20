<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Landlord\Setting\Shop\ShopController;
use App\Http\Controllers\Landlord\Setting\General\SettingController;
use App\Http\Controllers\Landlord\Setting\Email\EmailSettingController;
use App\Http\Controllers\Pos\Widget\WidgetController;
use App\Http\Controllers\Landlord\Setting\Ecommerce\EcommerceController;
use App\Http\Controllers\Landlord\Setting\PaymentMethod\PaymentMethodController;
use App\Http\Controllers\Landlord\Setting\Attendance\AttendanceSettingController;
use App\Http\Controllers\Landlord\Setting\Leave\LeaveSettingController;
use App\Http\Controllers\Landlord\Setting\DeliveryCharge\DeliveryChargeSettingController;
use App\Http\Controllers\Landlord\Setting\DeliveryCharge\AreaShippingChargeController;


Route::group(['prefix' => 'landlord/setting', 'middleware' => 'auth.permission'], function (Router $route) {

    $route->apiResource('payment-methods', PaymentMethodController::class);
    $route->apiResource('shops', ShopController::class);
    $route->apiResource('ecommerces', EcommerceController::class);
    $route->patch('shops/change-status/{shop}', [ShopController::class, 'updateStatus'])
        ->name('update_status.shop');
    $route->patch('ecommerces/change-status/{ecommerce}', [EcommerceController::class, 'updateStatus'])
        ->name('update_status.ecommerce');

    $route->get('settings', [SettingController::class, 'index'])
        ->name('settings.view');

    $route->post('settings', [SettingController::class, 'update'])
        ->name('settings.update');

    $route->get('shop-settings/{tenant?}', [SettingController::class, 'index'])
        ->name('shop_settings.view');

    $route->post('shop-settings/{tenant?}', [SettingController::class, 'update'])
        ->name('shop_settings.update');


    $route->get('email-settings', [EmailSettingController::class, 'index'])
        ->name('email_setting_view.landlord');

    $route->post('email-settings', [EmailSettingController::class, 'update'])
        ->name('email_setting_update.landlord');



    $route->get('leave-settings', [LeaveSettingController::class, 'index'])
        ->name('leave_settings.view');
    $route->post('leave-settings', [LeaveSettingController::class, 'update'])
        ->name('leave_settings.update');
    $route->post('leave-settings/assign', [LeaveSettingController::class, 'assign'])
        ->name('leave_settings.assign');

    $route->get('attendance-settings', [AttendanceSettingController::class, 'index'])
        ->name('attendance_settings.view');
    $route->post('attendance-settings', [AttendanceSettingController::class, 'update'])
        ->name('attendance_settings.update');

    $route->get('delivery-charge-settings', [DeliveryChargeSettingController::class, 'index'])
        ->name('delivery_charge_settings.view');
    $route->post('delivery-charge-settings', [DeliveryChargeSettingController::class, 'update'])
        ->name('delivery_charge_settings.update');

    $route->get('area-shipping-charges', [AreaShippingChargeController::class, 'index'])
        ->name('area_shipping_charges.view');
    $route->patch('area-shipping-charges/{area}', [AreaShippingChargeController::class, 'update'])
        ->name('area_shipping_charges.update');

    $route->get('policy-settings', [WidgetController::class, 'index'])->name('policy_settings.index');
    $route->post('policy-settings/update', [WidgetController::class, 'store'])->name('policy_setting_store.landlord');
    $route->post('policy-settings/update/{id}', [WidgetController::class, 'update'])->name('policy_settings.update');
});

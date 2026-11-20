<?php

declare(strict_types=1);

use App\Http\Controllers\Landlord\Role\RoleController;
use App\Http\Controllers\Landlord\Setting\Email\EmailDeliveryCheckController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'landlord/support'], function (Router $route) {

    $route->get('roles', [RoleController::class, 'index']);
    $route->get('role-without-users', [RoleController::class, 'roleWithoutUser']);
    $route->get('exist-mail-setup', [EmailDeliveryCheckController::class, 'isExists']);
    $route->post('send-test-mail', [EmailDeliveryCheckController::class, 'sendTestMail']);
    $route->get('permissions', [RoleController::class, 'permission']);

});

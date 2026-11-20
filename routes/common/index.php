<?php

use App\Http\Controllers\Common\Notification\NotificationController;
use App\Http\Controllers\Landlord\Template\TemplateController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum']], function (Router $router) {

    $router->get('template-types', [TemplateController::class, 'index'])
        ->name('template-types.index');
    $router->patch('templates/{template}', [TemplateController::class, 'update'])
        ->name('templates.update');



});


Route::group(['prefix' => 'app', 'middleware' => 'auth:sanctum'], function (Router $router) {

    $router->get('notifications', [NotificationController::class, 'index']);
    $router->patch('read-notifications/{id}', [NotificationController::class, 'markAsRead']);
    $router->patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

});



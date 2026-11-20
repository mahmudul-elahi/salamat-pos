<?php

declare(strict_types=1);

use App\Http\Controllers\Landlord\Role\DetachUserRoleController;
use App\Http\Controllers\Landlord\Role\RoleController;
use App\Http\Controllers\Landlord\Template\TemplateController;
use App\Http\Controllers\Landlord\User\UserController;
use App\Http\Controllers\Landlord\User\UserInviteController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'landlord', 'middleware' => 'auth.permission'], function (Router $router) {

    // Administrator
    $router->post('user-invite', [UserInviteController::class, 'invite'])
        ->name('users_invite.landlord')->middleware('check.email.config');

    $router->apiResource('users', UserController::class);

    $router->apiResource('roles', RoleController::class);

    $router->patch('detach-user-role/{user}', [DetachUserRoleController::class, 'detach'])
        ->name('role_detach_user.landlord');

    $router->post('attach-user-role/{role}', [DetachUserRoleController::class, 'attach'])
        ->name('role_attach_user.landlord');

    $router->get('email-template-types', [TemplateController::class, 'index'])
        ->name('view_email_template_types.landlord');

    $router->get('templates/{typeId}', [TemplateController::class, 'showTemplate'])
        ->name('show_email_template_types.landlord');

    $router->post('templates/{template}', [TemplateController::class, 'update'])
        ->name('update_email_template.landlord');
});

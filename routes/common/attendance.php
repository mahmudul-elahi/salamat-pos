<?php

declare(strict_types=1);

use App\Http\Controllers\Hrm\AttendanceController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

// Self-service punch in/out — available to any authenticated user (not gated
// behind the hrm permission group), so every employee can punch their own
// attendance regardless of their assigned role permissions.
Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum']], function (Router $router) {

    $router->get('hrm/attendances/today', [AttendanceController::class, 'today'])
        ->name('attendances.today');

    $router->get('hrm/attendances/geo-settings', [AttendanceController::class, 'geoSettings'])
        ->name('attendances.geo-settings');

    $router->post('hrm/attendances/punch-in', [AttendanceController::class, 'punchIn'])
        ->name('attendances.punch-in');

    $router->post('hrm/attendances/punch-out', [AttendanceController::class, 'punchOut'])
        ->name('attendances.punch-out');

    $router->post('hrm/attendances/start-break', [AttendanceController::class, 'startBreak'])
        ->name('attendances.start-break');

    $router->post('hrm/attendances/end-break', [AttendanceController::class, 'endBreak'])
        ->name('attendances.end-break');
});

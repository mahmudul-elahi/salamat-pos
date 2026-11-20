<?php

declare(strict_types=1);

use App\Http\Controllers\Hrm\EmployeeDetailController;
use App\Http\Controllers\Hrm\Support\SupportController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

// Lightweight, dropdown-style endpoints for any authenticated user — no
// auth.permission gate, mirroring routes/landlord/support.php's pattern.
Route::group(['prefix' => 'hrm/support'], function (Router $router) {

    $router->get('leave-types', [SupportController::class, 'leaveTypes']);

    $router->get('employees', [SupportController::class, 'employees']);

    // Same controller/method as employees.leave-allowances — an employee
    // needs to see their own leave availability when filing a leave
    // request, without needing the HR-only permission that route requires.
    $router->get('employees/{employee}/leave-allowances', [EmployeeDetailController::class, 'leaveAllowances']);

});

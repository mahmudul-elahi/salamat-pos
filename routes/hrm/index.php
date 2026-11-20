<?php

use App\Http\Controllers\Hrm\AnnouncementController;
use App\Http\Controllers\Hrm\AssetCategoryController;
use App\Http\Controllers\Hrm\AssetController;
use App\Http\Controllers\Hrm\AttendanceController;
use App\Http\Controllers\Hrm\AttendanceRequestController;
use App\Http\Controllers\Hrm\BeneficiaryController;
use App\Http\Controllers\Hrm\BeneficiaryValueController;
use App\Http\Controllers\Hrm\BreakTimeController;
use App\Http\Controllers\Hrm\DepartmentController;
use App\Http\Controllers\Hrm\DesignationController;
use App\Http\Controllers\Hrm\EmployeeContactController;
use App\Http\Controllers\Hrm\EmployeeController;
use App\Http\Controllers\Hrm\EmployeeDetailController;
use App\Http\Controllers\Hrm\EmployeeDocumentController;
use App\Http\Controllers\Hrm\EmployeeStatusController;
use App\Http\Controllers\Hrm\EventController;
use App\Http\Controllers\Hrm\HolidayController;
use App\Http\Controllers\Hrm\LeaveRequestController;
use App\Http\Controllers\Hrm\LeaveTypeController;
use App\Http\Controllers\Hrm\PayrunController;
use App\Http\Controllers\Hrm\PayrunTypeController;
use App\Http\Controllers\Hrm\PayslipController;
use App\Http\Controllers\Hrm\WorkShiftController;
use App\Http\Controllers\Landlord\Setting\Payrun\PayrunSettingController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'hrm', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('employees', EmployeeController::class);

    $router->post('employees/{user}/make-employee', [EmployeeController::class, 'makeEmployee'])
        ->name('employees.make-employee');

    $router->patch('employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])
        ->name('employees.toggle-status');

    $router->post('employees/{employee}/resend-email', [EmployeeController::class, 'resendEmail'])
        ->name('employees.resend-email');

    $router->get('employees/{employee}/summary', [EmployeeDetailController::class, 'summary'])
        ->name('employees.summary');

    $router->post('employees/{employee}/avatar', [EmployeeDetailController::class, 'uploadAvatar'])
        ->name('employees.upload-avatar');

    $router->get('employees/{employee}/profile', [EmployeeDetailController::class, 'profile'])
        ->name('employees.profile');

    $router->patch('employees/{employee}/profile', [EmployeeDetailController::class, 'updateProfile'])
        ->name('employees.update-profile');

    $router->get('employees/{employee}/attendance-stats', [EmployeeDetailController::class, 'attendanceStats'])
        ->name('employees.attendance-stats');

    $router->get('employees/{employee}/attendance-calendar', [EmployeeDetailController::class, 'attendanceCalendar'])
        ->name('employees.attendance-calendar');

    $router->get('employees/{employee}/leave-allowances', [EmployeeDetailController::class, 'leaveAllowances'])
        ->name('employees.leave-allowances');

    $router->get('employees/{employee}/leave-summary', [EmployeeDetailController::class, 'leaveSummary'])
        ->name('employees.leave-summary');

    $router->get('employees/{employee}/job-history', [EmployeeDetailController::class, 'jobHistory'])
        ->name('employees.job-history');

    $router->post('employees/{employee}/job-history', [EmployeeDetailController::class, 'updateJobHistory'])
        ->name('employees.update-job-history');

    $router->get('employees/{employee}/contacts', [EmployeeContactController::class, 'index'])
        ->name('employees_contacts.index');

    $router->post('employees/{employee}/contacts', [EmployeeContactController::class, 'update'])
        ->name('employees_contacts.update');

    $router->get('employees/{employee}/contacts/address', [EmployeeContactController::class, 'indexAddress'])
        ->name('employees_contact_address.index');

    $router->post('employees/{employee}/contacts/address', [EmployeeContactController::class, 'updateAddress'])
        ->name('employees_contact_address.update');

    $router->get('employees/{employee}/documents', [EmployeeDocumentController::class, 'index'])
        ->name('employee_documents.index');

    $router->post('employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])
        ->name('employee_documents.store');

    $router->delete('employee-documents/{document}', [EmployeeDocumentController::class, 'destroy'])
        ->name('employee_documents.destroy');

    $router->get('payrun-types/default', [PayrunTypeController::class, 'showDefault'])
        ->name('payrun_types.default');

    $router->post('payrun-types/default', [PayrunTypeController::class, 'updateDefault'])
        ->name('payrun_types.update-default');

    $router->get('payrun-settings', [PayrunSettingController::class, 'index'])
        ->name('payrun_settings.view');

    $router->post('payrun-settings', [PayrunSettingController::class, 'update'])
        ->name('payrun_settings.update');

    // Registered before the apiResource so this literal path isn't swallowed
    // by the {attendance} wildcard on the show route.
    $router->get('attendances/report', [AttendanceController::class, 'report'])
        ->name('attendances.report');

    $router->apiResource('attendances', AttendanceController::class);

    $router->get('attendances/{attendance}/punch-details', [AttendanceController::class, 'punchDetails'])
        ->name('attendances.punch-details');

    $router->apiResource('attendance-requests', AttendanceRequestController::class);

    // Dedicated status (approve/reject/pending) endpoint — kept separate from
    // the full update() so a reviewed request can still change status even
    // after update()/destroy() lock it down. Guarded by its own
    // update_attendance_requests_status permission (see PermissionSeeder).
    $router->patch('attendance-requests/{attendance_request}/status', [AttendanceRequestController::class, 'updateStatus'])
        ->name('attendance-requests-status.update');

    $router->apiResource('designations', DesignationController::class);

    $router->apiResource('employee-statuses', EmployeeStatusController::class);

    $router->apiResource('departments', DepartmentController::class);

    $router->apiResource('holidays', HolidayController::class);
    $router->get('holidays-calendar', [HolidayController::class, 'calendar'])
        ->name('holidays.calendar');

    $router->apiResource('announcements', AnnouncementController::class);

    $router->apiResource('events', EventController::class);

    $router->apiResource('break-times', BreakTimeController::class);
    $router->apiResource('work-shifts', WorkShiftController::class);

    $router->apiResource('leave-types', LeaveTypeController::class);
    $router->apiResource('leave-requests', LeaveRequestController::class);

    $router->apiResource('asset-categories', AssetCategoryController::class);
    $router->apiResource('assets', AssetController::class);

    $router->post('assets/{asset}/assign', [AssetController::class, 'assign'])
        ->name('assets.assign');

    $router->post('assets/{asset}/return', [AssetController::class, 'returnAsset'])
        ->name('assets.return');

    $router->get('assets/{asset}/history', [AssetController::class, 'history'])
        ->name('assets.history');

    $router->apiResource('beneficiaries', BeneficiaryController::class);

    $router->get('beneficiary-values/default', [BeneficiaryValueController::class, 'showDefault'])
        ->name('beneficiary-values.default');

    $router->post('beneficiary-values/default', [BeneficiaryValueController::class, 'updateDefault'])
        ->name('beneficiary-values.update-default');

    // Literal paths registered before the apiResource so they aren't
    // swallowed by the {payrun} wildcard on the show route.
    $router->get('payruns/default-preview', [PayrunController::class, 'defaultPreview'])
        ->name('payruns.default-preview');

    $router->post('payruns/run-default', [PayrunController::class, 'runDefault'])
        ->name('payruns.run-default');

    $router->post('payruns/run-manual', [PayrunController::class, 'runManual'])
        ->name('payruns.run-manual');

    $router->get('payruns/{payrun}/progress', [PayrunController::class, 'progress'])
        ->name('payruns.progress');

    $router->post('payruns/{payrun}/send-all', [PayslipController::class, 'sendAll'])
        ->name('payruns.send-all');

    $router->apiResource('payruns', PayrunController::class)->only(['index', 'show', 'destroy']);

    $router->post('payslips/{payslip}/send', [PayslipController::class, 'send'])
        ->name('payslips.send');

    $router->get('payslips/{payslip}/download', [PayslipController::class, 'download'])
        ->name('payslips.download');

    $router->apiResource('payslips', PayslipController::class)->only(['index', 'show']);
});

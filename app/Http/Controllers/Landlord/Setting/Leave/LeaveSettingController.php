<?php

namespace App\Http\Controllers\Landlord\Setting\Leave;

use App\Http\Controllers\Controller;
use App\Jobs\Hrm\AssignEmployeeLeavesJob;
use App\Services\Setting\SettingService;
use Illuminate\Http\Request;

class LeaveSettingController extends Controller
{
    private const CONTEXT = 'leave';

    private const ARRAY_KEYS = ['statuses_for_paid_leave', 'statuses_for_unpaid_leave'];

    public function __construct(protected SettingService $service) {}

    public function index(): array
    {
        $settings = $this->service->getFormattedSettings(self::CONTEXT);

        foreach (self::ARRAY_KEYS as $key) {
            $settings[$key] = isset($settings[$key]) ? (json_decode($settings[$key], true) ?? []) : [];
        }

        return [
            'start_month'               => $settings['start_month'] ?? '1',
            'statuses_for_paid_leave'   => $settings['statuses_for_paid_leave'],
            'statuses_for_unpaid_leave' => $settings['statuses_for_unpaid_leave'],
        ];
    }

    public function update(Request $request)
    {
        $request->validate([
            'start_month'                 => ['required', 'integer', 'between:1,12'],
            'statuses_for_paid_leave'     => ['nullable', 'array'],
            'statuses_for_paid_leave.*'   => ['exists:employee_statuses,id'],
            'statuses_for_unpaid_leave'   => ['nullable', 'array'],
            'statuses_for_unpaid_leave.*' => ['exists:employee_statuses,id'],
        ]);

        foreach (self::ARRAY_KEYS as $key) {
            request()->merge([$key => json_encode($request->input($key, []))]);
        }

        $this->service->update(self::CONTEXT);

        return successResponse('Leave settings updated successfully');
    }

    public function assign()
    {
        AssignEmployeeLeavesJob::dispatch()->onQueue('hrm-leave-assign');

        return successResponse('Leave assignment job dispatched. Employees will be updated shortly.');
    }
}

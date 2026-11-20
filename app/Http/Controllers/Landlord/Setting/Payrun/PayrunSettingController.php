<?php

namespace App\Http\Controllers\Landlord\Setting\Payrun;

use App\Http\Controllers\Controller;
use App\Services\Setting\SettingService;
use Illuminate\Http\Request;

class PayrunSettingController extends Controller
{
    private const string CONTEXT = 'payrun';

    /**
     * Settings whose value is a list of IDs, stored as a JSON string in the
     * generic settings table and transparently decoded/encoded here so the
     * frontend can just bind plain arrays.
     *
     * Note: the "Default Payrun" tab's own fields (period / consider type /
     * overtime) now live in the dedicated `payrun_types` table — see
     * App\Http\Controllers\Hrm\PayrunTypeController. This controller only
     * backs the "Manage Audience" tab's restriction lists.
     */
    private const array ARRAY_KEYS = ['restricted_departments', 'restricted_employees', 'restricted_employee_statuses'];

    public function __construct(protected SettingService $service) {}

    public function index(): array
    {
        $settings = $this->service->getFormattedSettings(self::CONTEXT);

        foreach (self::ARRAY_KEYS as $key) {
            $settings[$key] = isset($settings[$key]) ? (json_decode($settings[$key], true) ?? []) : [];
        }

        return $settings;
    }

    public function update(Request $request)
    {
        $request->validate([
            'restricted_departments' => ['nullable', 'array'],
            'restricted_departments.*' => ['exists:departments,id'],
            'restricted_employees' => ['nullable', 'array'],
            'restricted_employees.*' => ['exists:users,id'],
            'restricted_employee_statuses' => ['nullable', 'array'],
            'restricted_employee_statuses.*' => ['exists:employee_statuses,id'],
        ]);

        foreach (self::ARRAY_KEYS as $key) {
            request()->merge([$key => json_encode($request->input($key, []))]);
        }

        $this->service->update(self::CONTEXT);

        return successResponse('Payrun settings updated successfully');
    }
}

<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeContactController extends Controller
{
    private const array KEYS = [
        'contact_name',
        'contact_email',
        'emergency_name',
        'emergency_relation',
        'emergency_phone',
        'emergency_email',
    ];

    private const array ADDRESS_KEYS = [
        'residential_address',
        'residential_country',
        'residential_country_id',
        'residential_district',
        'residential_district_id',
        'residential_area_id',
        'residential_thana',
        'residential_postal_code',
        'permanent_address',
        'permanent_country',
        'permanent_country_id',
        'permanent_district',
        'permanent_district_id',
        'permanent_area_id',
        'permanent_thana',
        'permanent_postal_code',
    ];

    public function index(User $employee)
    {
        $stored = $employee->contacts->pluck('value', 'key');

        return collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => $stored->get($k)])->all();
    }

    public function update(Request $request, User $employee)
    {
        $data = $request->validate([
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'emergency_name' => ['nullable', 'string', 'max:255'],
            'emergency_relation' => ['nullable', 'string', 'max:255'],
            'emergency_phone' => ['nullable', 'string', 'max:50'],
            'emergency_email' => ['nullable', 'email', 'max:255'],
        ]);

        foreach ($data as $key => $value) {
            $employee->contacts()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return successResponse('Contact information updated successfully.');
    }

    public function indexAddress(User $employee)
    {
        $stored = $employee->contacts->pluck('value', 'key');

        return collect(self::ADDRESS_KEYS)->mapWithKeys(fn ($k) => [$k => $stored->get($k)])->all();
    }

    public function updateAddress(Request $request, User $employee)
    {
        $data = $request->validate([
            'residential_address' => ['nullable', 'string', 'max:1000'],
            'residential_country' => ['nullable', 'string', 'max:100'],
            'residential_country_id' => ['nullable', 'integer'],
            'residential_district' => ['nullable', 'string', 'max:100'],
            'residential_district_id' => ['nullable', 'integer'],
            'residential_area_id' => ['nullable', 'integer'],
            'residential_thana' => ['nullable', 'string', 'max:100'],
            'residential_postal_code' => ['nullable', 'string', 'max:20'],
            'permanent_address' => ['nullable', 'string', 'max:1000'],
            'permanent_country' => ['nullable', 'string', 'max:100'],
            'permanent_country_id' => ['nullable', 'integer'],
            'permanent_district' => ['nullable', 'string', 'max:100'],
            'permanent_district_id' => ['nullable', 'integer'],
            'permanent_area_id' => ['nullable', 'integer'],
            'permanent_thana' => ['nullable', 'string', 'max:100'],
            'permanent_postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        foreach ($data as $key => $value) {
            $employee->contacts()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return successResponse('Address information updated successfully.');
    }
}

<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\PayrunType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayrunTypeController extends Controller
{
    /**
     * The org-wide default payrun type, backing the "Default Payrun" settings
     * tab. Auto-created with sensible defaults the first time it's requested.
     */
    public function showDefault()
    {
        return PayrunType::query()->firstWhere('is_default', true)
            ?? PayrunType::query()->create([
                'name' => 'Default Payrun',
                'payrun_period' => 'monthly',
                'consider_type' => 'hour',
                'consider_overtime' => true,
                'is_default' => true,
            ]);
    }

    public function updateDefault(Request $request)
    {
        $validated = $request->validate([
            'payrun_period' => ['required', Rule::in(['monthly', 'weekly'])],
            'consider_type' => ['required', Rule::in(['hour', 'daily_log', 'none'])],
            'consider_overtime' => ['required', 'boolean'],
        ]);

        $payrunType = PayrunType::query()->firstWhere('is_default', true);

        if ($payrunType) {
            $payrunType->update($validated);
        } else {
            PayrunType::query()->create($validated + [
                'name' => 'Default Payrun',
                'is_default' => true,
            ]);
        }

        return successResponse('Default payrun updated successfully');
    }
}

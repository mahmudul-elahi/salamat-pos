<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\BeneficiaryValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BeneficiaryValueController extends Controller
{
    /**
     * Sentinel "owner" used for the organization-wide default values shown
     * on the "Badge Value" settings page.
     */
    private const string DEFAULT_MODEL_NAME = 'default';

    private const int DEFAULT_MODEL_ID = 0;

    /**
     * The org-wide default beneficiary values, split into allowances and
     * deductions for the "Badge Value" settings page.
     */
    public function showDefault()
    {
        $values = BeneficiaryValue::query()
            ->where('model_name', self::DEFAULT_MODEL_NAME)
            ->where('model_id', self::DEFAULT_MODEL_ID)
            ->with('beneficiary:id,name,type')
            ->get();

        return [
            'allowances' => $values->filter(fn (BeneficiaryValue $v) => $v->beneficiary?->type === 'allowance')
                ->values(),
            'deductions' => $values->filter(fn (BeneficiaryValue $v) => $v->beneficiary?->type === 'deduction')
                ->values(),
        ];
    }

    /**
     * Replace the org-wide default beneficiary values with the submitted
     * list (the page submits the full set of rows at once).
     */
    public function updateDefault(Request $request)
    {
        $validated = $request->validate([
            'values' => ['array'],
            'values.*.beneficiary_id' => ['required', 'exists:beneficiaries,id'],
            'values.*.amount' => ['required', 'numeric', 'min:0'],
            'values.*.is_percentage' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated) {
            BeneficiaryValue::query()
                ->where('model_name', self::DEFAULT_MODEL_NAME)
                ->where('model_id', self::DEFAULT_MODEL_ID)
                ->delete();

            foreach ($validated['values'] ?? [] as $value) {
                BeneficiaryValue::query()->create([
                    'model_name' => self::DEFAULT_MODEL_NAME,
                    'model_id' => self::DEFAULT_MODEL_ID,
                    'beneficiary_id' => $value['beneficiary_id'],
                    'amount' => $value['amount'],
                    'is_percentage' => $value['is_percentage'] ?? false,
                ]);
            }
        });

        return successResponse('Beneficiary values updated successfully');
    }
}

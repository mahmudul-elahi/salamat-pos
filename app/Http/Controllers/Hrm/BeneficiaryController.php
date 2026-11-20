<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Beneficiary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BeneficiaryController extends Controller
{
    public function index()
    {
        return Beneficiary::query()
            ->when(request('type'), fn ($query, $type) => $query->where('type', $type))
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        Beneficiary::query()->create($validated);

        return successResponse('Beneficiary created successfully');
    }

    public function show(Beneficiary $beneficiary)
    {
        return $beneficiary;
    }

    public function update(Request $request, Beneficiary $beneficiary)
    {
        // Quick status-only update from the list actions.
        if ($request->has('is_active') && ! $request->has('name')) {
            $request->validate(['is_active' => ['required', 'boolean']]);

            $beneficiary->update(['is_active' => $request->boolean('is_active')]);

            return successResponse('Beneficiary status updated successfully');
        }

        $validated = $this->validateRequest($request);

        $beneficiary->update($validated);

        return successResponse('Beneficiary updated successfully');
    }

    public function destroy(Beneficiary $beneficiary)
    {
        $beneficiary->delete();

        return successResponse('Beneficiary deleted successfully');
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['allowance', 'deduction'])],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
    }
}

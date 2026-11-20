<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Asset;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function index()
    {
        return Asset::query()
            ->with(['category:id,name', 'currentAssignment.employee:id,name'])
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_category_id' => ['required', 'exists:asset_categories,id'],
            'asset_name' => ['required', 'string', 'max:255'],
            'asset_code' => ['required', 'string', 'max:255', 'unique:assets,asset_code'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        Asset::query()->create($validated);

        return successResponse('Asset created successfully');
    }

    public function show(Asset $asset)
    {
        return $asset->load(['category:id,name', 'currentAssignment.employee:id,name']);
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'asset_category_id' => ['sometimes', 'exists:asset_categories,id'],
            'asset_name' => ['sometimes', 'string', 'max:255'],
            'asset_code' => ['sometimes', 'string', 'max:255', Rule::unique('assets', 'asset_code')->ignore($asset->id)],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        $asset->update($validated);

        return successResponse('Asset updated successfully');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();

        return successResponse('Asset deleted successfully');
    }

    public function assign(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:users,id'],
            'assigned_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        // Reassigning to someone else implicitly returns the asset from
        // whoever currently holds it, closing their custody record before
        // opening the new one - keeps at most one open assignment per asset.
        // ->update() here runs on the relation's query builder, not a
        // fetched model, so it bypasses the 'date' cast entirely - the raw
        // ISO datetime string from the frontend must be normalized by hand
        // or MySQL rejects it as an invalid DATE value.
        $asset->currentAssignment()->update([
            'returned_date' => Carbon::parse($validated['assigned_date'])->toDateString(),
        ]);

        $asset->assignments()->create($validated);

        return successResponse('Asset assigned successfully');
    }

    public function returnAsset(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'returned_date' => ['required', 'date'],
            'condition' => ['required', Rule::in(['good', 'needs_repair', 'damaged', 'lost'])],
            'return_note' => ['nullable', 'string'],
        ]);

        // Same bypassed-cast issue as assign() above - the relation query
        // builder update() doesn't run the 'date' cast on returned_date.
        $asset->currentAssignment()->update([
            ...$validated,
            'returned_date' => Carbon::parse($validated['returned_date'])->toDateString(),
        ]);

        return successResponse('Asset returned successfully');
    }

    public function history(Asset $asset)
    {
        return $asset->load([
            'category:id,name',
            'currentAssignment.employee:id,name',
            'assignments' => fn ($query) => $query
                ->with('employee:id,name')
                ->orderByDesc('assigned_date')
                ->orderByDesc('id'),
        ]);
    }
}

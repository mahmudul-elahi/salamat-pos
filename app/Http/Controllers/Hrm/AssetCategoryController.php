<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\AssetCategory;
use Illuminate\Http\Request;

class AssetCategoryController extends Controller
{
    public function index()
    {
        return AssetCategory::query()
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        AssetCategory::query()->create($validated);

        return successResponse('Asset category created successfully');
    }

    public function show(AssetCategory $assetCategory)
    {
        return $assetCategory;
    }

    public function update(Request $request, AssetCategory $assetCategory)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'boolean'],
        ]);

        $assetCategory->update($validated);

        return successResponse('Asset category updated successfully');
    }

    public function destroy(AssetCategory $assetCategory)
    {
        $assetCategory->delete();

        return successResponse('Asset category deleted successfully');
    }
}

<?php

namespace App\Http\Controllers\Pos\SpecificationGroup;

use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\SpecificationGroup\SpecificationGroupRequest;
use App\Models\Pos\SpecificationGroup\SpecificationGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SpecificationGroupController extends Controller
{
    public function __construct(protected NameFilter $filter) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return SpecificationGroup::query()
            ->whereNull('parent_id')
            ->filters($this->filter)
            ->with('subSpecificationGroups')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SpecificationGroupRequest $request): JsonResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data) {
            // If parent_id is provided, all values are children
            if (! empty($data['parent_id'])) {
                if (! empty($data['values']) && is_array($data['values'])) {
                    foreach ($data['values'] as $value) {
                        if (! empty(trim($value))) {
                            SpecificationGroup::create([
                                'name' => trim($value),
                                'parent_id' => $data['parent_id'],
                            ]);
                        }
                    }
                }
            }
            // If no parent_id is selected, all become top-level
            else {
                if (! empty($data['values']) && is_array($data['values'])) {
                    foreach ($data['values'] as $value) {
                        if (! empty(trim($value))) {
                            SpecificationGroup::create([
                                'name' => trim($value),
                                'parent_id' => null,
                            ]);
                        }
                    }
                }
            }
        });

        return successResponse('Specification created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(SpecificationGroup $specificationGroup): SpecificationGroup
    {
        return $specificationGroup->load('subSpecificationGroups');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SpecificationGroupRequest $request, SpecificationGroup $specificationGroup): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($specificationGroup, $data) {
            // If updating the parent group itself
            if (! empty($data['parent_id'])) {
                $specificationGroup->update([
                    'parent_id' => $data['parent_id'],
                ]);
            } else {
                $specificationGroup->update([
                    'parent_id' => null,
                ]);
            }

            // If new sub-values are provided, reset children
            if (! empty($data['values']) && is_array($data['values'])) {
                // Remove old sub-specifications
                $specificationGroup->subSpecificationGroups()->delete();

                foreach ($data['values'] as $value) {
                    if (! empty(trim($value))) {
                        SpecificationGroup::create([
                            'name' => trim($value),
                            'parent_id' => $specificationGroup->id,
                        ]);
                    }
                }
            }
        });

        return successResponse('Specification updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SpecificationGroup $specificationGroup): JsonResponse
    {
        $specificationGroup->delete();

        return successResponse('Specification deleted successfully');
    }
}

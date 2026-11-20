<?php

namespace App\Http\Controllers\Pos\Attribute;

use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Landlord\Attribute\AuthorizeException;
use App\Http\Requests\Landlord\Attribute\AttributeRequest;
use App\Models\Attribute\Attribute;
use Illuminate\Http\JsonResponse;

class AttributeController extends Controller
{
    public function __construct(protected NameFilter $filter) {}

    /**
     * Display a listing of the resource.
     *
     * @throws AuthorizeException
     */
    public function index()
    {
        return Attribute::query()
            ->with('values')
            ->filters($this->filter)
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AttributeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Create attribute
        $attribute = Attribute::create([
            'name' => $validated['name'],
            'status' => $validated['status'] ?? 1,
        ]);

        // Insert attribute values
        if (! empty($validated['values']) && is_array($validated['values'])) {
            foreach ($validated['values'] as $value) {
                $attribute->values()->create([
                    'value' => $value,
                    'status' => 1,
                ]);
            }
        }

        return successResponse('Attribute created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Attribute $attribute): Attribute
    {
        return $attribute->load('values');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AttributeRequest $request, Attribute $attribute): JsonResponse
    {
        $validated = $request->validated();

        // Update attribute
        $attribute->update([
            'name' => $validated['name'],
            'status' => $validated['status'] ?? 1,
        ]);

        // Handle values sync
        if (! empty($validated['values']) && is_array($validated['values'])) {
            $existingValues = $attribute->values->pluck('value')->toArray();
            $newValues = $validated['values'];

            // Delete removed values
            foreach ($attribute->values as $value) {
                if (! in_array($value->value, $newValues)) {
                    $value->delete();
                }
            }

            // Add new values
            foreach ($newValues as $value) {
                if (! in_array($value, $existingValues)) {
                    $attribute->values()->create([
                        'value' => $value,
                        'status' => 1,
                    ]);
                }
            }
        }

        return successResponse('Attribute updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attribute $attribute): JsonResponse
    {
        $attribute->delete();

        return successResponse('Attribute deleted successfully');
    }
}

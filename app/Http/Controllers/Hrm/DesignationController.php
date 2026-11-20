<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index()
    {
        return Designation::query()
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Designation::query()->create($validated);

        return successResponse('Designation created successfully');
    }

    public function show(Designation $designation)
    {
        return $designation;
    }

    public function update(Request $request, Designation $designation)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $designation->update($validated);

        return successResponse('Designation updated successfully');
    }

    public function destroy(Designation $designation)
    {
        $designation->delete();

        return successResponse('Designation deleted successfully');
    }
}

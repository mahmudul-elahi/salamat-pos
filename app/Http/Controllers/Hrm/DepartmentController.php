<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return Department::query()
            ->with('departmentHead:id,name')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'department_head_id' => ['nullable', 'exists:users,id'],
        ]);

        Department::query()->create($validated);

        return successResponse('Department created successfully');
    }

    public function show(Department $department)
    {
        return $department->load('departmentHead:id,name');
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name'               => ['sometimes', 'string', 'max:255'],
            'department_head_id' => ['nullable', 'exists:users,id'],
        ]);

        $department->update($validated);

        return successResponse('Department updated successfully');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return successResponse('Department deleted successfully');
    }
}

<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\EmployeeStatus;
use Illuminate\Http\Request;

class EmployeeStatusController extends Controller
{
    public function index()
    {
        return EmployeeStatus::query()
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'color'       => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
        ]);

        EmployeeStatus::query()->create($validated);

        return successResponse('Employee status created successfully');
    }

    public function show(EmployeeStatus $employeeStatus)
    {
        return $employeeStatus;
    }

    public function update(Request $request, EmployeeStatus $employeeStatus)
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'color'       => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
        ]);

        $employeeStatus->update($validated);

        return successResponse('Employee status updated successfully');
    }

    public function destroy(EmployeeStatus $employeeStatus)
    {
        $employeeStatus->delete();

        return successResponse('Employee status deleted successfully');
    }
}

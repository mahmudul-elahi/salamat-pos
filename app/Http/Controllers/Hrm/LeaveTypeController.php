<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index()
    {
        return LeaveType::query()
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:paid,unpaid'],
            'day' => ['required', 'numeric', 'min:0'],
            'enable' => ['required', 'boolean'],
        ]);

        LeaveType::query()->create($validated);

        return successResponse('Leave type created successfully');
    }

    public function show(LeaveType $leaveType)
    {
        return $leaveType;
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:paid,unpaid'],
            'day' => ['required', 'numeric', 'min:0'],
            'enable' => ['required', 'boolean'],
        ]);

        $leaveType->update($validated);

        return successResponse('Leave type updated successfully');
    }

    public function destroy(LeaveType $leaveType)
    {
        $leaveType->delete();

        return successResponse('Leave type deleted successfully');
    }
}

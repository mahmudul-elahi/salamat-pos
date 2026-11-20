<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index()
    {
        return Holiday::query()
            ->with('department:id,name')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function calendar()
    {
        return Holiday::query()
            ->with('department:id,name')
            ->get()
            ->map(fn ($h) => [
                'id' => $h->id,
                'title' => $h->name,
                'start' => $h->start_date->format('Y-m-d'),
                'end' => $h->end_date->addDay()->format('Y-m-d'),
                'description' => $h->description,
                'department' => $h->department?->name,
                'backgroundColor' => '#0d6efd',
                'borderColor' => '#0d6efd',
            ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'description' => ['nullable', 'string'],
        ]);

        Holiday::query()->create($validated);

        return successResponse('Holiday created successfully');
    }

    public function show(Holiday $holiday)
    {
        return $holiday->load('department:id,name');
    }

    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'description' => ['nullable', 'string'],
        ]);

        $holiday->update($validated);

        return successResponse('Holiday updated successfully');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return successResponse('Holiday deleted successfully');
    }
}

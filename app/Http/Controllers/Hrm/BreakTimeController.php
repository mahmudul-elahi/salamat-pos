<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\BreakTime;
use Illuminate\Http\Request;

class BreakTimeController extends Controller
{
    public function index()
    {
        return BreakTime::query()->orderByDesc('id')->paginate(request('per_page', 10));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'duration' => ['required', 'string', 'max:20'],
        ]);

        BreakTime::query()->create($request->only('name', 'duration'));

        return successResponse('Break time created successfully');
    }

    public function show(BreakTime $breakTime)
    {
        return $breakTime;
    }

    public function update(Request $request, BreakTime $breakTime)
    {
        $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'duration' => ['sometimes', 'string', 'max:20'],
        ]);

        $breakTime->update($request->only('name', 'duration'));

        return successResponse('Break time updated successfully');
    }

    public function destroy(BreakTime $breakTime)
    {
        $breakTime->delete();

        return successResponse('Break time deleted successfully');
    }
}

<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\WorkShift;
use App\Models\Hrm\WorkShiftSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkShiftController extends Controller
{
    public function index()
    {
        return WorkShift::query()
            ->with(['breakTimes:id,name,duration', 'departments:id,name', 'employees:id,name'])
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_name'          => ['required', 'string', 'max:255'],
            'working_shift_type'  => ['required', 'in:regular,scheduled'],
            'start_time'          => ['nullable', 'string'],
            'end_time'            => ['nullable', 'string'],
            'weekend_days'        => ['nullable', 'array'],
            'weekend_days.*'      => ['string'],
            'break_times'         => ['nullable', 'array'],
            'break_times.*'       => ['exists:break_times,id'],
            'departments'         => ['nullable', 'array'],
            'departments.*'       => ['exists:departments,id'],
            'employees'           => ['nullable', 'array'],
            'employees.*'         => ['exists:users,id'],
            'schedules'           => ['nullable', 'array'],
            'schedules.*.day_name'   => ['required_with:schedules', 'string'],
            'schedules.*.start_time' => ['nullable', 'string'],
            'schedules.*.end_time'   => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        $shift = WorkShift::query()->create([
            'shift_name'         => $validated['shift_name'],
            'working_shift_type' => $validated['working_shift_type'],
            'start_time'         => $validated['start_time'] ?? null,
            'end_time'           => $validated['end_time'] ?? null,
            'weekend_days'       => $validated['weekend_days'] ?? [],
        ]);

        $shift->breakTimes()->sync($validated['break_times'] ?? []);
        $shift->departments()->sync($validated['departments'] ?? []);
        $shift->employees()->sync($validated['employees'] ?? []);

        if ($validated['working_shift_type'] === 'scheduled' && !empty($validated['schedules'])) {
            foreach ($validated['schedules'] as $schedule) {
                $shift->schedules()->create([
                    'day_name'   => $schedule['day_name'],
                    'start_time' => $schedule['start_time'] ?? null,
                    'end_time'   => $schedule['end_time'] ?? null,
                ]);
            }
        }

        DB::commit();

        return successResponse('Work shift created successfully');
    }

    public function show(WorkShift $workShift)
    {
        return $workShift->load(['breakTimes:id,name,duration', 'departments:id,name', 'employees:id,name', 'schedules']);
    }

    public function update(Request $request, WorkShift $workShift)
    {
        $validated = $request->validate([
            'shift_name'          => ['sometimes', 'string', 'max:255'],
            'working_shift_type'  => ['sometimes', 'in:regular,scheduled'],
            'start_time'          => ['nullable', 'string'],
            'end_time'            => ['nullable', 'string'],
            'weekend_days'        => ['nullable', 'array'],
            'weekend_days.*'      => ['string'],
            'break_times'         => ['nullable', 'array'],
            'break_times.*'       => ['exists:break_times,id'],
            'departments'         => ['nullable', 'array'],
            'departments.*'       => ['exists:departments,id'],
            'employees'           => ['nullable', 'array'],
            'employees.*'         => ['exists:users,id'],
            'schedules'           => ['nullable', 'array'],
            'schedules.*.day_name'   => ['required_with:schedules', 'string'],
            'schedules.*.start_time' => ['nullable', 'string'],
            'schedules.*.end_time'   => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        $workShift->update([
            'shift_name'         => $validated['shift_name'] ?? $workShift->shift_name,
            'working_shift_type' => $validated['working_shift_type'] ?? $workShift->working_shift_type,
            'start_time'         => $validated['start_time'] ?? null,
            'end_time'           => $validated['end_time'] ?? null,
            'weekend_days'       => $validated['weekend_days'] ?? [],
        ]);

        if (array_key_exists('break_times', $validated)) {
            $workShift->breakTimes()->sync($validated['break_times'] ?? []);
        }
        if (array_key_exists('departments', $validated)) {
            $workShift->departments()->sync($validated['departments'] ?? []);
        }
        if (array_key_exists('employees', $validated)) {
            $workShift->employees()->sync($validated['employees'] ?? []);
        }

        $type = $validated['working_shift_type'] ?? $workShift->working_shift_type;
        if ($type === 'scheduled' && array_key_exists('schedules', $validated)) {
            $workShift->schedules()->delete();
            foreach ($validated['schedules'] as $schedule) {
                $workShift->schedules()->create([
                    'day_name'   => $schedule['day_name'],
                    'start_time' => $schedule['start_time'] ?? null,
                    'end_time'   => $schedule['end_time'] ?? null,
                ]);
            }
        } elseif ($type === 'regular') {
            $workShift->schedules()->delete();
        }

        DB::commit();

        return successResponse('Work shift updated successfully');
    }

    public function destroy(WorkShift $workShift)
    {
        $workShift->delete();

        return successResponse('Work shift deleted successfully');
    }
}

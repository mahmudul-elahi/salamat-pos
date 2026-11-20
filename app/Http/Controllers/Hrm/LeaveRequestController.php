<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\EmployeeLeave;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\WorkShift;
use App\Models\User;
use App\Notifications\Hrm\LeaveStatusNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function index()
    {
        return LeaveRequest::query()
            ->with(['leaveType:id,name,type', 'user:id,name', 'media'])
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn ($lr) => $this->formatLeaveRequest($lr));
    }

    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'leave_duration' => ['required', 'in:single_day,multi_day,hours'],
            'note' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg,pdf', 'max:5120'],
            'date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
        ]);

        $userId = auth()->id();
        $leaveTypeId = $request->input('leave_type_id');
        $duration = $request->input('leave_duration');
        $requestedDays = $this->calculateDays($request);
        $refDate = $request->input('date') ?? $request->input('start_date');

        // Check duplicate leave for same date (allow re-apply only if previous was rejected)
        $this->checkDuplicateLeave($request, $userId, $duration);

        // Check weekend conflict against employee's work shift
        $this->checkWeekendConflict($request, $userId, $duration);

        // Check holiday overlap
        $this->checkHolidayConflict($request, $userId);

        // Check employee has a leave balance for this leave type
        $balance = $this->findBalance($userId, $leaveTypeId, $refDate);

        if (! $balance instanceof EmployeeLeave) {
            throw ValidationException::withMessages([
                'leave_type_id' => ['You do not have any leave balance for this leave type.'],
            ]);
        }

        if ($balance->day < $requestedDays) {
            throw ValidationException::withMessages([
                'leave_type_id' => ["Insufficient leave balance. Available: {$balance->day} day(s), Requested: {$requestedDays} day(s)."],
            ]);
        }

        $data = $request->only(['leave_type_id', 'leave_duration', 'note', 'date', 'start_date', 'end_date', 'start_time', 'end_time']);
        $data['user_id'] = $userId;
        $data['status'] = 'pending';

        $leaveRequest = LeaveRequest::query()->create($data);

        foreach ($request->file('attachments', []) as $file) {
            if ($file && $file->isValid()) {
                $leaveRequest->addMedia($file)->toMediaCollection('leave_attachments');
            }
        }

        return successResponse('Leave request created successfully');
    }

    public function show(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load(['leaveType:id,name,type', 'user:id,name', 'media']);

        return $this->formatLeaveRequest($leaveRequest);
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        // Quick status-only update (approve / reject) from the list actions.
        if ($request->has('status') && ! $request->has('leave_type_id')) {
            $request->validate([
                'status' => ['required', Rule::in(['approved', 'rejected', 'pending'])],
            ]);

            $previousStatus = $leaveRequest->status;
            $newStatus = $request->get('status');

            $leaveRequest->update(['status' => $newStatus]);

            // Deduct balance when approved (only once)
            if ($newStatus === 'approved' && $previousStatus !== 'approved') {
                $this->deductLeaveBalance($leaveRequest);
            }

            // Restore balance if previously approved and now rejected/pending
            if ($previousStatus === 'approved' && $newStatus !== 'approved') {
                $this->restoreLeaveBalance($leaveRequest);
            }

            // Notify the employee who applied
            $leaveRequest->load('leaveType');
            $leaveRequest->user?->notify(new LeaveStatusNotification($leaveRequest, $newStatus));

            return successResponse('Leave request status updated successfully');
        }

        $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'leave_duration' => ['required', 'in:single_day,multi_day,hours'],
            'note' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:svg,png,jpg,jpeg,webp', 'max:5120'],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer'],
            'date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
        ]);

        $data = $request->only(['leave_type_id', 'leave_duration', 'note', 'date', 'start_date', 'end_date', 'start_time', 'end_time']);
        $leaveRequest->update($data);

        if ($request->filled('remove_attachments')) {
            $leaveRequest->media()
                ->whereIn('id', $request->input('remove_attachments'))
                ->get()
                ->each(fn ($media) => $media->delete());
        }

        foreach ($request->file('attachments', []) as $file) {
            if ($file && $file->isValid()) {
                $leaveRequest->addMedia($file)->toMediaCollection('leave_attachments');
            }
        }

        return successResponse('Leave request updated successfully');
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        $leaveRequest->delete();

        return successResponse('Leave request deleted successfully');
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    private function checkWeekendConflict(Request $request, int $userId, string $duration): void
    {
        $user = User::with('currentDepartment')->find($userId);
        $departmentId = $user?->currentDepartment?->department_id;

        if (! $departmentId) {
            return;
        }

        $shift = WorkShift::query()
            ->whereHas('departments', fn ($q) => $q->where('departments.id', $departmentId))
            ->first();

        if (! $shift || empty($shift->weekend_days)) {
            return;
        }

        $weekendDays = $shift->weekend_days; // e.g. ["Saturday", "Sunday"]

        if ($duration === 'multi_day') {
            $current = Carbon::parse($request->input('start_date'))->startOfDay();
            $end = Carbon::parse($request->input('end_date'))->startOfDay();

            while ($current->lte($end)) {
                if (in_array($current->format('l'), $weekendDays)) {
                    throw ValidationException::withMessages([
                        'start_date' => [
                            "You cannot apply leave on a weekend day ({$current->format('l, d M Y')}) as per your work shift.",
                        ],
                    ]);
                }
                $current->addDay();
            }
        } else {
            $date = Carbon::parse($request->input('date'));

            if (in_array($date->format('l'), $weekendDays)) {
                throw ValidationException::withMessages([
                    'date' => [
                        "You cannot apply leave on a weekend day ({$date->format('l, d M Y')}) as per your work shift.",
                    ],
                ]);
            }
        }
    }

    private function checkDuplicateLeave(Request $request, int $userId, string $duration): void
    {
        $query = LeaveRequest::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved']);

        if ($duration === 'multi_day') {
            $leaveStart = $request->input('start_date');
            $leaveEnd = $request->input('end_date');

            $exists = $query->where('leave_duration', 'multi_day')
                ->where('start_date', '<=', $leaveEnd)
                ->where('end_date', '>=', $leaveStart)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'start_date' => ['You already have a pending or approved leave request for this date range.'],
                ]);
            }
        } else {
            $date = $request->input('date');
            $exists = $query->where('leave_duration', $duration)
                ->where('date', $date)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'date' => ['You already have a pending or approved leave request for this date.'],
                ]);
            }
        }
    }

    private function checkHolidayConflict(Request $request, int $userId): void
    {
        $duration = $request->input('leave_duration');

        if ($duration === 'multi_day') {
            $leaveStart = Carbon::parse($request->input('start_date'))->startOfDay();
            $leaveEnd = Carbon::parse($request->input('end_date'))->endOfDay();
        } else {
            $date = Carbon::parse($request->input('date'))->startOfDay();
            $leaveStart = $date;
            $leaveEnd = $date->copy()->endOfDay();
        }

        // Resolve user's current department for department-specific holidays
        $user = User::with('currentDepartment')->find($userId);
        $departmentId = $user?->currentDepartment?->department_id;

        $holiday = Holiday::query()
            ->where(function ($q) use ($departmentId) {
                $q->whereNull('department_id');
                if ($departmentId) {
                    $q->orWhere('department_id', $departmentId);
                }
            })
            ->where('start_date', '<=', $leaveEnd->toDateString())
            ->where('end_date', '>=', $leaveStart->toDateString())
            ->first();

        if ($holiday) {
            $start = Carbon::parse($holiday->start_date)->format('d M Y');
            $end = Carbon::parse($holiday->end_date)->format('d M Y');
            $label = $start === $end ? $start : "{$start} – {$end}";
            $errorKey = $duration === 'multi_day' ? 'start_date' : 'date';

            throw ValidationException::withMessages([
                $errorKey => ["You cannot apply leave on a holiday: {$holiday->name} ({$label})."],
            ]);
        }
    }

    private function calculateDays(Request $request): float
    {
        return match ($request->input('leave_duration')) {
            'single_day' => 1,
            'multi_day' => Carbon::parse($request->input('start_date'))
                ->diffInDays(Carbon::parse($request->input('end_date'))) + 1,
            'hours' => 0.5,
            default => 1,
        };
    }

    private function findBalance(int $userId, int $leaveTypeId, ?string $refDate): ?EmployeeLeave
    {
        $date = $refDate ? Carbon::parse($refDate) : now();

        return EmployeeLeave::query()
            ->where('employee_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    private function deductLeaveBalance(LeaveRequest $leaveRequest): void
    {
        $refDate = $leaveRequest->date ?? $leaveRequest->start_date;
        $balance = $this->findBalance(
            $leaveRequest->user_id,
            $leaveRequest->leave_type_id,
            $refDate?->toDateString()
        );

        if (! $balance instanceof EmployeeLeave) {
            return;
        }

        $days = $this->calculateDaysFromLeaveRequest($leaveRequest);
        $balance->decrement('day', $days);
    }

    private function restoreLeaveBalance(LeaveRequest $leaveRequest): void
    {
        $refDate = $leaveRequest->date ?? $leaveRequest->start_date;
        $balance = $this->findBalance(
            $leaveRequest->user_id,
            $leaveRequest->leave_type_id,
            $refDate?->toDateString()
        );

        if (! $balance instanceof EmployeeLeave) {
            return;
        }

        $days = $this->calculateDaysFromLeaveRequest($leaveRequest);
        $balance->increment('day', $days);
    }

    private function calculateDaysFromLeaveRequest(LeaveRequest $leaveRequest): float
    {
        return match ($leaveRequest->leave_duration) {
            'single_day' => 1,
            'multi_day' => $leaveRequest->start_date && $leaveRequest->end_date
                ? $leaveRequest->start_date->diffInDays($leaveRequest->end_date) + 1
                : 1,
            'hours' => 0.5,
            default => 1,
        };
    }

    private function formatLeaveRequest(LeaveRequest $leaveRequest): array
    {
        $data = $leaveRequest->toArray();
        $data['attachments'] = $leaveRequest->getMedia('leave_attachments')
            ->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getFullUrl(),
                'mime_type' => $media->mime_type,
                'size' => $media->size,
            ])->values()->toArray();

        return $data;
    }
}

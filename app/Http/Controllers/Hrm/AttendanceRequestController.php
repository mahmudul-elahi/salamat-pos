<?php

namespace App\Http\Controllers\Hrm;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Customization\Customization;
use App\Models\Hrm\Attendance;
use App\Models\Hrm\AttendanceRequest;
use App\Models\Hrm\WorkShift;
use App\Notifications\Hrm\AttendanceRequestStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceRequestController extends Controller
{
    public function index()
    {
        return AttendanceRequest::query()
            ->when(request('employee'), fn ($q) => $q->where('user_id', request('employee')))
            ->with(['user:id,name', 'user.media', 'user.currentDepartment.department:id,name', 'reviewer:id,name'])
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn (AttendanceRequest $ar) => $this->formatAttendanceRequest($ar));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        $userId = auth()->id();

        $exists = AttendanceRequest::query()
            ->where('user_id', $userId)
            ->where('date', $validated['date'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'date' => ['You already have a pending or approved attendance request for this date.'],
            ]);
        }

        $this->assertNoExistingAttendance($userId, $validated['date']);

        $data = $this->convertTimesToUtc($validated);
        $data['user_id'] = $userId;
        $data['status'] = 'pending';

        AttendanceRequest::query()->create($data);

        return successResponse('Attendance request submitted successfully');
    }

    public function show(AttendanceRequest $attendanceRequest)
    {
        return $this->formatAttendanceRequest($attendanceRequest->load(['user:id,name', 'user.media', 'user.currentDepartment.department:id,name', 'reviewer:id,name']));
    }

    public function update(Request $request, AttendanceRequest $attendanceRequest)
    {
        if ($attendanceRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['This attendance request has already been reviewed and can no longer be updated.'],
            ]);
        }

        $validated = $this->validateRequest($request);

        if ($validated['date'] !== $attendanceRequest->date->toDateString()) {
            $this->assertNoExistingAttendance(auth()->id(), $validated['date']);
        }

        $data = $this->convertTimesToUtc($validated);

        $attendanceRequest->update($data);

        return successResponse('Attendance request updated successfully');
    }

    /**
     * Dedicated approve/reject/pending endpoint — deliberately separate from
     * update() so a request's status can still be changed after it has been
     * reviewed, even though update() and destroy() lock the record down at
     * that point.
     */
    public function updateStatus(Request $request, AttendanceRequest $attendanceRequest)
    {
        $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'pending'])],
        ]);

        $newStatus = $request->get('status');

        if ($newStatus === 'approved' && $attendanceRequest->status !== 'approved') {
            $this->applyToAttendance($attendanceRequest);
        }

        // Un-approving (approved -> rejected/pending) must undo whatever
        // applyToAttendance() created, otherwise the employee keeps
        // showing as present for that day even after the request is
        // rejected.
        if ($attendanceRequest->status === 'approved' && $newStatus !== 'approved') {
            $this->revertAttendance($attendanceRequest);
        }

        $attendanceRequest->update([
            'status' => $newStatus,
            'reviewed_by' => auth()->id(),
        ]);

        $attendanceRequest->user?->notify(new AttendanceRequestStatusNotification($attendanceRequest, $newStatus));

        return successResponse('Attendance request status updated successfully');
    }

    /**
     * Block requests for a date the employee already has real attendance
     * recorded for — e.g. they already punched in/out normally that day, so
     * there's nothing to correct.
     */
    private function assertNoExistingAttendance(int $userId, string $date): void
    {
        $hasAttendance = Attendance::query()
            ->where('user_id', $userId)
            ->where('in_date', $date)
            ->exists();

        if ($hasAttendance) {
            throw ValidationException::withMessages([
                'date' => ['You already have attendance recorded for this date, so a request is not needed.'],
            ]);
        }
    }

    public function destroy(AttendanceRequest $attendanceRequest)
    {
        if ($attendanceRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['This attendance request has already been reviewed and can no longer be deleted.'],
            ]);
        }

        $attendanceRequest->delete();

        return successResponse('Attendance request deleted successfully');
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'in_time' => ['nullable', 'date_format:H:i', 'required_without:out_time'],
            'out_time' => ['nullable', 'date_format:H:i', 'required_without:in_time', 'after:in_time'],
            'note' => ['nullable', 'string'],
        ]);
    }

    /**
     * in_time/out_time arrive as plain "H:i" wall-clock strings in the
     * employee's own timezone. Combine each with the request's date and
     * convert to UTC before storing — attendance_requests.in_time/out_time
     * are UTC datetimes, matching attendance_details.in_time/out_time.
     */
    private function convertTimesToUtc(array $validated): array
    {
        $tz = user_timezone();

        foreach (['in_time', 'out_time'] as $field) {
            $validated[$field] = ! empty($validated[$field])
                ? Carbon::parse($validated['date'].' '.$validated[$field], $tz)->setTimezone('UTC')
                : null;
        }

        return $validated;
    }

    /**
     * Convert stored UTC in_time/out_time back to the viewing user's local
     * "H:i" wall-clock time for display and for prefilling the edit form's
     * <input type="time">.
     */
    private function formatAttendanceRequest(AttendanceRequest $attendanceRequest): array
    {
        $data = $attendanceRequest->toArray();
        $data['in_time'] = $attendanceRequest->in_time ? to_user_timezone_datetime($attendanceRequest->in_time, 'H:i') : null;
        $data['out_time'] = $attendanceRequest->out_time ? to_user_timezone_datetime($attendanceRequest->out_time, 'H:i') : null;

        if ($data['user']) {
            $data['user']['profile_picture'] = $attendanceRequest->user->profile_picture;
            $data['user']['department'] = $attendanceRequest->user->currentDepartment?->department?->name;
        }

        return $data;
    }

    /**
     * Approving a request marks the employee present for that day — creating
     * the day's Attendance row and punch session(s) if they don't already
     * exist, mirroring AttendanceController::punchIn()'s work-shift
     * resolution and behavior calculation so approved requests behave the
     * same as a real punch. Both in_time and out_time can be supplied
     * together (a full session in one go), or just one of them (correcting
     * a single missed punch). Both are already UTC datetimes at this point.
     */
    private function applyToAttendance(AttendanceRequest $attendanceRequest): void
    {
        DB::transaction(function () use ($attendanceRequest) {
            $user = $attendanceRequest->user;
            $date = $attendanceRequest->date->toDateString();

            $inAt = $attendanceRequest->in_time;
            $outAt = $attendanceRequest->out_time;

            $attendance = Attendance::query()->firstOrNew([
                'user_id' => $user->id,
                'in_date' => $date,
            ]);

            if (! $attendance->exists) {
                $workShift = $user->workShifts()->with('schedules')->first()
                    ?? $user->currentDepartment?->department?->workShifts()->with('schedules')->first();

                // attendances.work_shift_id has no default and isn't
                // nullable — mirrors AttendanceController::punchIn()'s own
                // hard requirement for a resolvable work shift before a row
                // can be saved at all.
                if (! $workShift) {
                    throw new GeneralException('This employee has no work shift assigned, so the attendance request cannot be approved yet.');
                }

                $attendance->work_shift_id = $workShift->id;
                $attendance->status = 'approved';
                $attendance->behavior = $this->calculateBehavior($workShift, $inAt ?? $outAt);
                $attendance->save();
            }

            // Always its own dedicated session (never reuse/mutate an
            // existing detail) — assertNoExistingAttendance() guarantees no
            // Attendance existed for this date before the request was
            // submitted, so there's nothing else to merge with, and keeping
            // this 1:1 with the request is what makes revertAttendance()
            // below able to cleanly undo exactly this and nothing else.
            $detail = $attendance->details()->create([
                'in_time' => $inAt,
                'out_time' => $outAt,
                'added_by' => auth()->id(),
                'review_by' => auth()->id(),
                'in_note' => $inAt ? $attendanceRequest->note : null,
                'out_note' => $outAt ? $attendanceRequest->note : null,
            ]);

            $attendanceRequest->attendance_detail_id = $detail->id;
            $attendanceRequest->save();
        });
    }

    /**
     * Undo exactly what applyToAttendance() did: remove the punch session it
     * created, and if that leaves the day with no sessions at all, remove
     * the Attendance row too (it only exists because of this request).
     */
    private function revertAttendance(AttendanceRequest $attendanceRequest): void
    {
        DB::transaction(function () use ($attendanceRequest) {
            $detail = $attendanceRequest->attendanceDetail;

            if (! $detail) {
                return;
            }

            $attendance = $detail->attendance;
            $detail->delete();

            if ($attendance && $attendance->details()->count() === 0) {
                $attendance->delete();
            }

            $attendanceRequest->attendance_detail_id = null;
            $attendanceRequest->save();
        });
    }

    private function calculateBehavior(WorkShift $shift, Carbon $punchTime): string
    {
        $tz = user_timezone();
        $localPunch = $punchTime->copy()->setTimezone($tz);

        $startTime = $shift->working_shift_type === 'scheduled'
            ? $shift->schedules->firstWhere('day_name', $localPunch->format('l'))?->start_time
            : $shift->start_time;

        if (! $startTime) {
            return 'regular';
        }

        $scheduledStart = Carbon::parse($localPunch->toDateString().' '.$startTime, $tz);

        if ($localPunch->lt($scheduledStart)) {
            return 'early';
        }

        $toleranceMinutes = (int) Customization::where('type', 'attendance')
            ->where('name', 'punch_in_tolerance')
            ->value('value');

        $toleranceEnd = $scheduledStart->copy()->addMinutes($toleranceMinutes);

        return $localPunch->lte($toleranceEnd) ? 'regular' : 'late';
    }
}

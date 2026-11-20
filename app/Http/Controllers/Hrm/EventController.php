<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Event;
use App\Notifications\Hrm\EventNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class EventController extends Controller
{
    public function index()
    {
        return Event::query()
            ->with(['departments:id,name', 'employees:id,name'])
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_type'     => ['required', 'in:bulk,individual'],
            'title'          => ['required', 'string', 'max:255'],
            'start_datetime' => ['required', 'date'],
            'end_datetime'   => ['required', 'date', 'after_or_equal:start_datetime'],
            'location'       => ['nullable', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'departments'    => ['nullable', 'array'],
            'departments.*'  => ['exists:departments,id'],
            'employees'      => ['nullable', 'array'],
            'employees.*'    => ['exists:users,id'],
        ]);

        $event = Event::query()->create([
            'event_type'     => $validated['event_type'],
            'title'          => $validated['title'],
            'start_datetime' => $validated['start_datetime'],
            'end_datetime'   => $validated['end_datetime'],
            'location'       => $validated['location'] ?? null,
            'description'    => $validated['description'] ?? null,
        ]);

        if ($validated['event_type'] === 'individual') {
            $event->departments()->sync($validated['departments'] ?? []);
            $event->employees()->sync($validated['employees'] ?? []);
        }

        $this->sendNotifications($event, $validated);

        return successResponse('Event created successfully');
    }

    public function show(Event $event)
    {
        return $event->load(['departments:id,name', 'employees:id,name']);
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'event_type'     => ['sometimes', 'in:bulk,individual'],
            'title'          => ['sometimes', 'string', 'max:255'],
            'start_datetime' => ['sometimes', 'date'],
            'end_datetime'   => ['sometimes', 'date', 'after_or_equal:start_datetime'],
            'location'       => ['nullable', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'departments'    => ['nullable', 'array'],
            'departments.*'  => ['exists:departments,id'],
            'employees'      => ['nullable', 'array'],
            'employees.*'    => ['exists:users,id'],
        ]);

        $event->update([
            'event_type'     => $validated['event_type'] ?? $event->event_type,
            'title'          => $validated['title'] ?? $event->title,
            'start_datetime' => $validated['start_datetime'] ?? $event->start_datetime,
            'end_datetime'   => $validated['end_datetime'] ?? $event->end_datetime,
            'location'       => $validated['location'] ?? $event->location,
            'description'    => $validated['description'] ?? $event->description,
        ]);

        $type = $validated['event_type'] ?? $event->event_type;
        if ($type === 'individual') {
            if (array_key_exists('departments', $validated)) {
                $event->departments()->sync($validated['departments'] ?? []);
            }
            if (array_key_exists('employees', $validated)) {
                $event->employees()->sync($validated['employees'] ?? []);
            }
        } else {
            $event->departments()->detach();
            $event->employees()->detach();
        }

        return successResponse('Event updated successfully');
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return successResponse('Event deleted successfully');
    }

    // ──────────────────────────────────────────────────────────────────────
    // Notification
    // ──────────────────────────────────────────────────────────────────────

    private function sendNotifications(Event $event, array $validated): void
    {
        $recipients = $this->resolveRecipients($event, $validated);

        if ($recipients->isEmpty()) return;

        Notification::send($recipients, new EventNotification($event));
    }

    private function resolveRecipients(Event $event, array $validated): Collection
    {
        // Bulk → all employees
        if ($validated['event_type'] === 'bulk') {
            return User::query()
                ->where('is_employee', true)
                ->get();
        }

        // Individual → union of directly selected employees + department employees
        $userIds = collect($validated['employees'] ?? []);

        // Employees from selected departments (current department, no end_date)
        $departmentIds = $validated['departments'] ?? [];
        if (!empty($departmentIds)) {
            $deptUserIds = User::query()
                ->whereHas('currentDepartment', fn($q) => $q->whereIn('department_id', $departmentIds))
                ->pluck('id');

            $userIds = $userIds->merge($deptUserIds)->unique();
        }

        if ($userIds->isEmpty()) return collect();

        return User::query()->whereIn('id', $userIds)->get();
    }
}

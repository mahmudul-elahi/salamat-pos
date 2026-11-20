<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Announcement;
use App\Models\User;
use App\Notifications\Hrm\AnnouncementNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AnnouncementController extends Controller
{
    public function index()
    {
        return Announcement::query()
            ->with('departments:id,name')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string'],
            'departments' => ['nullable', 'array'],
            'departments.*' => ['exists:departments,id'],
        ]);

        $announcement = Announcement::query()->create([
            'title' => $validated['title'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'description' => $validated['description'] ?? null,
        ]);

        if (! empty($validated['departments'])) {
            $announcement->departments()->sync($validated['departments']);
        }

        $this->sendNotifications($announcement, $validated['departments'] ?? []);

        return successResponse('Announcement created successfully');
    }

    public function show(Announcement $announcement)
    {
        return $announcement->load('departments:id,name');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string'],
            'departments' => ['nullable', 'array'],
            'departments.*' => ['exists:departments,id'],
        ]);

        $announcement->update([
            'title' => $validated['title'] ?? $announcement->title,
            'start_date' => $validated['start_date'] ?? $announcement->start_date,
            'end_date' => $validated['end_date'] ?? $announcement->end_date,
            'description' => $validated['description'] ?? $announcement->description,
        ]);

        if (array_key_exists('departments', $validated)) {
            $announcement->departments()->sync($validated['departments'] ?? []);
        }

        return successResponse('Announcement updated successfully');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return successResponse('Announcement deleted successfully');
    }

    // ──────────────────────────────────────────────────────────────────────
    // Notification
    // ──────────────────────────────────────────────────────────────────────

    private function sendNotifications(Announcement $announcement, array $departmentIds): void
    {
        if ($departmentIds !== []) {
            // Only employees in the selected departments
            $recipients = User::query()
                ->where('is_employee', true)
                ->whereHas('currentDepartment', fn ($q) => $q->whereIn('department_id', $departmentIds))
                ->get();
        } else {
            // No department selected → all employees
            $recipients = User::query()
                ->where('is_employee', true)
                ->get();
        }

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AnnouncementNotification($announcement));
    }
}

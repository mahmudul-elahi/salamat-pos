<?php

namespace App\Notifications\Hrm;

use App\Models\Hrm\AttendanceRequest;
use Illuminate\Notifications\Notification;

class AttendanceRequestStatusNotification extends Notification
{
    public function __construct(
        private readonly AttendanceRequest $attendanceRequest,
        private readonly string $status
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $statusLabel = ucfirst($this->status);
        $date = $this->attendanceRequest->date->format('d M Y');

        return [
            'title' => "Attendance Request {$statusLabel}",
            'message' => "Your attendance request for {$date} has been {$this->status}.",
            'attendance_request_id' => $this->attendanceRequest->id,
            'status' => $this->status,
        ];
    }
}

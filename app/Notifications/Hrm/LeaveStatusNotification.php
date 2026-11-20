<?php

namespace App\Notifications\Hrm;

use App\Models\Hrm\LeaveRequest;
use Illuminate\Notifications\Notification;

class LeaveStatusNotification extends Notification
{
    public function __construct(
        private readonly LeaveRequest $leaveRequest,
        private readonly string $status
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $leaveType = $this->leaveRequest->leaveType?->name ?? 'Leave';
        $statusLabel = ucfirst($this->status);

        return [
            'title'            => "Leave Request {$statusLabel}",
            'message'          => "Your {$leaveType} request has been {$this->status}.",
            'leave_request_id' => $this->leaveRequest->id,
            'status'           => $this->status,
        ];
    }
}

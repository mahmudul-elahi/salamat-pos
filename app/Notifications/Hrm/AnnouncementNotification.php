<?php

namespace App\Notifications\Hrm;

use App\Models\Hrm\Announcement;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification
{
    public function __construct(private readonly Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'           => 'Announcement: ' . $this->announcement->title,
            'message'         => implode(' – ', array_filter([
                $this->announcement->start_date?->format('d M Y'),
                $this->announcement->end_date?->format('d M Y'),
            ])),
            'announcement_id' => $this->announcement->id,
        ];
    }
}

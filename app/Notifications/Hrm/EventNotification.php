<?php

namespace App\Notifications\Hrm;

use App\Models\Hrm\Event;
use Illuminate\Notifications\Notification;

class EventNotification extends Notification
{
    public function __construct(private readonly Event $event) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'    => 'New Event: ' . $this->event->title,
            'message'  => implode(' | ', array_filter([
                $this->event->start_datetime?->format('d M Y, h:i A'),
                $this->event->location,
            ])),
            'event_id' => $this->event->id,
        ];
    }
}

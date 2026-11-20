<?php

namespace App\Http\Controllers\Common\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;


class NotificationController extends Controller
{
    public function index()
    {
        $request = request();
        $user = auth()->user();

        // Base query
        $notifications = $user->notifications();

        // Count unread notifications separately
        $unreadCount = $user->unreadNotifications()->count();

        if ($request->status === 'read')
            $notifications = $notifications->whereNotNull('read_at');

        if ($request->status === 'unread')
            $notifications = $notifications->whereNull('read_at');

        $notifications = $notifications->latest('created_at');

        $date = json_decode(htmlspecialchars_decode($request->get('date')), true);

        $notifications = $notifications->when($date, function (Builder $builder) use ($date) {
            $builder->whereBetween(DB::raw('DATE(created_at)'), array_values($date));
        })->when($request->get('search'), function (Builder $builder) use ($request) {
            $builder->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.message")) LIKE ?', ["%" . $request->get('search') . "%"]);
        })->paginate($request->get('per_page', 10))->toArray();

        return [
            ...$notifications,
            'unread_count' => $unreadCount
        ];

    }

    public function markAsRead($id): \Illuminate\Http\JsonResponse
    {
        $notification = auth()->user()
            ->notifications()
            ->whereId($id)
            ->first();
        $notification->markAsRead();

        return successResponse("Notifications read successfully", $notification);
        
    }

    public function markAllAsRead(): \Illuminate\Http\JsonResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return successResponse("Notifications marked all as read successfully");

    }

}

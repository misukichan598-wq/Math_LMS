<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20);

        $data = $notifications->getCollection()->map(function ($notification) {
            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'icon' => $notification->getTypeIconAttribute(),
                'color' => $notification->getTypeColorAttribute(),
                'link' => $notification->link,
                'is_read' => $notification->is_read,
                'created_at' => $notification->created_at,
                'created_at_human' => $notification->created_at->diffForHumans(),
            ];
        });

        return $this->successResponse([
            'notifications' => $data,
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function unread(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->unread()
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'icon' => $notification->getTypeIconAttribute(),
                    'color' => $notification->getTypeColorAttribute(),
                    'link' => $notification->link,
                    'created_at' => $notification->created_at,
                    'created_at_human' => $notification->created_at->diffForHumans(),
                ];
            });

        return $this->successResponse($notifications);
    }

    public function unreadCount(Request $request)
    {
        $user = $request->user();

        $count = $user->notifications()->unread()->count();

        return $this->successResponse(['count' => $count]);
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        // Check ownership
        if ($notification->user_id !== $request->user()->id) {
            return $this->forbiddenResponse();
        }

        $notification->markAsRead();

        return $this->successResponse(null, 'Notification marked as read');
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        $user->notifications()->unread()->update(['is_read' => true]);

        return $this->successResponse(null, 'All notifications marked as read');
    }

    public function delete(Request $request, Notification $notification)
    {
        // Check ownership
        if ($notification->user_id !== $request->user()->id) {
            return $this->forbiddenResponse();
        }

        $notification->delete();

        return $this->successResponse(null, 'Notification deleted successfully');
    }
}

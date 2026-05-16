<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user.
     */
    public function getMyNotifications(Request $request)
    {
        $userId = $request->user()->id;
        $role = $request->query('role', $request->user()->role);

        $query = Notification::where('userId', $userId);
        
        if ($role) {
            $query->where('targetRole', $role);
        }

        $notifications = $query->orderBy('createdAt', 'desc')->get();

        return response()->json($notifications);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $userId = $request->user()->id;
        $role = $request->query('role', $request->user()->role);

        $query = Notification::where('userId', $userId)->where('isRead', false);
        
        if ($role) {
            $query->where('targetRole', $role);
        }

        $query->update(['isRead' => true]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Get unread count.
     */
    public function getUnreadCount(Request $request)
    {
        $userId = $request->user()->id;
        $role = $request->query('role', $request->user()->role);

        $query = Notification::where('userId', $userId)->where('isRead', false);
        
        if ($role) {
            $query->where('targetRole', $role);
        }

        $unreadCount = $query->count();

        return response()->json(['unreadCount' => $unreadCount]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where('id', $id)
            ->where('userId', $request->user()->id)
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->isRead = true;
        $notification->save();

        return response()->json($notification);
    }
}

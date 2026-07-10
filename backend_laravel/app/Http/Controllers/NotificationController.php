<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Utils\SocketUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    private function resolveNotificationRole(Request $request)
    {
        $requestedRole = trim(strtolower($request->query('role', '')));
        $validRoles = ['customer', 'seller', 'admin'];
        if (!$requestedRole) {
            return Auth::user()->role;
        }
        if (!in_array($requestedRole, $validRoles)) {
            return null;
        }
        if ($requestedRole !== Auth::user()->role) {
            return null;
        }
        return $requestedRole;
    }

    public function getMyNotifications(Request $request)
    {
        try {
            $userId = Auth::id();
            $role = $this->resolveNotificationRole($request);
            if ($request->query('role') && !$role) {
                return response()->json(['message' => 'Invalid notification role filter'], 400);
            }

            $query = Notification::where('userId', $userId);
            if ($role) {
                $query->where('targetRole', $role);
            }

            $notifications = $query->orderBy('createdAt', 'DESC')->get();
            return response()->json($notifications);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function markAllRead(Request $request)
    {
        try {
            $userId = Auth::id();
            $role = $this->resolveNotificationRole($request);
            if ($request->query('role') && !$role) {
                return response()->json(['message' => 'Invalid notification role filter'], 400);
            }

            $query = Notification::where('userId', $userId)->where('isRead', false);
            if ($role) {
                $query->where('targetRole', $role);
            }

            $query->update(['isRead' => true]);

            $unreadCount = Notification::where('userId', $userId)->where('isRead', false)->count();
            try {
                SocketUtility::emitNotificationCountUpdated($userId, $unreadCount);
            } catch (\Exception $broadcastErr) {
                // Socket server may be offline — broadcast failure should not break the API
                \Illuminate\Support\Facades\Log::warning('Broadcast failed in markAllRead: ' . $broadcastErr->getMessage());
            }

            return response()->json(['message' => 'All notifications marked as read']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getUnreadCount(Request $request)
    {
        try {
            $userId = Auth::id();
            $role = $this->resolveNotificationRole($request);
            if ($request->query('role') && !$role) {
                return response()->json(['message' => 'Invalid notification role filter'], 400);
            }

            $query = Notification::where('userId', $userId)->where('isRead', false);
            if ($role) {
                $query->where('targetRole', $role);
            }

            $unreadCount = $query->count();
            return response()->json(['unreadCount' => $unreadCount]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function markAsRead(string $id)
    {
        try {
            $notification = Notification::find($id);
            if (!$notification) {
                return response()->json(['message' => 'Notification not found'], 404);
            }
            if ($notification->userId !== Auth::id()) {
                return response()->json(['message' => 'You can only update your own notifications'], 403);
            }

            $notification->update(['isRead' => true]);

            $unreadCount = Notification::where('userId', Auth::id())->where('isRead', false)->count();
            try {
                SocketUtility::emitNotificationCountUpdated(Auth::id(), $unreadCount);
            } catch (\Exception $broadcastErr) {
                \Illuminate\Support\Facades\Log::warning('Broadcast failed in markAsRead: ' . $broadcastErr->getMessage());
            }

            return response()->json($notification);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}

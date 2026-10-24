<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationDismissal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    /**
     * Get all popup candidate notifications for the authenticated user.
     */
    public function getMyNotifications(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;
        $role = $request->query('role', $user->role);

        $query = Notification::query();

        if (in_array($role, ['admin', 'superadmin']) || in_array($user->role, ['admin', 'superadmin'])) {
            $targetRoles = in_array($role, ['superadmin']) || $user->role === 'superadmin'
                ? ['admin', 'superadmin']
                : ['admin'];

            $query->where(function ($q) use ($userId, $targetRoles) {
                $q->where('userId', $userId)
                  ->orWhere(function ($sub) use ($targetRoles) {
                      $sub->whereNull('userId')
                          ->whereIn('targetRole', $targetRoles);
                  });
            });

            $query->where(function ($q) use ($targetRoles) {
                $q->whereIn('targetRole', $targetRoles)
                  ->orWhereNull('targetRole');
            });
        } else {
            $query->where('userId', $userId);
            if ($role) {
                $query->where(function ($q) use ($role) {
                    $q->where('targetRole', $role)
                      ->orWhereNull('targetRole');
                });
            }
        }

        // Exclude dismissed notifications so dismissed alerts never reappear on refresh or login
        if (Schema::hasTable('notification_dismissals')) {
            $dismissedIds = NotificationDismissal::where('user_id', $userId)->pluck('notification_id');
            if ($dismissedIds->isNotEmpty()) {
                $query->whereNotIn('id', $dismissedIds);
            }
        }

        if (Schema::hasColumn('notifications', 'is_dismissed')) {
            $query->where(function ($q) {
                $q->whereNull('is_dismissed')
                  ->orWhere('is_dismissed', false);
            });
        }

        // Only unread notifications are candidates for live popup alerts
        $query->where('isRead', false);

        $notifications = $query->orderBy('createdAt', 'desc')->get();

        return response()->json($notifications);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;
        $role = $request->query('role', $user->role);

        $query = Notification::query()->where('isRead', false);

        if (in_array($role, ['admin', 'superadmin']) || in_array($user->role, ['admin', 'superadmin'])) {
            $targetRoles = in_array($role, ['superadmin']) || $user->role === 'superadmin'
                ? ['admin', 'superadmin']
                : ['admin'];

            $query->where(function ($q) use ($userId, $targetRoles) {
                $q->where('userId', $userId)
                  ->orWhere(function ($sub) use ($targetRoles) {
                      $sub->whereNull('userId')
                          ->whereIn('targetRole', $targetRoles);
                  });
            });
        } else {
            $query->where('userId', $userId);
            if ($role) {
                $query->where(function ($q) use ($role) {
                    $q->where('targetRole', $role)
                      ->orWhereNull('targetRole');
                });
            }
        }

        $query->update(['isRead' => true]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Get unread count.
     */
    public function getUnreadCount(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;
        $role = $request->query('role', $user->role);

        $query = Notification::query()->where('isRead', false);

        if (in_array($role, ['admin', 'superadmin']) || in_array($user->role, ['admin', 'superadmin'])) {
            $targetRoles = in_array($role, ['superadmin']) || $user->role === 'superadmin'
                ? ['admin', 'superadmin']
                : ['admin'];

            $query->where(function ($q) use ($userId, $targetRoles) {
                $q->where('userId', $userId)
                  ->orWhere(function ($sub) use ($targetRoles) {
                      $sub->whereNull('userId')
                          ->whereIn('targetRole', $targetRoles);
                  });
            });
        } else {
            $query->where('userId', $userId);
            if ($role) {
                $query->where(function ($q) use ($role) {
                    $q->where('targetRole', $role)
                      ->orWhereNull('targetRole');
                });
            }
        }

        $unreadCount = $query->count();

        return response()->json(['unreadCount' => $unreadCount]);
    }

    /**
     * Dismiss a popup notification so it never shows as a popup again.
     */
    public function dismiss(Request $request, $id)
    {
        $user = $request->user();
        $query = Notification::where('id', $id);

        if (in_array($user->role, ['admin', 'superadmin'])) {
            $targetRoles = $user->role === 'superadmin' ? ['admin', 'superadmin'] : ['admin'];
            $query->where(function ($q) use ($user, $targetRoles) {
                $q->where('userId', $user->id)
                  ->orWhereNull('userId')
                  ->orWhereIn('targetRole', $targetRoles);
            });
        } else {
            $query->where('userId', $user->id);
        }

        $notification = $query->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        if (Schema::hasTable('notification_dismissals')) {
            NotificationDismissal::firstOrCreate(
                ['user_id' => $user->id, 'notification_id' => $notification->id],
                ['created_at' => now()]
            );
        }

        if (Schema::hasColumn('notifications', 'is_dismissed')) {
            if ($notification->userId === $user->id) {
                $notification->is_dismissed = true;
                $notification->save();
            }
        }

        return response()->json(['message' => 'Notification dismissed', 'notification' => $notification]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $query = Notification::where('id', $id);

        if (in_array($user->role, ['admin', 'superadmin'])) {
            $targetRoles = $user->role === 'superadmin' ? ['admin', 'superadmin'] : ['admin'];
            $query->where(function ($q) use ($user, $targetRoles) {
                $q->where('userId', $user->id)
                  ->orWhereNull('userId')
                  ->orWhereIn('targetRole', $targetRoles);
            });
        } else {
            $query->where('userId', $user->id);
        }

        $notification = $query->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->isRead = true;
        if (Schema::hasTable('notification_dismissals')) {
            NotificationDismissal::firstOrCreate(
                ['user_id' => $user->id, 'notification_id' => $notification->id],
                ['created_at' => now()]
            );
        }
        if (Schema::hasColumn('notifications', 'is_dismissed')) {
            if ($notification->userId === $user->id) {
                $notification->is_dismissed = true;
            }
        }
        $notification->save();

        return response()->json($notification);
    }

    /**
     * Mark a notification as read and redirect to its link.
     */
    public function readAndRedirect(Request $request, $id)
    {
        $user = $request->user();
        $query = Notification::where('id', $id);

        if ($user && in_array($user->role, ['admin', 'superadmin'])) {
            $targetRoles = $user->role === 'superadmin' ? ['admin', 'superadmin'] : ['admin'];
            $query->where(function ($q) use ($user, $targetRoles) {
                $q->where('userId', $user->id)
                  ->orWhereNull('userId')
                  ->orWhereIn('targetRole', $targetRoles);
            });
        } elseif ($user) {
            $query->where('userId', $user->id);
        }

        $notification = $query->first();

        if ($notification) {
            $notification->isRead = true;
            if (Schema::hasTable('notification_dismissals')) {
                NotificationDismissal::firstOrCreate(
                    ['user_id' => $user->id, 'notification_id' => $notification->id],
                    ['created_at' => now()]
                );
            }
            if (Schema::hasColumn('notifications', 'is_dismissed')) {
                if ($notification->userId === $user->id) {
                    $notification->is_dismissed = true;
                }
            }
            $notification->save();

            $targetUrl = $notification->target_url;
            if (!empty($targetUrl) && $targetUrl !== '#') {
                return redirect($targetUrl);
            }
        }

        if ($user) {
            if ($user->role === 'superadmin') return redirect()->route('superadmin.dashboard');
            if ($user->role === 'admin') return redirect()->route('admin.dashboard');
            if ($user->role === 'seller') return redirect()->route('seller.dashboard');
        }

        return redirect()->route('profile.orders');
    }
}

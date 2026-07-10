<?php

namespace App\Utils;

use App\Events\LumbarongEvent;

class SocketUtility
{
    public static function emit(string $event, array $data, ?string $room = null): void
    {
        event(new LumbarongEvent($event, $data, $room));
    }

    public static function emitToUser(string $userId, string $event, array $data): void
    {
        self::emit($event, $data, "user_{$userId}");
    }

    public static function emitNotificationReceived(object|array $notification): void
    {
        $data = is_array($notification) ? $notification : $notification->toArray();
        self::emitToUser($notification->userId, 'new_notification', $data);
    }

    public static function emitNotificationCountUpdated(string $userId, int $count): void
    {
        self::emitToUser($userId, 'notification_count_update', [
            'userId' => $userId,
            'unreadCount' => $count
        ]);
    }

    public static function emitInventoryUpdated(object|array $product, array $metadata = []): void
    {
        $productData = is_array($product) ? $product : $product->toArray();
        self::emit('inventory_updated', array_merge(['product' => $productData], $metadata));
        self::emitStatsUpdate(['type' => 'inventory', 'productId' => $productData['id'], 'sellerId' => $productData['sellerId']]);
    }

    public static function emitOrderCreated(object|array $order): void
    {
        $orderData = is_array($order) ? $order : $order->toArray();
        self::emitToUser($order->customerId, 'order_created', $orderData);
        self::emitToUser($order->sellerId, 'new_order', $orderData);
        self::emit('order_created', $orderData, 'admin');
        self::emitStatsUpdate(['type' => 'order', 'sellerId' => $order->sellerId]);
    }

    public static function emitOrderUpdated(object|array $order, array $metadata = []): void
    {
        $orderData = is_array($order) ? $order : $order->toArray();
        $payload = array_merge(['orderId' => $orderData['id'], 'status' => $orderData['status']], $metadata);
        self::emitToUser($order->customerId, 'order_status_update', $payload);
        self::emitToUser($order->sellerId, 'order_status_update', $payload);
        self::emit('order_updated', $payload, 'admin');
        self::emitStatsUpdate(['type' => 'order_status', 'sellerId' => $order->sellerId]);
    }

    public static function emitUserUpdated(object|array $user, array $metadata = []): void
    {
        $userData = is_array($user) ? $user : $user->toArray();
        $payload = array_merge(['user' => $userData], $metadata);
        self::emitToUser($user->id, 'user_updated', $payload);
        self::emit('user_updated', $payload, 'admin');
        self::emitStatsUpdate(['type' => 'user']);
    }

    public static function emitDashboardUpdate(): void
    {
        self::emit('dashboard_update', ['timestamp' => now()->toIso8601String()]);
        self::emitStatsUpdate(['type' => 'dashboard']);
    }

    public static function emitStatsUpdate(array $metadata = []): void
    {
        self::emit('stats_update', array_merge(['timestamp' => now()->toIso8601String()], $metadata));
    }

    public static function broadcast(string $message, string $title = 'System Broadcast'): void
    {
        self::emit('broadcast_message', [
            'title' => $title,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
            'type' => 'system'
        ]);
    }

    public static function emitSettingsUpdated(array $settings): void
    {
        self::emit('settings_updated', $settings);
    }

    public static function emitForceLogout(string $userId, string $status, ?string $reason = null): void
    {
        self::emitToUser($userId, 'force_logout', [
            'status' => $status,
            'reason' => $reason,
            'message' => $status === 'blocked' ? 'Account Terminated' : 'Account Frozen',
        ]);
    }
}

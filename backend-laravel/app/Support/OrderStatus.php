<?php

namespace App\Support;

class OrderStatus
{
    // Canonical statuses stored in database
    public const PENDING = 'Pending';
    public const PROCESSING = 'Processing';
    public const SHIPPED = 'Shipped';
    public const DELIVERED = 'Delivered';
    public const COMPLETED = 'Completed';
    public const CANCELLED = 'Cancelled';
    public const CANCELLATION_PENDING = 'cancellation pending';
    public const RETURNED = 'Returned';

    /**
     * Map any incoming raw status to canonical database format.
     */
    public static function canonicalize(?string $status): string
    {
        if (!$status) {
            return self::PENDING;
        }

        $lower = strtolower(trim($status));
        return match ($lower) {
            'pending' => self::PENDING,
            'processing' => self::PROCESSING,
            'shipped', 'in transit', 'in_transit' => self::SHIPPED,
            'delivered' => self::DELIVERED,
            'completed', 'received by buyer', 'received_by_buyer', 'received' => self::COMPLETED,
            'cancelled', 'canceled' => self::CANCELLED,
            'cancellation pending', 'cancellation_pending', 'cancellation requested' => self::CANCELLATION_PENDING,
            'returned' => self::RETURNED,
            default => ucfirst(trim($status)),
        };
    }

    /**
     * All status strings that represent completed/delivered orders across legacy and canonical records.
     *
     * @return array<int, string>
     */
    public static function completedStatuses(): array
    {
        return [
            self::DELIVERED,
            self::COMPLETED,
            'Received by Buyer',
            'received by buyer',
            'received_by_buyer',
            'delivered',
            'completed',
        ];
    }

    /**
     * All status strings that represent active (non-cancelled) orders.
     *
     * @return array<int, string>
     */
    public static function activeStatuses(): array
    {
        return [
            self::PENDING,
            self::PROCESSING,
            self::SHIPPED,
            self::DELIVERED,
            self::COMPLETED,
            'pending',
            'processing',
            'shipped',
            'in transit',
            'in_transit',
            'delivered',
            'completed',
            'Received by Buyer',
            'received by buyer',
        ];
    }

    /**
     * Check if a status represents completion/delivery.
     */
    public static function isCompleted(?string $status): bool
    {
        if (!$status) {
            return false;
        }
        $canonical = self::canonicalize($status);
        return in_array($canonical, [self::DELIVERED, self::COMPLETED], true);
    }

    /**
     * Check if order is eligible for refund or return request.
     */
    public static function isEligibleForReturnOrRefund(?string $status): bool
    {
        return self::isCompleted($status);
    }
}

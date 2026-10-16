<?php

namespace App\Services\Courier;

class CourierStatusMapper
{
    /**
     * Map vendor-specific scan status or event description to normalized LumBarong checkpoint status.
     * Checkpoint statuses:
     * - 'registered'       (Waybill created, pending courier pickup)
     * - 'picked_up'        (Parcel collected from seller)
     * - 'in_transit'       (Departed origin or arriving at distribution hub)
     * - 'out_for_delivery' (Dispatched with local delivery rider)
     * - 'delivered'        (Successfully delivered to recipient)
     * - 'failed_delivery'  (Attempted delivery failed, e.g. recipient unavailable)
     * - 'returned'         (Parcel being returned to sender / failed attempts exhausted)
     *
     * @param string|null $rawStatus
     * @param string|null $description
     * @return string
     */
    public static function normalize(?string $rawStatus, ?string $description = ''): string
    {
        $statusLower = strtolower(trim($rawStatus ?? ''));
        $descLower = strtolower(trim($description ?? ''));
        $combined = $statusLower . ' ' . $descLower;

        // 1. Delivered
        if (
            str_contains($combined, 'delivered') ||
            str_contains($combined, 'signed') ||
            str_contains($combined, 'received by') ||
            str_contains($combined, 'successful delivery') ||
            str_contains($statusLower, 'pod') ||
            str_contains($statusLower, 'dlv')
        ) {
            return 'delivered';
        }

        // 2. Returned to Sender
        if (
            str_contains($combined, 'returned to sender') ||
            str_contains($combined, 'return to sender') ||
            str_contains($combined, 'rts') ||
            str_contains($combined, 'returned') ||
            str_contains($statusLower, 'return')
        ) {
            return 'returned';
        }

        // 3. Failed Delivery
        if (
            str_contains($combined, 'failed delivery') ||
            str_contains($combined, 'delivery attempt failed') ||
            str_contains($combined, 'consignee unavailable') ||
            str_contains($combined, 'unsuccessful delivery') ||
            str_contains($combined, 'rescheduled') ||
            str_contains($statusLower, 'fail') ||
            str_contains($statusLower, 'undelivered')
        ) {
            return 'failed_delivery';
        }

        // 4. Out for Delivery
        if (
            str_contains($combined, 'out for delivery') ||
            str_contains($combined, 'with courier rider') ||
            str_contains($combined, 'with delivery courier') ||
            str_contains($combined, 'delivering') ||
            str_contains($statusLower, 'ofd')
        ) {
            return 'out_for_delivery';
        }

        // 5. Picked up
        if (
            str_contains($combined, 'picked up') ||
            str_contains($combined, 'pickup complete') ||
            str_contains($combined, 'received at origin') ||
            str_contains($combined, 'parcel collected') ||
            str_contains($statusLower, 'pickup')
        ) {
            return 'picked_up';
        }

        // 6. Registered / Created
        if (
            str_contains($combined, 'registered') ||
            str_contains($combined, 'created') ||
            str_contains($combined, 'manifested') ||
            str_contains($combined, 'label created') ||
            str_contains($combined, 'electronic information received')
        ) {
            return 'registered';
        }

        // Default: in_transit for any general scans
        return 'in_transit';
    }

    /**
     * Map normalized checkpoint status to canonical LumBarong Order status.
     * Note: Returns null if checkpoint status does NOT change the canonical Order status.
     * For example: 'out_for_delivery', 'failed_delivery', or intermediate 'in_transit' scans
     * keep the Order status as 'In Transit'.
     *
     * @param string $checkpointStatus
     * @param string|null $currentOrderStatus
     * @return string|null
     */
    public static function mapToCanonicalOrderStatus(string $checkpointStatus, ?string $currentOrderStatus = null): ?string
    {
        $current = strtolower(str_replace('_', ' ', $currentOrderStatus ?? ''));

        // Never advance Completed or Cancelled orders
        if (in_array($current, ['completed', 'cancelled', 'declined'], true)) {
            return null;
        }

        return match ($checkpointStatus) {
            // Picked up or in-transit moves 'To Ship' / 'Shipped' orders to 'In Transit'
            'picked_up', 'in_transit' => in_array($current, ['pending', 'to ship', 'shipped', 'ready_to_ship'], true) ? 'In Transit' : null,

            // Out for delivery or failed delivery: order remains 'In Transit'
            'out_for_delivery', 'failed_delivery' => in_array($current, ['to ship', 'shipped', 'ready_to_ship'], true) ? 'In Transit' : null,

            // Delivered transitions order to 'Delivered' (Never to 'Completed')
            'delivered' => ($current !== 'delivered') ? 'Delivered' : null,

            // 'returned' does NOT auto-cancel; it remains in transit/held for inspection
            'returned', 'registered' => null,

            default => null,
        };
    }
}

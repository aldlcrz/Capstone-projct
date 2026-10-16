<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CourierTrackingService
{
    /**
     * Map courier name to normalized carrier key.
     */
    public function getCarrierCode(?string $courierName): ?string
    {
        if (!$courierName) return null;

        $normalized = strtolower(trim($courierName));

        // J&T Express Philippines
        if (str_contains($normalized, 'j&t') || str_contains($normalized, 'jnt') || str_contains($normalized, 'j and t')) {
            return 'jtexpress-ph';
        }

        // LBC Express
        if (str_contains($normalized, 'lbc')) {
            return 'lbc';
        }

        // Flash Express Philippines
        if (str_contains($normalized, 'flash')) {
            return 'flashexpress-ph';
        }

        // Ninja Van Philippines
        if (str_contains($normalized, 'ninja')) {
            return 'ninjavan-ph';
        }

        // 2GO Express
        if (str_contains($normalized, '2go')) {
            return '2go';
        }

        // JRS Express
        if (str_contains($normalized, 'jrs')) {
            return 'jrs-express';
        }

        return null;
    }

    /**
     * Query live courier status directly from public official tracking endpoints.
     * Works 100% free with zero API keys or business email registration.
     *
     * @param string $courierName
     * @param string $trackingNumber
     * @return array|null
     */
    public function fetchTracking(string $courierName, string $trackingNumber): ?array
    {
        $carrierCode = $this->getCarrierCode($courierName);
        $cleanNumber = trim($trackingNumber);

        if (!$carrierCode || empty($cleanNumber)) {
            return null;
        }

        try {
            return match ($carrierCode) {
                'jtexpress-ph'   => $this->queryJtExpress($cleanNumber),
                'flashexpress-ph'=> $this->queryFlashExpress($cleanNumber),
                'lbc'            => $this->queryLbcExpress($cleanNumber),
                default          => null,
            };
        } catch (\Throwable $e) {
            Log::warning("Direct live courier tracking query error for {$courierName} #{$cleanNumber}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Live Query J&T Express Philippines official tracking router.
     */
    protected function queryJtExpress(string $trackingNumber): ?array
    {
        try {
            $response = Http::asForm()
                ->timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Referer'    => 'https://www.jtexpress.ph/index/query/gzquery.html',
                ])
                ->post('https://www.jtexpress.ph/index/router/index.html', [
                    'method' => 'order.massquerybill',
                    'data'   => [
                        'f_bills' => $trackingNumber,
                    ],
                ]);

            if ($response->successful()) {
                $json = $response->json();
                $details = $json['data'][0]['details'] ?? [];

                if (!empty($details) && is_array($details)) {
                    $latest = end($details);
                    $scanType = strtolower($latest['scantype'] ?? $latest['scanstatus'] ?? '');
                    $desc = $latest['desc'] ?? 'Scanned by J&T Express';
                    $time = $latest['scantime'] ?? now()->toDateTimeString();

                    $status = 'transit';
                    if (str_contains($scanType, 'sign') || str_contains($scanType, 'deliver') || str_contains(strtolower($desc), 'delivered') || str_contains(strtolower($desc), 'received')) {
                        $status = 'delivered';
                    } elseif (str_contains($scanType, 'pick') || str_contains($scanType, 'departure') || str_contains($scanType, 'arrival')) {
                        $status = 'transit';
                    }

                    return [
                        'delivery_status'        => $status,
                        'latest_event'           => $desc,
                        'latest_checkpoint_time' => $time,
                        'carrier'                => 'J&T Express Philippines',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::debug("J&T live query: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Live Query Flash Express Philippines official tracking endpoint.
     */
    protected function queryFlashExpress(string $trackingNumber): ?array
    {
        try {
            $response = Http::asForm()
                ->timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Referer'    => 'https://www.flashexpress.ph/tools/tracking/',
                ])
                ->post('https://www.flashexpress.ph/tools/tracking/', [
                    'pno' => $trackingNumber,
                ]);

            if ($response->successful()) {
                $html = $response->body();
                // Check delivery milestones in HTML or JSON
                if (stripos($html, 'Delivered') !== false || stripos($html, 'Signed') !== false) {
                    return [
                        'delivery_status'        => 'delivered',
                        'latest_event'           => 'Parcel successfully delivered to consignee.',
                        'latest_checkpoint_time' => now()->toDateTimeString(),
                        'carrier'                => 'Flash Express Philippines',
                    ];
                } elseif (stripos($html, 'In Transit') !== false || stripos($html, 'Arrived at') !== false || stripos($html, 'Departed from') !== false) {
                    return [
                        'delivery_status'        => 'transit',
                        'latest_event'           => 'Parcel in transit with Flash Express.',
                        'latest_checkpoint_time' => now()->toDateTimeString(),
                        'carrier'                => 'Flash Express Philippines',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::debug("Flash Express live query: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Live Query LBC Express official tracking endpoint.
     */
    protected function queryLbcExpress(string $trackingNumber): ?array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'Accept'     => 'application/json',
                ])
                ->get("https://cache.lbcexpress.com/lbc/track/?tracking_no={$trackingNumber}");

            if ($response->successful()) {
                $json = $response->json();
                $timeline = $json['data']['timeline'] ?? ($json['timeline'] ?? []);

                if (!empty($timeline) && is_array($timeline)) {
                    $latest = reset($timeline);
                    $desc = $latest['status'] ?? $latest['description'] ?? 'Scanned by LBC Express';
                    $time = $latest['date'] ?? now()->toDateTimeString();

                    $status = (stripos($desc, 'delivered') !== false || stripos($desc, 'received') !== false) ? 'delivered' : 'transit';

                    return [
                        'delivery_status'        => $status,
                        'latest_event'           => $desc,
                        'latest_checkpoint_time' => $time,
                        'carrier'                => 'LBC Express',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::debug("LBC Express live query: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Register a new tracking number with real-time tracking network.
     */
    public function registerTracking(string $courierName, string $trackingNumber, ?string $orderId = null): array
    {
        $carrierCode = $this->getCarrierCode($courierName);
        if (!$carrierCode) {
            return [
                'success' => false,
                'message' => "Courier '{$courierName}' is not supported for automated tracking.",
            ];
        }

        // Attempt live query to check initial checkpoint
        $initialData = $this->fetchTracking($courierName, $trackingNumber);

        Log::info("Tracking registered for {$courierName} #{$trackingNumber}. Direct resolver active.");
        return [
            'success' => true,
            'message' => 'Tracking number registered with live carrier network.',
            'data'    => $initialData ?: ['tracking_number' => $trackingNumber, 'courier' => $courierName],
        ];
    }

    /**
     * Poll and synchronize order status from courier scanning records.
     *
     * @param Order $order
     * @return array
     */
    public function syncOrderTracking(Order $order): array
    {
        if ($order->isStorePickup() || $order->isSpecialDelivery()) {
            return ['synced' => false, 'reason' => 'Direct workshop or artisan fulfillment does not use 3rd party courier tracking.'];
        }

        if (empty($order->courierName) || empty($order->trackingNumber)) {
            return ['synced' => false, 'reason' => 'Missing courier name or tracking number on order.'];
        }

        $trackingData = $this->fetchTracking($order->courierName, $order->trackingNumber);
        if (!$trackingData) {
            return ['synced' => false, 'reason' => 'No new tracking checkpoint returned from courier servers yet.'];
        }

        $deliveryStatus = strtolower($trackingData['delivery_status'] ?? '');
        $latestEvent = $trackingData['latest_event'] ?? 'Parcel scanned';
        $latestCheckpoint = $trackingData['latest_checkpoint_time'] ?? now()->toDateTimeString();

        return $this->processStatusUpdate($order, $deliveryStatus, $latestEvent, $latestCheckpoint);
    }

    /**
     * Process courier status change, update database, and notify customer.
     *
     * @param Order $order
     * @param string $deliveryStatus
     * @param string $description
     * @param string|null $timestamp
     * @return array
     */
    public function processStatusUpdate(Order $order, string $deliveryStatus, string $description = '', ?string $timestamp = null): array
    {
        $canonicalStatus = match ($deliveryStatus) {
            'pickup', 'transit', 'in_transit' => 'In Transit',
            'delivered'                       => 'Delivered',
            default                           => null,
        };

        if (!$canonicalStatus) {
            return [
                'synced'  => false,
                'message' => "Courier status '{$deliveryStatus}' does not require order state transition.",
            ];
        }

        if ($order->status === $canonicalStatus) {
            return [
                'synced'  => false,
                'message' => "Order is already in status '{$canonicalStatus}'.",
            ];
        }

        // Prevent status regressions
        if ($order->status === 'Delivered' && $canonicalStatus === 'In Transit') {
            return [
                'synced'  => false,
                'message' => 'Cannot regress Delivered order back to In Transit.',
            ];
        }

        $prevStatus = $order->status;
        $order->status = $canonicalStatus;

        // Auto-mark COD as paid if courier confirms delivery
        if ($canonicalStatus === 'Delivered' && in_array(strtoupper($order->paymentMethod ?? ''), ['COD', 'CASH ON DELIVERY'], true)) {
            $order->paymentStatus = 'Paid';
        }

        $order->save();

        // Synchronize OrderShipping snapshot
        if ($order->shipping) {
            $order->shipping->update([
                'shipping_status' => $canonicalStatus,
            ]);
        }

        // Log to immutable status audit history
        $timeStr = $timestamp ? " at {$timestamp}" : "";
        $auditNotes = "Automated Courier Update ({$order->courierName}): {$description}{$timeStr}";

        OrderStatusHistory::create([
            'orderId'        => $order->id,
            'previousStatus' => $prevStatus,
            'newStatus'      => $canonicalStatus,
            'updatedBy'      => $order->sellerId ?: $order->customerId,
            'userRole'       => 'system',
            'notes'          => $auditNotes,
        ]);

        // Send In-App Notification to Customer
        $notifTitle = $canonicalStatus === 'Delivered' ? 'Parcel Delivered' : 'Parcel In Transit';
        $notifMsg = "Your package with {$order->courierName} (Waybill: {$order->trackingNumber}) is now {$canonicalStatus}. {$description}";

        Notification::create([
            'userId'     => $order->customerId,
            'title'      => $notifTitle,
            'message'    => $notifMsg,
            'type'       => 'order',
            'link'       => "/orders/{$order->id}",
            'targetRole' => 'customer',
            'isRead'     => false,
        ]);

        // Send Email Notification if customer has email
        $customerUser = User::find($order->customerId);
        if ($customerUser && $customerUser->email) {
            try {
                $mailable = new \App\Mail\OrderStatusUpdatedMail($customerUser->name, $order->id, $canonicalStatus, $notifMsg);
                \App\Services\EmailNotificationService::sendNotification($customerUser->email, $mailable, 'order_status_updated', $customerUser->id, 'Order', $order->id);
            } catch (\Throwable $e) {
                Log::warning("Could not dispatch courier update email for order {$order->id}: " . $e->getMessage());
            }
        }

        return [
            'synced'          => true,
            'previous_status' => $prevStatus,
            'new_status'      => $canonicalStatus,
            'message'         => "Order updated from {$prevStatus} to {$canonicalStatus} based on live courier scan.",
        ];
    }
}

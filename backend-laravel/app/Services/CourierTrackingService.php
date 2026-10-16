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
    protected ?string $apiKey;
    protected string $baseUrl = 'https://api.trackingmore.com/v4';

    public function __construct()
    {
        $this->apiKey = config('services.trackingmore.api_key') ?: env('TRACKINGMORE_API_KEY');
    }

    /**
     * Map courier name to TrackingMore carrier identifier code.
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
     * Check whether real-time tracking is supported for the given courier.
     */
    public function isSupportedCourier(?string $courierName): bool
    {
        return $this->getCarrierCode($courierName) !== null;
    }

    /**
     * Check if the API key is configured.
     */
    public function hasApiKey(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Register a new tracking number with the real-time tracking provider.
     *
     * @param string $courierName
     * @param string $trackingNumber
     * @param string|null $orderId
     * @return array
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

        if (!$this->hasApiKey()) {
            Log::info("TrackingMore API Key not set. Tracking #{$trackingNumber} for {$courierName} not registered remotely.");
            return [
                'success' => false,
                'message' => 'TrackingMore API Key is not configured in .env (TRACKINGMORE_API_KEY).',
            ];
        }

        try {
            $payload = [
                'tracking_number' => trim($trackingNumber),
                'courier_code'    => $carrierCode,
                'auto_correct'    => 1,
            ];
            if ($orderId) {
                $payload['order_number'] = $orderId;
            }

            $response = Http::withHeaders([
                'Tracking-Api-Key' => $this->apiKey,
                'Content-Type'     => 'application/json',
            ])->timeout(10)->post("{$this->baseUrl}/trackings/create", $payload);

            $data = $response->json();
            $code = $data['meta']['code'] ?? $response->status();

            // TrackingMore returns 200 or 4016 (already exists/registered)
            if ($response->successful() || ($code === 4016 || $code === 200)) {
                Log::info("Successfully registered real-time tracking for {$courierName} #{$trackingNumber}");
                return [
                    'success' => true,
                    'message' => 'Tracking registered successfully with courier tracking network.',
                    'data'    => $data['data'] ?? [],
                ];
            }

            Log::warning("TrackingMore API registration response [{$code}]: " . json_encode($data));
            return [
                'success' => false,
                'message' => $data['meta']['message'] ?? 'Failed to register with tracking network.',
                'code'    => $code,
            ];
        } catch (\Throwable $e) {
            Log::error("Failed to call TrackingMore register API: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Query real-time tracking data for a waybill.
     *
     * @param string $courierName
     * @param string $trackingNumber
     * @return array|null
     */
    public function fetchTracking(string $courierName, string $trackingNumber): ?array
    {
        $carrierCode = $this->getCarrierCode($courierName);
        if (!$carrierCode || !$this->hasApiKey()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Tracking-Api-Key' => $this->apiKey,
                'Content-Type'     => 'application/json',
            ])->timeout(10)->get("{$this->baseUrl}/trackings/get", [
                'tracking_numbers' => trim($trackingNumber),
                'courier_code'    => $carrierCode,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['data'] ?? [];
                return is_array($items) && count($items) > 0 ? $items[0] : null;
            }

            return null;
        } catch (\Throwable $e) {
            Log::error("Failed to fetch TrackingMore tracking details: " . $e->getMessage());
            return null;
        }
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
            return ['synced' => false, 'reason' => 'Direct or workshop fulfillment does not use 3rd party courier tracking.'];
        }

        if (empty($order->courierName) || empty($order->trackingNumber)) {
            return ['synced' => false, 'reason' => 'Missing courier or tracking number on order.'];
        }

        $trackingData = $this->fetchTracking($order->courierName, $order->trackingNumber);
        if (!$trackingData) {
            return ['synced' => false, 'reason' => 'No tracking update received from courier network.'];
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

        // Prevent regressions
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

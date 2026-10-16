<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CourierTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourierTrackingWebhookController extends Controller
{
    protected CourierTrackingService $trackingService;

    public function __construct(CourierTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Handle incoming real-time courier tracking webhooks.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info("Received Courier Tracking Webhook: " . json_encode($payload));

        // Format 1: Standard TrackingMore / Courier Webhook Payload
        $data = $payload['data'] ?? null;
        if (is_array($data) && !empty($data['tracking_number'])) {
            $trackingNumber = trim($data['tracking_number']);
            $deliveryStatus = strtolower($data['delivery_status'] ?? '');
            $eventDesc = $data['latest_event'] ?? ($data['substatus'] ?? 'Parcel scanned by carrier.');
            $timestamp = $data['latest_checkpoint_time'] ?? now()->toDateTimeString();
        } 
        // Format 2: Direct / Simulation Webhook Payload
        elseif (!empty($payload['tracking_number']) || !empty($payload['trackingNumber'])) {
            $trackingNumber = trim($payload['tracking_number'] ?? $payload['trackingNumber']);
            $rawStatus = strtolower($payload['status'] ?? ($payload['delivery_status'] ?? 'transit'));
            $deliveryStatus = match($rawStatus) {
                'in transit', 'transit', 'picked up', 'pickup', 'shipped' => 'transit',
                'delivered', 'completed'                                 => 'delivered',
                default                                                  => $rawStatus,
            };
            $eventDesc = $payload['description'] ?? ($payload['event'] ?? 'Parcel scanned at courier hub.');
            $timestamp = $payload['timestamp'] ?? now()->toDateTimeString();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook payload: tracking_number is required.',
            ], 422);
        }

        // Locate order by tracking number
        $order = Order::where('trackingNumber', $trackingNumber)->first();
        if (!$order) {
            Log::warning("Courier webhook received for unknown tracking number: {$trackingNumber}");
            return response()->json([
                'success' => false,
                'message' => "Order with tracking number '{$trackingNumber}' not found.",
            ], 404);
        }

        // Process status update and notify customer
        $result = $this->trackingService->processStatusUpdate($order, $deliveryStatus, $eventDesc, $timestamp);

        return response()->json([
            'success' => true,
            'result'  => $result,
            'order'   => [
                'id'             => $order->id,
                'status'         => $order->status,
                'courier'        => $order->courierName,
                'trackingNumber' => $order->trackingNumber,
            ],
        ]);
    }
}

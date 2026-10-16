<?php

namespace App\Services\Courier\Adapters;

use App\Services\Courier\Contracts\CourierTrackingProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FlashExpressAdapter implements CourierTrackingProviderInterface
{
    public function getCarrierCode(): string
    {
        return 'flashexpress-ph';
    }

    public function getDisplayName(): string
    {
        return 'Flash Express Philippines';
    }

    /**
     * Authenticate Flash Express Webhook.
     * Official Flash Open Platform specification:
     * sign = MD5(sorted_parameters + sign_key)
     */
    public function validateWebhookSignature(Request $request): bool
    {
        $signKey = config('services.flashexpress.sign_key') ?? env('FLASHEXPRESS_SIGN_KEY');

        // 1. Enterprise signature verification if sign_key is configured
        if (!empty($signKey)) {
            $params = $request->except(['sign']);
            ksort($params);
            $signStr = '';
            foreach ($params as $k => $v) {
                if ($v !== null && $v !== '') {
                    $signStr .= $k . '=' . (is_array($v) ? json_encode($v) : $v) . '&';
                }
            }
            $signStr .= 'key=' . $signKey;
            $expectedSign = strtoupper(md5($signStr));
            $providedSign = strtoupper((string)($request->input('sign') ?? $request->header('X-Flash-Signature', '')));

            if (!empty($providedSign) && hash_equals($expectedSign, $providedSign)) {
                return true;
            }
        }

        // 2. Non-production staging test-secret fallback (Never in production)
        if (app()->environment('local', 'testing', 'staging')) {
            $testSecret = config('services.courier.webhook_secret') ?? env('COURIER_WEBHOOK_SECRET', 'lumbarong_courier_test_secret_2026');
            $providedSecret = $request->header('X-Lumbarong-Signature') ?? $request->header('X-Webhook-Secret') ?? $request->input('secret');
            if ($providedSecret && hash_equals($testSecret, $providedSecret)) {
                return true;
            }
        }

        return false;
    }

    public function parseWebhookPayload(Request $request): ?array
    {
        $payload = $request->all();

        $trackingNumber = trim((string)($payload['pno'] ?? ($payload['tracking_number'] ?? ($payload['pNo'] ?? ''))));
        if (empty($trackingNumber)) {
            return null;
        }

        $rawStatus = (string)($payload['state'] ?? ($payload['status'] ?? ($payload['delivery_status'] ?? 'transit')));
        $desc = (string)($payload['message'] ?? ($payload['description'] ?? ($payload['detail'] ?? 'Scanned by Flash Express')));
        $location = $payload['station'] ?? ($payload['location'] ?? null);
        $time = $payload['action_time'] ?? ($payload['timestamp'] ?? now()->toDateTimeString());

        return [
            'tracking_number' => $trackingNumber,
            'raw_status'      => $rawStatus,
            'description'     => $desc,
            'location'        => $location,
            'occurred_at'     => $time,
            'raw_payload'     => $this->sanitizePayload($payload),
        ];
    }

    public function fetchTracking(string $trackingNumber): ?array
    {
        // Direct querying requires official enterprise credentials.
        return null;
    }

    protected function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = ['sign', 'sign_key', 'token', 'secret', 'phone', 'mobile'];
        $clean = [];
        foreach ($payload as $k => $v) {
            if (in_array(strtolower($k), $sensitiveKeys, true)) {
                continue;
            }
            $clean[$k] = is_array($v) ? $this->sanitizePayload($v) : $v;
        }
        return $clean;
    }
}

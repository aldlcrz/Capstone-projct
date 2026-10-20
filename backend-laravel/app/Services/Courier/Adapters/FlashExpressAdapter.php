<?php

namespace App\Services\Courier\Adapters;

use App\Services\Courier\Contracts\CourierTrackingProviderInterface;
use App\Services\Courier\Contracts\CourierRateProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlashExpressAdapter implements CourierTrackingProviderInterface, CourierRateProviderInterface
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
        $mchId = config('services.flashexpress.mch_id') ?? env('FLASHEXPRESS_MCH_ID');
        $signKey = config('services.flashexpress.sign_key') ?? env('FLASHEXPRESS_SIGN_KEY');
        $apiUrl = config('services.flashexpress.api_url') ?? env('FLASHEXPRESS_API_URL', 'https://open-api.flashexpress.ph');

        if (empty($mchId) || empty($signKey)) {
            return null;
        }

        try {
            $params = [
                'mchId' => $mchId,
                'pno'   => $trackingNumber,
            ];
            ksort($params);
            $signStr = '';
            foreach ($params as $k => $v) {
                if ($v !== null && $v !== '') {
                    $signStr .= $k . '=' . $v . '&';
                }
            }
            $signStr .= 'key=' . $signKey;
            $params['sign'] = strtoupper(md5($signStr));

            $response = Http::timeout(3.5)->asForm()->post(rtrim($apiUrl, '/') . '/open/v1/orders/track', $params);
            if ($response->successful()) {
                $data = $response->json();
                if (($data['code'] ?? null) == 1 && !empty($data['data'])) {
                    return [
                        'carrier'         => $this->getDisplayName(),
                        'tracking_number' => $trackingNumber,
                        'events'          => $data['data']['routes'] ?? [],
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Flash Express tracking fetch failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Real-time Flash Express rate calculation.
     */
    public function calculateRate(
        string $originPostalCode,
        string $destProvince,
        string $destCity,
        ?string $destPostalCode,
        float $actualWeightKg,
        float $lengthCm,
        float $widthCm,
        float $heightCm
    ): ?array {
        $mchId = config('services.flashexpress.mch_id') ?? env('FLASHEXPRESS_MCH_ID');
        $signKey = config('services.flashexpress.sign_key') ?? env('FLASHEXPRESS_SIGN_KEY');
        $apiUrl = config('services.flashexpress.api_url') ?? env('FLASHEXPRESS_API_URL', 'https://open-api.flashexpress.ph');

        if (empty($mchId) || empty($signKey)) {
            return null;
        }

        try {
            $weightGrams = (int) round($actualWeightKg * 1000);
            $params = [
                'mchId'         => $mchId,
                'srcPostalCode' => $originPostalCode ?: '4014',
                'dstPostalCode' => $destPostalCode ?: '1000',
                'weight'        => max(100, $weightGrams),
                'width'         => max(1, (int) round($widthCm)),
                'length'        => max(1, (int) round($lengthCm)),
                'height'        => max(1, (int) round($heightCm)),
            ];
            ksort($params);
            $signStr = '';
            foreach ($params as $k => $v) {
                if ($v !== null && $v !== '') {
                    $signStr .= $k . '=' . $v . '&';
                }
            }
            $signStr .= 'key=' . $signKey;
            $params['sign'] = strtoupper(md5($signStr));

            $response = Http::timeout(2.5)->asForm()->post(rtrim($apiUrl, '/') . '/open/v1/orders/fee', $params);
            if ($response->successful()) {
                $data = $response->json();
                if (($data['code'] ?? null) == 1 && isset($data['data']['price'])) {
                    $fee = (float) $data['data']['price'];
                    return [
                        'fee'      => $fee,
                        'min_days' => (int) ($data['data']['min_days'] ?? 2),
                        'max_days' => (int) ($data['data']['max_days'] ?? 4),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::info("Flash Express live rate inquiry bypassed: " . $e->getMessage());
        }

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

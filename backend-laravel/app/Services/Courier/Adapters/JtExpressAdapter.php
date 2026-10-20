<?php

namespace App\Services\Courier\Adapters;

use App\Services\Courier\Contracts\CourierTrackingProviderInterface;
use App\Services\Courier\Contracts\CourierRateProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JtExpressAdapter implements CourierTrackingProviderInterface, CourierRateProviderInterface
{
    public function getCarrierCode(): string
    {
        return 'jtexpress-ph';
    }

    public function getDisplayName(): string
    {
        return 'J&T Express Philippines';
    }

    /**
     * Authenticate J&T Express Webhook.
     * Official enterprise specification:
     * Header 'digest' = Base64(MD5(body + apiKey)) OR RSA-SHA256 signature.
     * In non-production environments, allows staging secret if configured.
     */
    public function validateWebhookSignature(Request $request): bool
    {
        $rawBody = $request->getContent();
        $apiKey = config('services.jtexpress.api_key') ?? env('JTEXPRESS_API_KEY');

        // 1. Enterprise signature verification if API key is configured
        if (!empty($apiKey)) {
            $expectedDigest = base64_encode(md5($rawBody . $apiKey, true));
            $providedDigest = $request->header('digest') ?? $request->header('Sign');
            if ($providedDigest && hash_equals($expectedDigest, $providedDigest)) {
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

        // Support both J&T enterprise callback structure and normalized wrapper
        $data = $payload['data'] ?? $payload;
        if (isset($data[0]) && is_array($data[0])) {
            $data = $data[0];
        }

        $trackingNumber = trim((string)($data['billcode'] ?? ($data['billCode'] ?? ($data['tracking_number'] ?? ($payload['tracking_number'] ?? '')))));
        if (empty($trackingNumber)) {
            return null;
        }

        $rawStatus = (string)($data['scantype'] ?? ($data['scanStatus'] ?? ($data['status'] ?? ($payload['status'] ?? 'in_transit'))));
        $desc = (string)($data['desc'] ?? ($data['reason'] ?? ($data['description'] ?? ($payload['description'] ?? 'Scanned by J&T Express'))));
        $location = $data['scandest'] ?? ($data['scanNetworkName'] ?? ($data['location'] ?? null));
        $time = $data['scantime'] ?? ($data['scanTime'] ?? ($payload['timestamp'] ?? now()->toDateTimeString()));

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
        $apiKey = config('services.jtexpress.api_key') ?? env('JTEXPRESS_API_KEY');
        $customerCode = config('services.jtexpress.customer_code') ?? env('JTEXPRESS_CUSTOMER_CODE');
        $apiUrl = config('services.jtexpress.api_url') ?? env('JTEXPRESS_API_URL', 'https://openapi.jtexpress.ph');

        if (empty($apiKey) || empty($customerCode)) {
            return null;
        }

        try {
            $data = json_encode(['billCodes' => $trackingNumber]);
            $digest = base64_encode(md5($data . $apiKey, true));

            $response = Http::timeout(3.5)->asForm()->post(rtrim($apiUrl, '/') . '/web/logistics/trace', [
                'logistics_interface' => $data,
                'data_digest'         => $digest,
                'msg_type'            => 'TRACK_QUERY',
                'eccompanyid'         => $customerCode,
            ]);

            if ($response->successful()) {
                $resData = $response->json();
                if (!empty($resData['responseitems'])) {
                    return [
                        'carrier'         => $this->getDisplayName(),
                        'tracking_number' => $trackingNumber,
                        'events'          => $resData['responseitems'][0]['traces'] ?? [],
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("J&T Express tracking fetch failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Real-time J&T Express rate calculation.
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
        $apiKey = config('services.jtexpress.api_key') ?? env('JTEXPRESS_API_KEY');
        $customerCode = config('services.jtexpress.customer_code') ?? env('JTEXPRESS_CUSTOMER_CODE');
        $apiUrl = config('services.jtexpress.api_url') ?? env('JTEXPRESS_API_URL', 'https://openapi.jtexpress.ph');

        if (empty($apiKey) || empty($customerCode)) {
            return null;
        }

        try {
            $payload = [
                'sender' => [
                    'province' => 'Laguna',
                    'city'     => 'Lumban',
                    'postCode' => $originPostalCode ?: '4014',
                ],
                'receiver' => [
                    'province' => $destProvince,
                    'city'     => $destCity,
                    'postCode' => $destPostalCode ?: '1000',
                ],
                'weight' => max(0.5, $actualWeightKg),
                'length' => max(1, $lengthCm),
                'width'  => max(1, $widthCm),
                'height' => max(1, $heightCm),
            ];

            $jsonStr = json_encode($payload);
            $digest = base64_encode(md5($jsonStr . $apiKey, true));

            $response = Http::timeout(2.5)->asForm()->post(rtrim($apiUrl, '/') . '/web/freight/inquiry', [
                'logistics_interface' => $jsonStr,
                'data_digest'         => $digest,
                'msg_type'            => 'FREIGHT_INQUIRY',
                'eccompanyid'         => $customerCode,
            ]);

            if ($response->successful()) {
                $resData = $response->json();
                if (isset($resData['freight'])) {
                    return [
                        'fee'      => (float) $resData['freight'],
                        'min_days' => 2,
                        'max_days' => 4,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::info("J&T Express live rate inquiry bypassed: " . $e->getMessage());
        }

        return null;
    }

    protected function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = ['api_key', 'secret', 'password', 'token', 'digest', 'sign', 'phone', 'mobile'];
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

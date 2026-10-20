<?php

namespace App\Services\Courier\Adapters;

use App\Services\Courier\Contracts\CourierTrackingProviderInterface;
use App\Services\Courier\Contracts\CourierRateProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LbcExpressAdapter implements CourierTrackingProviderInterface, CourierRateProviderInterface
{
    public function getCarrierCode(): string
    {
        return 'lbc';
    }

    public function getDisplayName(): string
    {
        return 'LBC Express';
    }

    /**
     * Authenticate LBC Express Webhook.
     * Official DigiLBC specification:
     * Authorization: Bearer <Partner_Token> OR X-LBC-Signature: HMAC-SHA256(raw_body, secret_key)
     */
    public function validateWebhookSignature(Request $request): bool
    {
        $partnerToken = config('services.lbc.partner_token') ?? env('LBC_PARTNER_TOKEN');
        $secretKey = config('services.lbc.secret_key') ?? env('LBC_SECRET_KEY');

        // 1. Enterprise partner token or HMAC signature verification
        if (!empty($partnerToken)) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && hash_equals('Bearer ' . $partnerToken, $authHeader)) {
                return true;
            }
        }

        if (!empty($secretKey)) {
            $rawBody = $request->getContent();
            $expectedSig = hash_hmac('sha256', $rawBody, $secretKey);
            $providedSig = $request->header('X-LBC-Signature');
            if ($providedSig && hash_equals($expectedSig, $providedSig)) {
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

        $trackingNumber = trim((string)($payload['tracking_number'] ?? ($payload['tracking_no'] ?? ($payload['airwaybill'] ?? ''))));
        if (empty($trackingNumber)) {
            return null;
        }

        $rawStatus = (string)($payload['status'] ?? ($payload['event_name'] ?? ($payload['checkpoint'] ?? 'transit')));
        $desc = (string)($payload['status_description'] ?? ($payload['description'] ?? 'Scanned by LBC Express'));
        $location = $payload['branch_name'] ?? ($payload['location'] ?? null);
        $time = $payload['event_datetime'] ?? ($payload['timestamp'] ?? now()->toDateTimeString());

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
        $partnerToken = config('services.lbc.partner_token') ?? env('LBC_PARTNER_TOKEN');
        $apiUrl = config('services.lbc.api_url') ?? env('LBC_API_URL', 'https://api.lbcexpress.com');

        if (empty($partnerToken)) {
            return null;
        }

        try {
            $response = Http::timeout(3.5)
                ->withToken($partnerToken)
                ->get(rtrim($apiUrl, '/') . '/v1/trackings/' . urlencode($trackingNumber));

            if ($response->successful()) {
                $resData = $response->json();
                $events = $resData['data']['events'] ?? ($resData['events'] ?? []);
                return [
                    'carrier'         => $this->getDisplayName(),
                    'tracking_number' => $trackingNumber,
                    'events'          => $events,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("LBC Express tracking fetch failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Real-time LBC Express rate calculation.
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
        $partnerToken = config('services.lbc.partner_token') ?? env('LBC_PARTNER_TOKEN');
        $apiUrl = config('services.lbc.api_url') ?? env('LBC_API_URL', 'https://api.lbcexpress.com');

        if (empty($partnerToken)) {
            return null;
        }

        try {
            $payload = [
                'origin' => [
                    'province'    => 'Laguna',
                    'city'        => 'Lumban',
                    'postal_code' => $originPostalCode ?: '4014',
                ],
                'destination' => [
                    'province'    => $destProvince,
                    'city'        => $destCity,
                    'postal_code' => $destPostalCode ?: '1000',
                ],
                'package' => [
                    'weight' => max(0.5, $actualWeightKg),
                    'length' => max(1, $lengthCm),
                    'width'  => max(1, $widthCm),
                    'height' => max(1, $heightCm),
                ],
            ];

            $response = Http::timeout(2.5)
                ->withToken($partnerToken)
                ->post(rtrim($apiUrl, '/') . '/v1/rates/calculate', $payload);

            if ($response->successful()) {
                $resData = $response->json();
                $rate = $resData['data']['total_fee'] ?? ($resData['fee'] ?? ($resData['rate'] ?? null));
                if ($rate !== null) {
                    return [
                        'fee'      => (float) $rate,
                        'min_days' => (int) ($resData['data']['estimated_days_min'] ?? 2),
                        'max_days' => (int) ($resData['data']['estimated_days_max'] ?? 5),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::info("LBC Express live rate inquiry bypassed: " . $e->getMessage());
        }

        return null;
    }

    protected function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = ['partner_token', 'token', 'secret', 'phone', 'mobile'];
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

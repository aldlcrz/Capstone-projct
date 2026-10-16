<?php

namespace App\Services\Courier\Adapters;

use App\Services\Courier\Contracts\CourierTrackingProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JtExpressAdapter implements CourierTrackingProviderInterface
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
        // Direct querying requires official enterprise credentials.
        // Public web scraping is prohibited per security specifications.
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

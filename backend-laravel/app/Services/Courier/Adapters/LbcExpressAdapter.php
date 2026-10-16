<?php

namespace App\Services\Courier\Adapters;

use App\Services\Courier\Contracts\CourierTrackingProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LbcExpressAdapter implements CourierTrackingProviderInterface
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
        // Direct querying requires official enterprise credentials.
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

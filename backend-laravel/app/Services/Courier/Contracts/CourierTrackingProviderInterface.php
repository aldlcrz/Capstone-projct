<?php

namespace App\Services\Courier\Contracts;

use Illuminate\Http\Request;

interface CourierTrackingProviderInterface
{
    /**
     * Get provider's internal normalized identifier.
     * e.g., 'jtexpress-ph', 'flashexpress-ph', 'lbc'
     */
    public function getCarrierCode(): string;

    /**
     * Get human-readable courier display name.
     */
    public function getDisplayName(): string;

    /**
     * Validate incoming webhook request authenticity.
     * Enforces strict provider-specific signature verification in production.
     * Only allows fallback staging secret if non-production and explicitly configured.
     *
     * @param Request $request
     * @return bool
     */
    public function validateWebhookSignature(Request $request): bool;

    /**
     * Parse provider webhook payload into normalized checkpoint format.
     * Returns array with:
     * - tracking_number (string)
     * - raw_status (string)
     * - description (string)
     * - location (string|null)
     * - occurred_at (string|null ISO or Y-m-d H:i:s)
     * - raw_payload (array)
     *
     * @param Request $request
     * @return array|null
     */
    public function parseWebhookPayload(Request $request): ?array;

    /**
     * Optional direct query for tracking information.
     * Returns null if direct query is unsupported or credentials are not configured.
     *
     * @param string $trackingNumber
     * @return array|null
     */
    public function fetchTracking(string $trackingNumber): ?array;
}

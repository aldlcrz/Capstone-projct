<?php

namespace App\Services;

use App\Models\User;
use App\Models\Address;
use App\Models\Product;
use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use App\Models\SellerShippingProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;

class ShippingCalculatorService
{
    protected ShippingZoneResolverService $zoneResolver;

    public function __construct(ShippingZoneResolverService $zoneResolver)
    {
        $this->zoneResolver = $zoneResolver;
    }

    /**
     * Calculates shipping quotes for all eligible and enabled providers.
     * ZERO hardcoded courier pricing logic. ZERO hardcoded geography.
     * All rates and volumetric rules are database-driven.
     *
     * @param User $seller
     * @param Address|array $destinationAddress
     * @param array $cartItems Items array containing product data and quantity
     * @param string|null $specificProviderId Optional provider filter
     * @return array
     * @throws \Exception
     */
    public function calculateQuotes(User $seller, Address|array $destinationAddress, array $cartItems, ?string $specificProviderId = null): array
    {
        // 1. Resolve Seller Origin Zone
        $originZone = $this->zoneResolver->resolve(
            $seller->shopProvince ?: null,
            $seller->shopCity ?: null,
            $seller->shopBarangay ?: null,
            $seller->shopPostalCode ?: null
        );

        if (!$originZone) {
            throw new \Exception("The seller's shop location is currently unserviceable by our logistics network.");
        }

        // 2. Resolve Buyer Destination Zone
        $destProvince = is_array($destinationAddress) ? ($destinationAddress['province'] ?? null) : $destinationAddress->province;
        $destCity     = is_array($destinationAddress) ? ($destinationAddress['city'] ?? null) : $destinationAddress->city;
        $destBarangay = is_array($destinationAddress) ? ($destinationAddress['barangay'] ?? null) : $destinationAddress->barangay;
        $destPostal   = is_array($destinationAddress) ? ($destinationAddress['postalCode'] ?? null) : $destinationAddress->postalCode;

        $destinationZone = $this->zoneResolver->resolve($destProvince, $destCity, $destBarangay, $destPostal);

        if (!$destinationZone) {
            throw new \Exception("The selected delivery address is outside our serviceable delivery zones.");
        }

        // 3. Aggregate Actual Weight & Consolidated Packed Volume
        // ASSUMPTION: Package dimensions represent one packed sellable unit.
        // Multiple units are modeled as consolidated shipment volume for shipping estimation.
        $totalActualWeight = 0.00;
        $totalPackedVolume = 0.00;
        $maxHandlingDays   = 1;

        foreach ($cartItems as $item) {
            $pId = $item['id'] ?? ($item['productId'] ?? null);
            $qty = max(1, (int) ($item['quantity'] ?? 1));

            $product = ($item instanceof Product) ? $item : Product::find($pId);

            if (!$product) {
                throw new \Exception("One of the products in your cart is no longer available.");
            }

            // Safe legacy gating: Products must have valid physical package specs (> 0)
            $pWeight = (float) ($product->package_weight_per_unit ?? 0);
            $pLen    = (float) ($product->package_length_per_unit ?? 0);
            $pWid    = (float) ($product->package_width_per_unit ?? 0);
            $pHgt    = (float) ($product->package_height_per_unit ?? 0);
            $hDays   = (int)   ($product->handling_days ?? 2);

            if ($pWeight <= 0 || $pLen <= 0 || $pWid <= 0 || $pHgt <= 0) {
                throw new \Exception("Product \"{$product->name}\" lacks physical package dimensions. The seller must complete package specifications before this item can be shipped.");
            }

            $totalActualWeight += ($pWeight * $qty);
            $totalPackedVolume += ($pLen * $pWid * $pHgt * $qty);

            if ($hDays > $maxHandlingDays) {
                $maxHandlingDays = $hDays;
            }
        }

        // 4. Retrieve Active & Seller-Enabled Providers
        $hasCustomSettings = SellerShippingProvider::where('seller_id', $seller->id)->exists();
        $query = ShippingProvider::where('is_active', true);

        if ($hasCustomSettings) {
            $enabledProviderIds = SellerShippingProvider::where('seller_id', $seller->id)
                ->where('is_enabled', true)
                ->pluck('provider_id')
                ->toArray();
            $query->whereIn('id', $enabledProviderIds);
        }

        if ($specificProviderId) {
            $query->where('id', $specificProviderId);
        }

        $providers = $query->get();

        if ($providers->isEmpty()) {
            throw new \Exception("No logistics couriers are currently active or supported for this seller.");
        }

        $quotes = [];

        // 5. Calculate provider-specific quotes
        foreach ($providers as $provider) {
            // Find applicable rate bracket
            // Select volumetric divisor: rate divisor -> provider default -> 3500 fallback
            $rateQuery = ShippingRate::where('provider_id', $provider->id)
                ->where('origin_zone_id', $originZone->id)
                ->where('destination_zone_id', $destinationZone->id)
                ->where('is_active', true);

            // Fetch candidate rates for this provider and zone pair
            $candidateRates = (clone $rateQuery)->orderBy('min_weight', 'asc')->get();

            if ($candidateRates->isEmpty()) {
                continue; // Route unserviceable for this provider
            }

            // Pick volumetric divisor from first rate row or provider default
            $divisor = $candidateRates->first()->volumetric_divisor
                ?: ($provider->default_volumetric_divisor ?: 3500);

            if ($divisor <= 0) $divisor = 3500;

            // Volumetric Weight (cm3 / divisor)
            $volumetricWeight = round($totalPackedVolume / $divisor, 2);
            $chargeableWeight = max($totalActualWeight, $volumetricWeight);

            // Rate Bracket Lookup: min_weight <= chargeable_weight AND (max_weight >= chargeable_weight OR max_weight IS NULL)
            $matchingRate = $candidateRates->first(function ($rate) use ($chargeableWeight) {
                if ($chargeableWeight < $rate->min_weight) return false;
                if ($rate->max_weight !== null && $chargeableWeight > $rate->max_weight) return false;
                return true;
            });

            if (!$matchingRate) {
                continue; // Parcel exceeds supported weight brackets (oversized)
            }

            // Fee calculation: Bracket pricing or Open-ended incremental pricing
            $shippingFee = (float) $matchingRate->base_rate;
            if ($matchingRate->max_weight === null && $matchingRate->additional_weight_rate > 0) {
                $extraWeight = ceil(max(0, $chargeableWeight - $matchingRate->min_weight));
                $shippingFee += ($extraWeight * (float) $matchingRate->additional_weight_rate);
            }

            $shippingFee = round($shippingFee, 2);

            $quotes[] = [
                'provider_id'                  => $provider->id,
                'provider_name'                => $provider->name,
                'provider_code'                => $provider->code,
                'shipping_rate_id'             => $matchingRate->id,
                'origin_zone_id'               => $originZone->id,
                'origin_zone_name'             => $originZone->name,
                'destination_zone_id'          => $destinationZone->id,
                'destination_zone_name'        => $destinationZone->name,
                'actual_weight'                => round($totalActualWeight, 2),
                'volumetric_weight'            => $volumetricWeight,
                'chargeable_weight'            => round($chargeableWeight, 2),
                'rate_base_snapshot'           => (float) $matchingRate->base_rate,
                'additional_weight_rate_snapshot' => (float) $matchingRate->additional_weight_rate,
                'volumetric_divisor_snapshot'  => $divisor,
                'shipping_fee'                 => $shippingFee,
                'estimated_days_min'           => (int) ($maxHandlingDays + $matchingRate->estimated_days_min),
                'estimated_days_max'           => (int) ($maxHandlingDays + $matchingRate->estimated_days_max),
                'delivery_estimate_display'    => ((int) ($maxHandlingDays + $matchingRate->estimated_days_min)) . '–' . ((int) ($maxHandlingDays + $matchingRate->estimated_days_max)) . ' Days',
            ];
        }

        if (empty($quotes)) {
            throw new \Exception("No shipping providers service the route between {$originZone->name} and {$destinationZone->name} for this package weight.");
        }

        return $quotes;
    }

    /**
     * Generates a 15-minute quote consistency token.
     * The token binds seller, destination, item hash, and timestamp.
     * Purity rule: The token is strictly a consistency mechanism, NEVER a trusted price.
     *
     * @param string $sellerId
     * @param string $addressIdOrHash
     * @param array $cartItems
     * @return string
     */
    public function generateQuoteToken(string $sellerId, string $addressIdOrHash, array $cartItems): string
    {
        $itemsHash = $this->hashCartItems($cartItems);
        $payload = [
            'seller_id'      => $sellerId,
            'address_target' => $addressIdOrHash,
            'items_hash'     => $itemsHash,
            'expires_at'     => now()->addMinutes(15)->timestamp,
        ];

        return Crypt::encryptString(json_encode($payload));
    }

    /**
     * Validates that the quote token payload matches the current checkout state and is unexpired.
     *
     * @param string|null $token
     * @param string $sellerId
     * @param string $addressIdOrHash
     * @param array $cartItems
     * @return bool
     */
    public function validateQuoteToken(?string $token, string $sellerId, string $addressIdOrHash, array $cartItems): bool
    {
        if (empty($token)) return false;

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
            if (!is_array($payload)) return false;

            if (($payload['expires_at'] ?? 0) < now()->timestamp) {
                return false;
            }

            if (($payload['seller_id'] ?? null) !== $sellerId) {
                return false;
            }

            if (($payload['address_target'] ?? null) !== $addressIdOrHash) {
                return false;
            }

            $currentHash = $this->hashCartItems($cartItems);
            if (($payload['items_hash'] ?? null) !== $currentHash) {
                return false;
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function hashCartItems(array $cartItems): string
    {
        $normalized = [];
        foreach ($cartItems as $item) {
            $pId = (string) ($item['id'] ?? ($item['productId'] ?? ''));
            $qty = (int) ($item['quantity'] ?? 1);
            $normalized[] = "{$pId}:{$qty}";
        }
        sort($normalized);
        return md5(implode('|', $normalized));
    }
}

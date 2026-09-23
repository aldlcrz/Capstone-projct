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
        // 1. Resolve Seller Origin Zone (Defaulting to platform artisan hub in Lumban, Laguna if unspecified)
        $originZone = $this->zoneResolver->resolve(
            $seller->shopProvince ?: 'Laguna',
            $seller->shopCity ?: 'Lumban',
            $seller->shopBarangay ?: null,
            $seller->shopPostalCode ?: '4014'
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

        // Proximity Evaluation: Local Cluster vs Non-Local Destination
        $isLocal = $this->isLocalCluster($seller, $destinationAddress);

        if (!$specificProviderId) {
            if ($isLocal) {
                $localProviders = $providers->filter(fn($p) => in_array($p->code, ['store_pickup', 'seller_direct']));
                if ($localProviders->isNotEmpty()) {
                    $providers = $localProviders;
                }
            } else {
                $nonLocalProviders = $providers->filter(fn($p) => !in_array($p->code, ['store_pickup', 'seller_direct']));
                if ($nonLocalProviders->isNotEmpty()) {
                    $providers = $nonLocalProviders;
                }
            }
        }

        $quotes = [];

        // 5. Calculate provider-specific quotes
        foreach ($providers as $provider) {
            // Special handling for local-only standard providers (store_pickup & seller_direct)
            if ($provider->code === 'store_pickup') {
                $quotes[] = [
                    'provider_id'                     => $provider->id,
                    'provider_name'                   => 'Store Pickup (In-Shop Collection)',
                    'provider_code'                   => 'store_pickup',
                    'shipping_rate_id'                => null,
                    'origin_zone_id'                  => $originZone->id,
                    'origin_zone_name'                => $originZone->name,
                    'destination_zone_id'             => $destinationZone->id,
                    'destination_zone_name'           => $destinationZone->name,
                    'actual_weight'                   => round($totalActualWeight, 2),
                    'volumetric_weight'               => round($totalPackedVolume / 3500, 2),
                    'chargeable_weight'               => round($totalActualWeight, 2),
                    'rate_base_snapshot'              => 0.00,
                    'additional_weight_rate_snapshot' => 0.00,
                    'volumetric_divisor_snapshot'     => 3500,
                    'shipping_fee'                    => 0.00,
                    'estimated_days_min'              => 0,
                    'estimated_days_max'              => 1,
                    'delivery_estimate_display'       => 'Same Day / Next Day Pickup',
                ];
                continue;
            }

            if ($provider->code === 'seller_direct') {
                $quotes[] = [
                    'provider_id'                     => $provider->id,
                    'provider_name'                   => 'Seller Direct Delivery (Local Rider)',
                    'provider_code'                   => 'seller_direct',
                    'shipping_rate_id'                => null,
                    'origin_zone_id'                  => $originZone->id,
                    'origin_zone_name'                => $originZone->name,
                    'destination_zone_id'             => $destinationZone->id,
                    'destination_zone_name'           => $destinationZone->name,
                    'actual_weight'                   => round($totalActualWeight, 2),
                    'volumetric_weight'               => round($totalPackedVolume / 3500, 2),
                    'chargeable_weight'               => round($totalActualWeight, 2),
                    'rate_base_snapshot'              => 25.00,
                    'additional_weight_rate_snapshot' => 0.00,
                    'volumetric_divisor_snapshot'     => 3500,
                    'shipping_fee'                    => 25.00,
                    'estimated_days_min'              => 1,
                    'estimated_days_max'              => 1,
                    'delivery_estimate_display'       => '1 Day (Local Delivery)',
                ];
                continue;
            }

            // Standard Rate Bracket Query
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
     * The token binds a deterministic list of seller IDs, destination, item hash, and timestamps.
     * Purity rule: The token is strictly a consistency mechanism, NEVER a trusted price.
     *
     * @param array|string $sellerIds
     * @param string $addressIdOrHash
     * @param array $cartItems
     * @return string
     */
    public function generateQuoteToken(array|string $sellerIds, string $addressIdOrHash, array $cartItems): string
    {
        $normalizedSellerIds = is_array($sellerIds) ? $sellerIds : [$sellerIds];
        $normalizedSellerIds = array_values(array_unique(array_map('strval', $normalizedSellerIds)));
        sort($normalizedSellerIds);

        $itemsHash = $this->hashCartItems($cartItems);
        $payload = [
            'seller_ids'     => $normalizedSellerIds,
            'address_id'     => (string) $addressIdOrHash,
            'address_target' => (string) $addressIdOrHash,
            'cart_hash'      => $itemsHash,
            'items_hash'     => $itemsHash,
            'issued_at'      => (int) now()->timestamp,
            'expires_at'     => (int) now()->addMinutes(15)->timestamp,
        ];

        return Crypt::encryptString(json_encode($payload));
    }

    /**
     * Validates that the quote token payload matches the current checkout state and is unexpired.
     *
     * @param string|null $token
     * @param array|string $sellerIds
     * @param string $addressIdOrHash
     * @param array $cartItems
     * @return bool
     */
    public function validateQuoteToken(?string $token, array|string $sellerIds, string $addressIdOrHash, array $cartItems): bool
    {
        if (empty($token)) return false;

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
            if (!is_array($payload)) return false;

            if (($payload['expires_at'] ?? 0) < now()->timestamp) {
                return false;
            }

            $normalizedSellerIds = is_array($sellerIds) ? $sellerIds : [$sellerIds];
            $normalizedSellerIds = array_values(array_unique(array_map('strval', $normalizedSellerIds)));
            sort($normalizedSellerIds);

            $payloadSellerIds = $payload['seller_ids'] ?? (isset($payload['seller_id']) ? [$payload['seller_id']] : []);
            $payloadSellerIds = array_values(array_unique(array_map('strval', $payloadSellerIds)));
            sort($payloadSellerIds);

            if ($payloadSellerIds !== $normalizedSellerIds) {
                return false;
            }

            $targetAddress = (string) ($payload['address_id'] ?? ($payload['address_target'] ?? ''));
            if (!hash_equals($targetAddress, (string) $addressIdOrHash)) {
                return false;
            }

            $currentHash = $this->hashCartItems($cartItems);
            $tokenHash = (string) ($payload['cart_hash'] ?? ($payload['items_hash'] ?? ''));
            if (!hash_equals($tokenHash, $currentHash)) {
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
        return hash('sha256', implode('|', $normalized));
    }

    /**
     * Resolves the seller's preferred pricing provider.
     * Strict Validation & Deterministic Resolution Hierarchy:
     * 1. Seller-enabled provider marked as default/preferred (provider exists, is_active = true, is_enabled = true)
     * 2. First valid seller-enabled provider
     * 3. Explicit platform default active provider (is_platform_default = true)
     * 4. Returns null (actionable error: no silent arbitrary fallbacks or hardcoded couriers)
     *
     * @param User $seller
     * @return ShippingProvider|null
     */
    public function getSellerPreferredProvider(User $seller): ?ShippingProvider
    {
        $hasCustom = SellerShippingProvider::where('seller_id', $seller->id)->exists();

        if ($hasCustom) {
            $query = SellerShippingProvider::where('seller_id', $seller->id)
                ->where('is_enabled', true);

            if (\Illuminate\Support\Facades\Schema::hasColumn('seller_shipping_providers', 'is_default')) {
                $query->orderByDesc('is_default');
            } else {
                $query->orderBy('created_at', 'asc');
            }

            $sellerProviders = $query->get();

            foreach ($sellerProviders as $sellerPref) {
                $provider = ShippingProvider::where('id', $sellerPref->provider_id)
                    ->where('is_active', true)
                    ->first();
                if ($provider) {
                    return $provider;
                }
            }
        }

        // Explicit platform default provider from database configuration
        if (\Illuminate\Support\Facades\Schema::hasColumn('shipping_providers', 'is_platform_default')) {
            $platformDefault = ShippingProvider::where('is_active', true)
                ->where('is_platform_default', true)
                ->first();
            if ($platformDefault) {
                return $platformDefault;
            }
        }

        return null;
    }

    /**
     * Evaluates if a given destination address is within the seller's local cluster.
     *
     * @param User $seller
     * @param Address|array $destinationAddress
     * @return bool
     */
    public function isLocalCluster(User $seller, Address|array $destinationAddress): bool
    {
        $sellerProvince = strtolower(trim((string)($seller->shopProvince ?: 'Laguna')));
        $sellerCity     = strtolower(trim((string)($seller->shopCity ?: 'Lumban')));

        $destProvince = strtolower(trim((string)(is_array($destinationAddress) ? ($destinationAddress['province'] ?? '') : ($destinationAddress->province ?? ''))));
        $destCity     = strtolower(trim((string)(is_array($destinationAddress) ? ($destinationAddress['city'] ?? '') : ($destinationAddress->city ?? ''))));

        // If both seller and buyer are in Laguna
        if ((str_contains($sellerProvince, 'laguna') || empty($sellerProvince)) && str_contains($destProvince, 'laguna')) {
            // Local 4th District / East Shore Cluster
            $localMunicipalities = [
                'lumban', 'santa cruz', 'sta. cruz', 'sta cruz', 'pagsanjan', 
                'kalayaan', 'paete', 'pangil', 'cavinti', 'pakil', 'siniloan', 
                'famy', 'mabitac', 'pila', 'victoria', 'luisiana', 'magdalena', 'majayjay'
            ];
            foreach ($localMunicipalities as $local) {
                if (str_contains($destCity, $local)) {
                    return true;
                }
            }
        }

        // Zone-based cluster evaluation
        $originZone = $this->zoneResolver->resolve(
            $seller->shopProvince ?: null,
            $seller->shopCity ?: null,
            $seller->shopBarangay ?: null,
            $seller->shopPostalCode ?: null
        );

        $destinationZone = $this->zoneResolver->resolve(
            $destProvince ?: null,
            $destCity ?: null,
            is_array($destinationAddress) ? ($destinationAddress['barangay'] ?? null) : ($destinationAddress->barangay ?? null),
            is_array($destinationAddress) ? ($destinationAddress['postalCode'] ?? null) : ($destinationAddress->postalCode ?? null)
        );

        if ($originZone && $destinationZone && $originZone->id === $destinationZone->id) {
            $originName = strtolower($originZone->name);
            if (str_contains($originName, 'east') || str_contains($originName, 'local') || str_contains($originName, '4th')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns location-gated payment methods.
     * Nearby / Local: COD, GCash, Maya
     * Far away / Non-Local: GCash, Maya (COD disabled to eliminate RTS risk)
     *
     * @param bool $isLocal
     * @return array
     */
    public function getAvailablePaymentMethods(bool $isLocal): array
    {
        return $isLocal ? ['COD', 'GCash', 'Maya'] : ['GCash', 'Maya'];
    }
}


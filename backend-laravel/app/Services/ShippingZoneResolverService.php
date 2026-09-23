<?php

namespace App\Services;

use App\Models\ShippingZone;
use App\Models\ShippingZoneArea;

class ShippingZoneResolverService
{
    /**
     * Resolves a geographic location to an authoritative ShippingZone model.
     * Enforces the 6-tier hierarchical priority:
     * Tier 1: Exact postal code match
     * Tier 2: Most-specific postal code prefix match (LENGTH DESC)
     * Tier 3: Barangay + City + Province match
     * Tier 4: City + Province match
     * Tier 5: Province match
     * Tier 6: Failsafe -> returns null (Unserviceable)
     *
     * @param string|null $province
     * @param string|null $city
     * @param string|null $barangay
     * @param string|null $postalCode
     * @return ShippingZone|null
     */
    public function resolve(?string $province, ?string $city = null, ?string $barangay = null, ?string $postalCode = null): ?ShippingZone
    {
        $normProvince = $this->normalize($province);
        $normCity     = $this->normalize($city);
        $normBarangay = $this->normalize($barangay);
        $normPostal   = $this->normalizePostal($postalCode);

        // Tier 1: Exact Postal Code
        if (!empty($normPostal)) {
            $query = ShippingZoneArea::with('zone')->where('postal_code', $normPostal);
            if (!empty($normProvince)) {
                $query->whereRaw('LOWER(province) = ?', [$normProvince]);
            }
            $area = $query->first();
            if ($area && $area->zone) {
                return $area->zone;
            }
        }

        // Tier 2: Most-Specific Postal Code Prefix (longest prefix match)
        if (!empty($normPostal)) {
            $query = ShippingZoneArea::with('zone')
                ->whereNotNull('postal_code_prefix')
                ->where('postal_code_prefix', '!=', '')
                ->whereRaw('? LIKE CONCAT(postal_code_prefix, "%")', [$normPostal]);
            if (!empty($normProvince)) {
                $query->whereRaw('LOWER(province) = ?', [$normProvince]);
            }
            $area = $query->orderByRaw('LENGTH(postal_code_prefix) DESC')->first();
            if ($area && $area->zone) {
                return $area->zone;
            }
        }

        // Tier 3: Barangay + City + Province
        if (!empty($normBarangay) && !empty($normCity) && !empty($normProvince)) {
            $area = ShippingZoneArea::with('zone')
                ->whereRaw('LOWER(province) = ?', [$normProvince])
                ->whereRaw('LOWER(city) = ?', [$normCity])
                ->whereRaw('LOWER(barangay) = ?', [$normBarangay])
                ->first();
            if ($area && $area->zone) {
                return $area->zone;
            }
        }

        // Tier 4: City + Province (match city-level area rules only, exclude postal-specific rows)
        if (!empty($normCity) && !empty($normProvince)) {
            $area = ShippingZoneArea::with('zone')
                ->whereRaw('LOWER(province) = ?', [$normProvince])
                ->whereRaw('LOWER(city) = ?', [$normCity])
                ->whereNull('postal_code')
                ->whereNull('barangay')
                ->first();
            if ($area && $area->zone) {
                return $area->zone;
            }
        }

        // Tier 5: Province (match province-level area rules only, exclude city/postal-specific rows)
        if (!empty($normProvince)) {
            $area = ShippingZoneArea::with('zone')
                ->whereRaw('LOWER(province) = ?', [$normProvince])
                ->whereNull('city')
                ->whereNull('postal_code')
                ->first();
            if ($area && $area->zone) {
                return $area->zone;
            }
        }

        // Tier 6: Failsafe (Explicitly Unserviceable)
        return null;
    }

    protected function normalize(?string $val): ?string
    {
        if ($val === null) return null;
        $trimmed = trim($val);
        return $trimmed === '' ? null : strtolower($trimmed);
    }

    protected function normalizePostal(?string $val): ?string
    {
        if ($val === null) return null;
        // Strip non-alphanumeric chars
        $cleaned = preg_replace('/[^0-9A-Za-z]/', '', trim($val));
        return $cleaned === '' ? null : $cleaned;
    }
}

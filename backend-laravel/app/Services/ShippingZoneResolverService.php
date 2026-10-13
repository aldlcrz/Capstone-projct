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
                ->where('postal_code_prefix', '!=', '');
            if (!empty($normProvince)) {
                $query->whereRaw('LOWER(province) = ?', [$normProvince]);
            }
            $areas = $query->get();
            $matchingArea = $areas->filter(function ($a) use ($normPostal) {
                return str_starts_with($normPostal, (string) $a->postal_code_prefix);
            })->sortByDesc(function ($a) {
                return strlen((string) $a->postal_code_prefix);
            })->first();

            if ($matchingArea && $matchingArea->zone) {
                return $matchingArea->zone;
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

        // Tier 5b: Canonical Philippine Geographic Fallback (NCR cities, regional aliases, all 82 PH provinces)
        $canonicalZone = $this->resolveCanonicalZone($normProvince, $normCity, $normPostal);
        if ($canonicalZone) {
            return $canonicalZone;
        }

        // Tier 6: Failsafe (Explicitly Unserviceable)
        return null;
    }

    /**
     * Resolves canonical Philippine regions, provinces, and cities to the core zones.
     */
    protected function resolveCanonicalZone(?string $normProvince, ?string $normCity, ?string $normPostal): ?ShippingZone
    {
        $this->ensureZonesSeeded();

        // 1. NCR Cities & Municipalities
        $ncrCities = [
            'manila', 'quezon city', 'caloocan', 'las piñas', 'las pinas', 'makati',
            'malabon', 'mandaluyong', 'marikina', 'muntinlupa', 'navotas', 'parañaque',
            'paranaque', 'pasay', 'pasig', 'san juan', 'taguig', 'valenzuela', 'pateros'
        ];

        if (!empty($normCity) && in_array($normCity, $ncrCities, true)) {
            $ncrZone = ShippingZone::where('code', 'NCR')->first();
            if ($ncrZone) return $ncrZone;
        }

        // 2. Comprehensive Province / Region Alias to Zone Code Map
        $provinceMap = [
            // NCR
            'metro manila' => 'NCR',
            'ncr' => 'NCR',
            'national capital region' => 'NCR',
            'national capital region (ncr)' => 'NCR',
            'first district' => 'NCR',
            'second district' => 'NCR',
            'third district' => 'NCR',
            'fourth district' => 'NCR',
            'southern manila district' => 'NCR',
            'eastern manila district' => 'NCR',
            'northern manila district' => 'NCR',
            'western manila district' => 'NCR',
            'capital district' => 'NCR',

            // South Luzon (LUZ_S) - Calabarzon, Mimaropa, Bicol
            'laguna' => 'LUZ_S',
            'cavite' => 'LUZ_S',
            'batangas' => 'LUZ_S',
            'rizal' => 'LUZ_S',
            'quezon' => 'LUZ_S',
            'oriental mindoro' => 'LUZ_S',
            'occidental mindoro' => 'LUZ_S',
            'mindoro' => 'LUZ_S',
            'marinduque' => 'LUZ_S',
            'romblon' => 'LUZ_S',
            'palawan' => 'LUZ_S',
            'albay' => 'LUZ_S',
            'camarines sur' => 'LUZ_S',
            'camarines norte' => 'LUZ_S',
            'catanduanes' => 'LUZ_S',
            'masbate' => 'LUZ_S',
            'sorsogon' => 'LUZ_S',
            'calabarzon' => 'LUZ_S',
            'mimaropa' => 'LUZ_S',
            'bicol' => 'LUZ_S',

            // North Luzon (LUZ_N) - Central Luzon, Ilocos, Cordillera, Cagayan Valley
            'bulacan' => 'LUZ_N',
            'pampanga' => 'LUZ_N',
            'tarlac' => 'LUZ_N',
            'bataan' => 'LUZ_N',
            'zambales' => 'LUZ_N',
            'nueva ecija' => 'LUZ_N',
            'aurora' => 'LUZ_N',
            'benguet' => 'LUZ_N',
            'abra' => 'LUZ_N',
            'apayao' => 'LUZ_N',
            'ifugao' => 'LUZ_N',
            'kalinga' => 'LUZ_N',
            'mountain province' => 'LUZ_N',
            'ilocos norte' => 'LUZ_N',
            'ilocos sur' => 'LUZ_N',
            'la union' => 'LUZ_N',
            'pangasinan' => 'LUZ_N',
            'batanes' => 'LUZ_N',
            'cagayan' => 'LUZ_N',
            'isabela' => 'LUZ_N',
            'nueva vizcaya' => 'LUZ_N',
            'quirino' => 'LUZ_N',
            'central luzon' => 'LUZ_N',
            'ilocos' => 'LUZ_N',
            'cordillera' => 'LUZ_N',
            'car' => 'LUZ_N',
            'cagayan valley' => 'LUZ_N',

            // Visayas (VIS) - Western, Central, Eastern Visayas
            'cebu' => 'VIS',
            'bohol' => 'VIS',
            'negros oriental' => 'VIS',
            'siquijor' => 'VIS',
            'iloilo' => 'VIS',
            'aklan' => 'VIS',
            'antique' => 'VIS',
            'capiz' => 'VIS',
            'guimaras' => 'VIS',
            'negros occidental' => 'VIS',
            'leyte' => 'VIS',
            'southern leyte' => 'VIS',
            'biliran' => 'VIS',
            'samar' => 'VIS',
            'western samar' => 'VIS',
            'eastern samar' => 'VIS',
            'northern samar' => 'VIS',
            'visayas' => 'VIS',
            'western visayas' => 'VIS',
            'central visayas' => 'VIS',
            'eastern visayas' => 'VIS',

            // Mindanao (MIN)
            'davao del sur' => 'MIN',
            'davao del norte' => 'MIN',
            'davao oriental' => 'MIN',
            'davao de oro' => 'MIN',
            'davao occidental' => 'MIN',
            'davao' => 'MIN',
            'misamis oriental' => 'MIN',
            'misamis occidental' => 'MIN',
            'bukidnon' => 'MIN',
            'camiguin' => 'MIN',
            'lanao del norte' => 'MIN',
            'lanao del sur' => 'MIN',
            'zamboanga del sur' => 'MIN',
            'zamboanga del norte' => 'MIN',
            'zamboanga sibugay' => 'MIN',
            'south cotabato' => 'MIN',
            'cotabato' => 'MIN',
            'north cotabato' => 'MIN',
            'sultan kudarat' => 'MIN',
            'sarangani' => 'MIN',
            'agusan del norte' => 'MIN',
            'agusan del sur' => 'MIN',
            'surigao del norte' => 'MIN',
            'surigao del sur' => 'MIN',
            'dinagat islands' => 'MIN',
            'basilan' => 'MIN',
            'sulu' => 'MIN',
            'tawi-tawi' => 'MIN',
            'tawi tawi' => 'MIN',
            'maguindanao' => 'MIN',
            'maguindanao del norte' => 'MIN',
            'maguindanao del sur' => 'MIN',
            'mindanao' => 'MIN',
            'barmm' => 'MIN',
            'soccsksargen' => 'MIN',
            'caraga' => 'MIN',
        ];

        // Match normalized province
        if (!empty($normProvince)) {
            foreach ($provinceMap as $key => $zoneCode) {
                if ($normProvince === $key || str_contains($normProvince, $key)) {
                    $z = ShippingZone::where('code', $zoneCode)->first();
                    if ($z) return $z;
                }
            }
        }

        // Match normalized city against province map (in case city was swapped or is a province level city)
        if (!empty($normCity)) {
            foreach ($provinceMap as $key => $zoneCode) {
                if ($normCity === $key || str_contains($normCity, $key)) {
                    $z = ShippingZone::where('code', $zoneCode)->first();
                    if ($z) return $z;
                }
            }
        }

        // Match postal prefix
        if (!empty($normPostal) && strlen($normPostal) >= 2) {
            $prefix2 = substr($normPostal, 0, 2);
            $p2 = (int) $prefix2;
            $code = match(true) {
                $p2 >= 10 && $p2 <= 17 => 'NCR',
                $p2 === 18 => (!empty($normProvince) && str_contains($normProvince, 'rizal') ? 'LUZ_S' : 'NCR'),
                $p2 >= 40 && $p2 <= 43 => 'LUZ_S',
                $p2 >= 44 && $p2 <= 48 => 'LUZ_S', // Bicol
                $p2 >= 51 && $p2 <= 53 => 'LUZ_S', // Mimaropa / Palawan
                $p2 >= 20 && $p2 <= 39 => 'LUZ_N',
                $p2 >= 50 && $p2 <= 68 => 'VIS',
                $p2 >= 70 && $p2 <= 98 => 'MIN',
                default => null,
            };

            if ($code) {
                $z = ShippingZone::where('code', $code)->first();
                if ($z) return $z;
            }
        }

        return null;
    }

    /**
     * Ensure standard shipping zones exist in database.
     */
    protected function ensureZonesSeeded(): void
    {
        if (ShippingZone::count() === 0) {
            try {
                (new \Database\Seeders\ShippingLogisticsSeeder())->run();
            } catch (\Throwable $e) {
                // Ignore if seeder cannot execute
            }
        }
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

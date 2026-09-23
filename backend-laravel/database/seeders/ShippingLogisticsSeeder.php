<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingProvider;
use App\Models\ShippingZone;
use App\Models\ShippingZoneArea;
use App\Models\ShippingRate;
use Illuminate\Support\Str;

class ShippingLogisticsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Providers
        $providersData = [
            [
                'name' => 'J&T Express',
                'code' => 'jnt',
                'default_volumetric_divisor' => 3500,
                'is_active' => true,
            ],
            [
                'name' => 'SPX Express',
                'code' => 'spx',
                'default_volumetric_divisor' => 3500,
                'is_active' => true,
            ],
            [
                'name' => 'LBC Express',
                'code' => 'lbc',
                'default_volumetric_divisor' => 3500,
                'is_active' => true,
            ],
        ];

        $providers = [];
        foreach ($providersData as $p) {
            $providers[$p['code']] = ShippingProvider::firstOrCreate(
                ['code' => $p['code']],
                $p
            );
        }

        // 2. Zones
        $zonesData = [
            ['name' => 'National Capital Region (NCR)', 'code' => 'NCR'],
            ['name' => 'North Luzon',                   'code' => 'LUZ_N'],
            ['name' => 'South Luzon',                   'code' => 'LUZ_S'],
            ['name' => 'Visayas',                       'code' => 'VIS'],
            ['name' => 'Mindanao',                      'code' => 'MIN'],
        ];

        $zones = [];
        foreach ($zonesData as $z) {
            $zones[$z['code']] = ShippingZone::firstOrCreate(
                ['code' => $z['code']],
                $z
            );
        }

        // 3. Zone Areas (Provincial / City / Postal mappings)
        $areas = [
            // NCR
            ['zone' => 'NCR', 'province' => 'Metro Manila', 'city' => null, 'barangay' => null, 'postal_code' => null, 'prefix' => '10'],
            ['zone' => 'NCR', 'province' => 'Metro Manila', 'city' => null, 'barangay' => null, 'postal_code' => null, 'prefix' => '11'],
            ['zone' => 'NCR', 'province' => 'Metro Manila', 'city' => null, 'barangay' => null, 'postal_code' => null, 'prefix' => '12'],
            ['zone' => 'NCR', 'province' => 'Metro Manila', 'city' => null, 'barangay' => null, 'postal_code' => null, 'prefix' => '14'],
            ['zone' => 'NCR', 'province' => 'Metro Manila', 'city' => null, 'barangay' => null, 'postal_code' => null, 'prefix' => '15'],
            ['zone' => 'NCR', 'province' => 'Metro Manila', 'city' => null, 'barangay' => null, 'postal_code' => null, 'prefix' => '16'],
            ['zone' => 'NCR', 'province' => 'Metro Manila', 'city' => null, 'barangay' => null, 'postal_code' => null, 'prefix' => '17'],
            ['zone' => 'NCR', 'province' => 'Metro Manila', 'city' => null, 'barangay' => null, 'postal_code' => null, 'prefix' => '18'],
            ['zone' => 'NCR', 'province' => 'NCR',          'city' => null, 'barangay' => null, 'postal_code' => null, 'prefix' => null],

            // South Luzon (including Lumban, Laguna artisan hub)
            ['zone' => 'LUZ_S', 'province' => 'Laguna',    'city' => 'Lumban', 'barangay' => null, 'postal_code' => '4014', 'prefix' => '4014'],
            ['zone' => 'LUZ_S', 'province' => 'Laguna',    'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '40'],
            ['zone' => 'LUZ_S', 'province' => 'Cavite',    'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '41'],
            ['zone' => 'LUZ_S', 'province' => 'Batangas',  'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '42'],
            ['zone' => 'LUZ_S', 'province' => 'Rizal',     'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '18'],
            ['zone' => 'LUZ_S', 'province' => 'Quezon',    'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '43'],

            // North Luzon
            ['zone' => 'LUZ_N', 'province' => 'Bulacan',    'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '30'],
            ['zone' => 'LUZ_N', 'province' => 'Pampanga',   'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '20'],
            ['zone' => 'LUZ_N', 'province' => 'Tarlac',     'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '23'],
            ['zone' => 'LUZ_N', 'province' => 'Benguet',    'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '26'],
            ['zone' => 'LUZ_N', 'province' => 'Pangasinan', 'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '24'],

            // Visayas
            ['zone' => 'VIS',   'province' => 'Cebu',      'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '60'],
            ['zone' => 'VIS',   'province' => 'Iloilo',    'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '50'],
            ['zone' => 'VIS',   'province' => 'Bohol',     'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '63'],
            ['zone' => 'VIS',   'province' => 'Leyte',     'city' => null,     'barangay' => null, 'postal_code' => null,   'prefix' => '65'],

            // Mindanao
            ['zone' => 'MIN',   'province' => 'Davao del Sur',   'city' => null, 'barangay' => null, 'postal_code' => null, 'prefix' => '80'],
            ['zone' => 'MIN',   'province' => 'Misamis Oriental','city' => null, 'barangay' => null, 'postal_code' => null, 'prefix' => '90'],
            ['zone' => 'MIN',   'province' => 'South Cotabato',  'city' => null, 'barangay' => null, 'postal_code' => null, 'prefix' => '95'],
            ['zone' => 'MIN',   'province' => 'Zamboanga del Sur','city' => null,'barangay' => null, 'postal_code' => null, 'prefix' => '70'],
        ];

        foreach ($areas as $a) {
            $zoneId = $zones[$a['zone']]->id;
            ShippingZoneArea::firstOrCreate(
                [
                    'zone_id' => $zoneId,
                    'province' => $a['province'],
                    'city' => $a['city'],
                    'barangay' => $a['barangay'],
                    'postal_code' => $a['postal_code'],
                    'postal_code_prefix' => $a['prefix'],
                ],
                [
                    'id' => (string) Str::uuid(),
                ]
            );
        }

        // 4. Rate Tables (Sample development rates across zone pairs with non-overlapping brackets)
        // Brackets: [0.00 - 1.00], [1.01 - 3.00], [3.01 - NULL (with additional_weight_rate)]
        $zoneKeys = ['NCR', 'LUZ_N', 'LUZ_S', 'VIS', 'MIN'];

        foreach ($providers as $code => $prov) {
            foreach ($zoneKeys as $oKey) {
                foreach ($zoneKeys as $dKey) {
                    $originZone = $zones[$oKey];
                    $destZone = $zones[$dKey];

                    $isSameZone = ($oKey === $dKey);
                    $isLuzonBoth = in_array($oKey, ['NCR', 'LUZ_N', 'LUZ_S']) && in_array($dKey, ['NCR', 'LUZ_N', 'LUZ_S']);

                    // Development baseline pricing multiplier based on courier and geographic relation
                    $multiplier = match($code) {
                        'jnt' => 1.0,
                        'spx' => 1.15,
                        'lbc' => 1.45,
                        default => 1.0,
                    };

                    $distMultiplier = 1.0;
                    $estMin = 2;
                    $estMax = 3;

                    if ($isSameZone) {
                        $distMultiplier = 1.0;
                        $estMin = 1;
                        $estMax = 3;
                    } elseif ($isLuzonBoth) {
                        $distMultiplier = 1.25;
                        $estMin = 2;
                        $estMax = 4;
                    } else {
                        // Inter-island (Luzon to Visayas/Mindanao or Vis to Min)
                        $distMultiplier = 1.70;
                        $estMin = 4;
                        $estMax = 7;
                    }

                    // Bracket 1: 0.00 - 1.00 kg
                    $baseB1 = round(70 * $multiplier * $distMultiplier, 2);
                    ShippingRate::firstOrCreate(
                        [
                            'provider_id' => $prov->id,
                            'origin_zone_id' => $originZone->id,
                            'destination_zone_id' => $destZone->id,
                            'min_weight' => 0.00,
                            'max_weight' => 1.00,
                        ],
                        [
                            'id' => (string) Str::uuid(),
                            'base_rate' => $baseB1,
                            'additional_weight_rate' => 0.00,
                            'volumetric_divisor' => 3500,
                            'estimated_days_min' => $estMin,
                            'estimated_days_max' => $estMax,
                            'is_active' => true,
                        ]
                    );

                    // Bracket 2: 1.01 - 3.00 kg
                    $baseB2 = round(110 * $multiplier * $distMultiplier, 2);
                    ShippingRate::firstOrCreate(
                        [
                            'provider_id' => $prov->id,
                            'origin_zone_id' => $originZone->id,
                            'destination_zone_id' => $destZone->id,
                            'min_weight' => 1.01,
                            'max_weight' => 3.00,
                        ],
                        [
                            'id' => (string) Str::uuid(),
                            'base_rate' => $baseB2,
                            'additional_weight_rate' => 0.00,
                            'volumetric_divisor' => 3500,
                            'estimated_days_min' => $estMin,
                            'estimated_days_max' => $estMax,
                            'is_active' => true,
                        ]
                    );

                    // Bracket 3: 3.01 - NULL (open-ended with incremental additional kg rate)
                    $baseB3 = round(160 * $multiplier * $distMultiplier, 2);
                    $addKgRate = round(25 * $multiplier * $distMultiplier, 2);
                    ShippingRate::firstOrCreate(
                        [
                            'provider_id' => $prov->id,
                            'origin_zone_id' => $originZone->id,
                            'destination_zone_id' => $destZone->id,
                            'min_weight' => 3.01,
                            'max_weight' => null,
                        ],
                        [
                            'id' => (string) Str::uuid(),
                            'base_rate' => $baseB3,
                            'additional_weight_rate' => $addKgRate,
                            'volumetric_divisor' => 3500,
                            'estimated_days_min' => $estMin + 1,
                            'estimated_days_max' => $estMax + 2,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}

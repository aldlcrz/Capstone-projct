<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class ProductFilterService
{
    /**
     * Parse and validate price filter values into decimal-safe bounds.
     *
     * @param mixed $min
     * @param mixed $max
     * @return array{
     *     min: ?string,
     *     max: ?string,
     *     isValid: bool,
     *     error: ?string
     * }
     */
    public static function parsePriceRange(mixed $min, mixed $max): array
    {
        $rawMin = $min !== null ? trim((string) $min) : '';
        $rawMax = $max !== null ? trim((string) $max) : '';

        $hasMin = $rawMin !== '';
        $hasMax = $rawMax !== '';

        if (!$hasMin && !$hasMax) {
            return [
                'min' => null,
                'max' => null,
                'isValid' => true,
                'error' => null,
            ];
        }

        $parsedMin = null;
        $parsedMax = null;

        if ($hasMin) {
            if (!is_numeric($rawMin)) {
                return [
                    'min' => null,
                    'max' => null,
                    'isValid' => false,
                    'error' => 'Minimum price must be a valid number.',
                ];
            }

            $floatMin = (float) $rawMin;
            if ($floatMin < 0) {
                return [
                    'min' => null,
                    'max' => null,
                    'isValid' => false,
                    'error' => 'Minimum price cannot be negative.',
                ];
            }

            $parsedMin = number_format($floatMin, 2, '.', '');
        }

        if ($hasMax) {
            if (!is_numeric($rawMax)) {
                return [
                    'min' => null,
                    'max' => null,
                    'isValid' => false,
                    'error' => 'Maximum price must be a valid number.',
                ];
            }

            $floatMax = (float) $rawMax;
            if ($floatMax < 0) {
                return [
                    'min' => null,
                    'max' => null,
                    'isValid' => false,
                    'error' => 'Maximum price cannot be negative.',
                ];
            }

            // Check if user provided an explicit non-zero decimal part (e.g. 419.50, 419.5, 419.25)
            $hasNonZeroDecimals = (strpos($rawMax, '.') !== false) && preg_match('/\.\d*[1-9]/', $rawMax);

            if (!$hasNonZeroDecimals) {
                // Whole-peso: includes the entire peso bracket from .00 through .99
                $wholePeso = floor($floatMax);
                $parsedMax = number_format($wholePeso + 0.99, 2, '.', '');
            } else {
                // Exact decimal maximum
                $parsedMax = number_format($floatMax, 2, '.', '');
            }
        }

        // Validate range if both min and max are specified
        if ($parsedMin !== null && $parsedMax !== null) {
            $comp = function_exists('bccomp')
                ? bccomp($parsedMin, $parsedMax, 2)
                : ((float)$parsedMin > (float)$parsedMax ? 1 : ((float)$parsedMin < (float)$parsedMax ? -1 : 0));

            if ($comp === 1) {
                return [
                    'min' => $parsedMin,
                    'max' => $parsedMax,
                    'isValid' => false,
                    'error' => 'Minimum price cannot be greater than maximum price.',
                ];
            }
        }

        return [
            'min' => $parsedMin,
            'max' => $parsedMax,
            'isValid' => true,
            'error' => null,
        ];
    }

    /**
     * Apply price filters to an Eloquent query using decimal-safe comparisons.
     *
     * @param Builder $query
     * @param mixed $min
     * @param mixed $max
     * @param string|null $presetRange
     * @return array{min: ?string, max: ?string, isValid: bool, error: ?string}
     */
    public static function applyPriceFilter(Builder $query, mixed $min, mixed $max, ?string $presetRange = null): array
    {
        $hasCustomMin = $min !== null && trim((string)$min) !== '';
        $hasCustomMax = $max !== null && trim((string)$max) !== '';

        // If custom min or max is supplied, custom range takes precedence over preset pills
        if (!empty($presetRange) && !$hasCustomMin && !$hasCustomMax) {
            self::applyPresetRange($query, $presetRange);
            return [
                'min' => null,
                'max' => null,
                'isValid' => true,
                'error' => null,
            ];
        }

        $result = self::parsePriceRange($min, $max);

        if (!$result['isValid']) {
            if ($result['error'] === 'Minimum price cannot be greater than maximum price.') {
                // Guaranteed empty result when min > max
                $query->whereRaw('1 = 0');
            }
            return $result;
        }

        if ($result['min'] !== null) {
            $query->where('price', '>=', $result['min']);
        }

        if ($result['max'] !== null) {
            $query->where('price', '<=', $result['max']);
        }

        return $result;
    }

    /**
     * Get the actual minimum and maximum prices of eligible approved products currently available.
     *
     * @return array{min: float, max: float}
     */
    public static function getEligibleCatalogPriceBounds(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('catalog_eligible_price_bounds', 30, function () {
            try {
                $bounds = \App\Models\Product::where('status', 'approved')
                    ->where('price', '>', 0)
                    ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
                    ->first();

                $min = ($bounds && $bounds->min_price !== null) ? (float) $bounds->min_price : 50.0;
                $max = ($bounds && $bounds->max_price !== null) ? (float) $bounds->max_price : 3999.0;

                if ($min <= 0) {
                    $min = 50.0;
                }
                if ($max < $min) {
                    $max = $min;
                }

                return ['min' => $min, 'max' => $max];
            } catch (\Throwable $e) {
                return ['min' => 50.0, 'max' => 3999.0];
            }
        });
    }

    /**
     * Invalidate cached price bounds so changes to product prices reflect immediately.
     */
    public static function clearPriceBoundsCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget('catalog_eligible_price_bounds');
    }

    /**
     * Dynamically generate sensible ascending price tiers between the catalog minimum and maximum prices.
     *
     * @param float $minPrice
     * @param float $maxPrice
     * @return array<int, array{label: string, html: string, val: string, min: float|null, max: float|null}>
     */
    public static function buildDynamicPriceTiers(float $minPrice, float $maxPrice): array
    {
        $min = max(0, $minPrice);
        $max = max($min, $maxPrice);

        $formatPrice = function(float $val): string {
            return (floor($val) == $val) ? number_format($val, 0) : number_format($val, 2);
        };

        if ($min >= $max) {
            $f = $formatPrice($min);
            return [
                ['label' => 'All Prices', 'html' => 'All Prices', 'val' => '', 'min' => null, 'max' => null],
                ['label' => "₱{$f}", 'html' => "<span class=\"font-bold\">₱{$f}</span>", 'val' => "{$min}_{$max}", 'min' => $min, 'max' => $max],
            ];
        }

        $rawTiers = [];

        if ($max <= 1000) {
            $step = max(100, round(($max - $min) / 4 / 50) * 50);
            $curr = $min;
            while ($curr < $max) {
                $next = min($max, round(($curr + $step) / 50) * 50 - 1);
                if ($next <= $curr || ($max - $next) < ($step / 2)) {
                    $next = $max;
                }
                $rawTiers[] = [$curr, $next];
                $curr = $next + 1;
                if ($next >= $max) break;
            }
        } elseif ($max <= 2500) {
            if ($min < 500) {
                $rawTiers[] = [$min, 499];
                $rawTiers[] = [500, 999];
                $rawTiers[] = [1000, 1499];
                $rawTiers[] = [1500, $max];
            } else {
                $rawTiers[] = [$min, 999];
                $rawTiers[] = [1000, 1499];
                $rawTiers[] = [1500, 1999];
                if ($max > 2000) {
                    $rawTiers[] = [2000, $max];
                }
            }
        } elseif ($max <= 5000) {
            // LumBarong catalog standard range: min ~50, max ~3999
            // Tier 1: starts at exact $min up to 999
            $firstEnd = ($min < 1000) ? 999 : 1499;
            $rawTiers[] = [$min, $firstEnd];

            $nextStart = $firstEnd + 1;
            if ($nextStart < 2000) {
                $rawTiers[] = [$nextStart, 1999];
                $nextStart = 2000;
            }

            if ($nextStart < 3000 && $max > 2500) {
                $rawTiers[] = [$nextStart, 2999];
                $nextStart = 3000;
            }

            if ($max <= 3500) {
                if ($nextStart < $max) {
                    $rawTiers[] = [$nextStart, $max];
                }
            } else {
                // max is between 3501 and 5000
                // splits at 3,499 and top tier ends at exact $max
                if ($nextStart <= 3000) {
                    $rawTiers[] = [3000, 3499];
                    $rawTiers[] = [3500, $max];
                } else {
                    $rawTiers[] = [$nextStart, $max];
                }
            }
        } elseif ($max <= 10000) {
            $rawTiers[] = [$min, 999];
            $rawTiers[] = [1000, 2499];
            $rawTiers[] = [2500, 4999];
            $rawTiers[] = [5000, 7499];
            $rawTiers[] = [7500, $max];
        } else {
            // Large catalog > 10,000
            $step = round(($max - $min) / 5 / 1000) * 1000;
            $curr = $min;
            for ($i = 0; $i < 4; $i++) {
                $next = round(($curr + $step) / 500) * 500 - 1;
                $rawTiers[] = [$curr, $next];
                $curr = $next + 1;
            }
            $rawTiers[] = [$curr, $max];
        }

        // Guarantee boundary fidelity:
        // First tier starts strictly at the catalog min, last tier ends strictly at the catalog max
        if (!empty($rawTiers)) {
            $rawTiers[0][0] = $min;
            $rawTiers[count($rawTiers) - 1][1] = $max;
        }

        $tiers = [
            ['label' => 'All Prices', 'html' => 'All Prices', 'val' => '', 'min' => null, 'max' => null]
        ];

        foreach ($rawTiers as $pair) {
            [$tMin, $tMax] = $pair;
            $fMin = $formatPrice($tMin);
            $fMax = $formatPrice($tMax);
            $label = "₱{$fMin} – ₱{$fMax}";
            $html = "<span class=\"font-bold\">₱{$fMin}</span> – <span class=\"font-bold\">₱{$fMax}</span>";
            $val = "{$tMin}_{$tMax}";
            $tiers[] = [
                'label' => $label,
                'html'  => $html,
                'val'   => $val,
                'min'   => $tMin,
                'max'   => $tMax,
            ];
        }

        return $tiers;
    }

    /**
     * Get price tiers for the storefront catalog.
     *
     * @param float|null $minPrice
     * @param float|null $maxPrice
     * @return array
     */
    public static function getPriceTiers(?float $minPrice = null, ?float $maxPrice = null): array
    {
        if ($minPrice === null || $maxPrice === null) {
            $bounds = self::getEligibleCatalogPriceBounds();
            $minPrice = $minPrice ?? $bounds['min'];
            $maxPrice = $maxPrice ?? $bounds['max'];
        }

        return self::buildDynamicPriceTiers((float) $minPrice, (float) $maxPrice);
    }

    /**
     * Apply predefined price tier filters.
     */
    public static function applyPresetRange(Builder $query, string $range): void
    {
        // 1. Support legacy hardcoded preset values for backward compatibility
        $matchedLegacy = true;
        match ($range) {
            'under_500' => $query->where('price', '<', '500.00'),
            '500_1499', '500_1500' => $query->where('price', '>=', '500.00')->where('price', '<', '1500.00'),
            '1500_3499', '1500_3500' => $query->where('price', '>=', '1500.00')->where('price', '<', '3500.00'),
            '3500_5999', '3500_6000' => $query->where('price', '>=', '3500.00')->where('price', '<', '6000.00'),
            'above_6000', '6000_plus' => $query->where('price', '>=', '6000.00'),
            'under_1500' => $query->where('price', '<', '1500.00'),
            default => $matchedLegacy = false,
        };

        if ($matchedLegacy) {
            return;
        }

        // 2. Dynamic range format: {min}_{max} (e.g. 50_999, 1000_1999, 3500_3999)
        if (preg_match('/^(\d+(?:\.\d+)?)_(\d+(?:\.\d+)?)$/', $range, $matches)) {
            $rMin = (float) $matches[1];
            $rMax = (float) $matches[2];
            $rawMaxStr = $matches[2];

            $query->where('price', '>=', number_format($rMin, 2, '.', ''));

            // Check if user provided an explicit non-zero decimal part in max (e.g. 999.50)
            $hasNonZeroDecimals = (strpos($rawMaxStr, '.') !== false) && preg_match('/\.\d*[1-9]/', $rawMaxStr);
            if (!$hasNonZeroDecimals) {
                // Whole-peso: includes the entire peso bracket from .00 through .99
                $wholePeso = floor($rMax);
                $query->where('price', '<=', number_format($wholePeso + 0.99, 2, '.', ''));
            } else {
                // Exact decimal maximum
                $query->where('price', '<=', number_format($rMax, 2, '.', ''));
            }
            return;
        }

        // 3. Dynamic under_{val} format
        if (preg_match('/^under_(\d+(?:\.\d+)?)$/', $range, $matches)) {
            $val = (float) $matches[1];
            $query->where('price', '<', number_format($val, 2, '.', ''));
            return;
        }

        // 4. Dynamic above_{val} / over_{val} format
        if (preg_match('/^(?:above|over)_(\d+(?:\.\d+)?)$/', $range, $matches)) {
            $val = (float) $matches[1];
            $query->where('price', '>=', number_format($val, 2, '.', ''));
            return;
        }
    }
}

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
     * Apply predefined price tier filters.
     */
    public static function applyPresetRange(Builder $query, string $range): void
    {
        match ($range) {
            'under_500' => $query->where('price', '<', '500.00'),
            '500_1499', '500_1500' => $query->where('price', '>=', '500.00')->where('price', '<', '1500.00'),
            '1500_3499', '1500_3500' => $query->where('price', '>=', '1500.00')->where('price', '<', '3500.00'),
            '3500_5999', '3500_6000' => $query->where('price', '>=', '3500.00')->where('price', '<', '6000.00'),
            'above_6000', '6000_plus' => $query->where('price', '>=', '6000.00'),
            'under_1500' => $query->where('price', '<', '1500.00'),
            default => null,
        };
    }
}

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

    /**
     * Apply unified category filtering to an Eloquent Product query.
     * Supports primary CategoryId, multi-category JSON UUID arrays (products.categories),
     * demographic shortcuts (Men, Women, Kids), and domain-aware category matching.
     */
    public static function applyCategoryFilter(Builder $query, ?string $rawCategory): void
    {
        if ($rawCategory === null || trim($rawCategory) === '' || $rawCategory === '__all__' || strtolower(trim($rawCategory)) === 'all') {
            return;
        }

        $catVal = trim($rawCategory);
        $catLower = mb_strtolower($catVal, 'UTF-8');

        // 1. Pure demographic category filter
        $demographics = ['men', 'male', 'women', 'female', 'kids'];
        if (in_array($catLower, $demographics, true)) {
            $normalised = match ($catLower) {
                'male', 'men'     => 'Men',
                'female', 'women' => 'Women',
                'kids'            => 'Kids',
                default           => $catVal,
            };
            $query->where('target_group', $normalised);
            return;
        }

        // 2. Resolve matching Category records (including merged synonymous pairs)
        $matchedCats = collect();
        try {
            $matchedCats = \App\Models\Category::query()
                ->where('id', $catVal)
                ->orWhereRaw('LOWER(name) = ?', [$catLower])
                ->get();

            if (in_array($catLower, ['filipiniana gown', 'traditional gowns', 'traditional gown'], true)) {
                $extra = \App\Models\Category::whereIn('name', ['Filipiniana Gown', 'Traditional Gowns'])->get();
                $matchedCats = $matchedCats->merge($extra)->unique('id');
            } elseif (in_array($catLower, ['accessories', 'heritage accessories'], true)) {
                $extra = \App\Models\Category::whereIn('name', ['Accessories', 'Heritage Accessories'])->get();
                $matchedCats = $matchedCats->merge($extra)->unique('id');
            }
        } catch (\Throwable $e) {
            $matchedCats = collect();
        }

        $matchedIds = $matchedCats->pluck('id')->filter()->values()->all();
        $matchedNames = $matchedCats->pluck('name')
            ->map(fn ($n) => mb_strtolower(trim((string) $n), 'UTF-8'))
            ->unique()
            ->values()
            ->all();

        if (empty($matchedNames)) {
            $matchedNames = [$catLower];
        }

        // 3. Special handling for Lumban Specials (on-sale items + items tagged in Lumban Specials)
        if (in_array('lumban specials', $matchedNames, true) || $catLower === 'lumban special') {
            $query->where(function (Builder $q) use ($matchedIds, $catVal) {
                $q->where(fn ($sq) => $sq->onSale());
                foreach ($matchedIds as $cid) {
                    $q->orWhere('CategoryId', $cid)
                      ->orWhere('categories', 'like', '%' . $cid . '%');
                }
                $q->orWhere('CategoryId', $catVal)
                  ->orWhere('categories', 'like', '%' . $catVal . '%');
            });
            return;
        }

        // 4. Unified multi-category UUID + domain-aware attribute matching
        $query->where(function (Builder $q) use ($catVal, $catLower, $matchedIds, $matchedNames) {
            foreach ($matchedIds as $cid) {
                $q->orWhere('CategoryId', $cid)
                  ->orWhere('categories', 'like', '%' . $cid . '%');
            }

            $q->orWhere('CategoryId', $catVal)
              ->orWhere('categories', 'like', '%' . $catVal . '%');

            if (in_array('jusi classic barong', $matchedNames, true)) {
                $q->orWhere(function (Builder $sq) {
                    $sq->where('target_group', 'Men')
                       ->where(function (Builder $fq) {
                           $fq->where('name', 'like', '%jusi%')
                              ->orWhere('description', 'like', '%jusi%')
                              ->orWhere('fabric_type', 'like', '%jusi%');
                       });
                });
            } elseif (in_array('piña formal barong', $matchedNames, true) || in_array('pina formal barong', $matchedNames, true)) {
                $q->orWhere(function (Builder $sq) {
                    $sq->where('target_group', 'Men')
                       ->where('name', 'not like', '%polo%')
                       ->where(function (Builder $fq) {
                           $fq->where('name', 'like', '%piña%')
                              ->orWhere('name', 'like', '%pina%')
                              ->orWhere('fabric_type', 'like', '%piña%')
                              ->orWhere('fabric_type', 'like', '%pina%');
                       });
                });
            } elseif (in_array('formal barong', $matchedNames, true)) {
                $q->orWhere(function (Builder $sq) {
                    $sq->where('target_group', 'Men')
                       ->where('name', 'not like', '%polo%')
                       ->where(function (Builder $fq) {
                           $fq->where('name', 'like', '%barong tagalog%')
                              ->orWhere('name', 'like', '%coat barong%')
                              ->orWhere('name', 'like', '%formal%');
                       });
                });
            } elseif (in_array('polo barong', $matchedNames, true)) {
                $q->orWhere(function (Builder $sq) {
                    $sq->whereIn('target_group', ['Men', 'Kids'])
                       ->where('name', 'like', '%polo%');
                });
            } elseif (in_array('wedding barong', $matchedNames, true)) {
                $q->orWhere(function (Builder $sq) {
                    $sq->where('target_group', 'Men')
                       ->where(function (Builder $fq) {
                           $fq->where('name', 'like', '%wedding%')
                              ->orWhere('description', 'like', '%wedding%')
                              ->orWhere('name', 'like', '%coat barong%');
                       });
                });
            } elseif (in_array('filipiniana gown', $matchedNames, true) || in_array('traditional gowns', $matchedNames, true)) {
                $q->orWhere(function (Builder $sq) {
                    $sq->where('target_group', 'Women')
                       ->where(function (Builder $fq) {
                           $fq->where('name', 'like', '%gown%')
                              ->orWhere('name', 'like', '%dress%')
                              ->orWhere('description', 'like', '%gown%');
                       });
                });
            } elseif (in_array('modern terno top', $matchedNames, true)) {
                $q->orWhere(function (Builder $sq) {
                    $sq->where('target_group', 'Women')
                       ->where(function (Builder $fq) {
                           $fq->where('name', 'like', '%terno%')
                              ->orWhere('name', 'like', '%bolero%')
                              ->orWhere('name', 'like', '%blouse%')
                              ->orWhere('name', 'like', '%vest%');
                       });
                });
            } elseif (in_array('lady barong', $matchedNames, true)) {
                $q->orWhere(function (Builder $sq) {
                    $sq->where('target_group', 'Women')
                       ->where(function (Builder $fq) {
                           $fq->where('name', 'like', '%lady barong%')
                              ->orWhere('description', 'like', '%lady barong%');
                       });
                });
            } elseif (in_array("boys' barong", $matchedNames, true) || in_array('boys barong', $matchedNames, true)) {
                $q->orWhere(function (Builder $sq) {
                    $sq->where('target_group', 'Kids')
                       ->where('name', 'not like', '%girl%')
                       ->where('name', 'not like', '%filipiniana%')
                       ->where('name', 'not like', '%dress%')
                       ->where('name', 'not like', '%gown%')
                       ->where(function (Builder $fq) {
                           $fq->where('name', 'like', '%boy%')
                              ->orWhere('name', 'like', '%barong%')
                              ->orWhere('description', 'like', '%barong%');
                       });
                });
            } elseif (in_array("girls' filipiniana", $matchedNames, true) || in_array('girls filipiniana', $matchedNames, true)) {
                $q->orWhere(function (Builder $sq) {
                    $sq->where('target_group', 'Kids')
                       ->where(function (Builder $fq) {
                           $fq->where('name', 'like', '%girl%')
                              ->orWhere('name', 'like', '%filipiniana%')
                              ->orWhere('name', 'like', '%dress%')
                              ->orWhere('description', 'like', '%filipiniana%');
                       });
                });
            } else {
                $q->orWhereHas('category', function ($cq) use ($catVal) {
                    $cq->where('name', 'like', '%' . $catVal . '%')->orWhere('id', $catVal);
                })
                ->orWhere('name', 'like', '%' . $catVal . '%')
                ->orWhere('description', 'like', '%' . $catVal . '%');
            }
        });
    }

    /**
     * Classify a category name into a storefront collection section for the "All Categories" modal.
     */
    public static function resolveCategorySection(string $name, mixed $targetGroup = null): string
    {
        $lower = mb_strtolower(trim($name), 'UTF-8');

        if (str_contains($lower, 'filipiniana') || str_contains($lower, 'gown') || str_contains($lower, 'terno') || str_contains($lower, 'lady')) {
            if (str_contains($lower, 'girl')) {
                return 'Kids & Accessories';
            }
            return "Women's Filipiniana";
        }

        if (str_contains($lower, 'boy') || str_contains($lower, 'girl') || str_contains($lower, 'kid') || str_contains($lower, 'accessor') || str_contains($lower, 'heritage')) {
            return 'Kids & Accessories';
        }

        if (in_array($lower, ['traditional', 'modern', 'special occasion', 'casual', 'semi-formal', 'lumban specials'], true)) {
            return 'Style & Occasion';
        }

        if (str_contains($lower, 'barong') || str_contains($lower, 'camisa') || str_contains($lower, 'polo')) {
            return "Men's Barong";
        }

        $groupStr = is_array($targetGroup) ? implode(',', $targetGroup) : (string) $targetGroup;
        if (stripos($groupStr, 'Women') !== false && stripos($groupStr, 'Men') === false) {
            return "Women's Filipiniana";
        }
        if (stripos($groupStr, 'Kids') !== false && stripos($groupStr, 'Men') === false) {
            return 'Kids & Accessories';
        }

        return "Men's Barong";
    }

    /**
     * Build enriched, deduplicated, and smart-ordered category items for the storefront.
     * Populated categories appear first (ordered by product count), while empty categories
     * are placed at the end so shoppers always see active collections upfront.
     *
     * @param iterable $categories
     * @return array<int, array{name: string, cat: string, group: string, section: string, img: string, count: int}>
     */
    public static function buildStorefrontCategoryItems(iterable $categories): array
    {
        $catCollection = collect($categories);
        if ($catCollection->isEmpty()) {
            return [
                ['name' => 'Wedding Barong', 'cat' => 'Wedding Barong', 'group' => 'Men', 'section' => "Men's Barong", 'img' => '/uploads/categories/wedding_groom.png', 'count' => 0],
                ['name' => 'Piña Formal Barong', 'cat' => 'Piña Formal Barong', 'group' => 'Men', 'section' => "Men's Barong", 'img' => '/uploads/categories/pina_formal.png', 'count' => 0],
                ['name' => 'Jusi Classic Barong', 'cat' => 'Jusi Classic Barong', 'group' => 'Men', 'section' => "Men's Barong", 'img' => '/uploads/categories/jusi_classic.png', 'count' => 0],
                ['name' => 'Polo Barong', 'cat' => 'Polo Barong', 'group' => 'Men', 'section' => "Men's Barong", 'img' => '/uploads/categories/polo_casual.png', 'count' => 0],
                ['name' => 'Camisa de Chino', 'cat' => 'Camisa de Chino', 'group' => 'Men', 'section' => "Men's Barong", 'img' => '/uploads/categories/camisa_undershirt.png', 'count' => 0],
                ['name' => 'Filipiniana Gown', 'cat' => 'Filipiniana Gown', 'group' => 'Women', 'section' => "Women's Filipiniana", 'img' => '/uploads/categories/women_filipiniana.png', 'count' => 0],
                ['name' => 'Modern Terno Top', 'cat' => 'Modern Terno Top', 'group' => 'Women', 'section' => "Women's Filipiniana", 'img' => '/uploads/categories/women_terno.png', 'count' => 0],
                ['name' => 'Lady Barong', 'cat' => 'Lady Barong', 'group' => 'Women', 'section' => "Women's Filipiniana", 'img' => '/uploads/categories/women_lady_barong.png', 'count' => 0],
                ['name' => "Boys' Barong", 'cat' => "Boys' Barong", 'group' => 'Kids', 'section' => 'Kids & Accessories', 'img' => '/uploads/categories/kids_boys.png', 'count' => 0],
                ['name' => "Girls' Filipiniana", 'cat' => "Girls' Filipiniana", 'group' => 'Kids', 'section' => 'Kids & Accessories', 'img' => '/uploads/categories/kids_girls.png', 'count' => 0],
                ['name' => 'Accessories', 'cat' => 'Accessories', 'group' => 'Accessories', 'section' => 'Kids & Accessories', 'img' => '/uploads/categories/accessories.png', 'count' => 0],
            ];
        }

        $namesPresent = $catCollection->map(fn ($c) => mb_strtolower(trim($c->name ?? ''), 'UTF-8'))->all();
        $hasAccessories = in_array('accessories', $namesPresent, true);
        $hasFilipinianaGown = in_array('filipiniana gown', $namesPresent, true);

        // Categories that own a dedicated studio illustration icon
        $dedicatedStudioIconOwners = [
            'piña formal barong', 'pina formal barong',
            'jusi classic barong',
            'polo barong',
            'wedding barong',
            'camisa de chino',
            'filipiniana gown',
            'modern terno top',
            'lady barong',
            "boys' barong",
            "girls' filipiniana",
            'accessories',
        ];

        $usedImages = [];
        $items = [];

        foreach ($catCollection as $dbCat) {
            $dbName = trim($dbCat->name ?? '');
            if ($dbName === '') {
                continue;
            }
            $lowerName = mb_strtolower($dbName, 'UTF-8');

            // Skip redundant duplicate categories (their products are merged into the canonical category)
            if ($lowerName === 'heritage accessories' && $hasAccessories) {
                continue;
            }
            if ($lowerName === 'traditional gowns' && $hasFilipinianaGown) {
                continue;
            }

            $targetGroupStr = '';
            if (is_array($dbCat->target_group) && count($dbCat->target_group) > 0) {
                $targetGroupStr = implode(', ', $dbCat->target_group);
            } elseif (is_string($dbCat->target_group) && !empty($dbCat->target_group)) {
                $targetGroupStr = $dbCat->target_group;
            }

            // Compute accurate matching product count & representative product image
            $count = 0;
            $representativeImg = null;
            try {
                $q = \App\Models\Product::where('status', 'approved');
                self::applyCategoryFilter($q, $dbName);
                $count = (clone $q)->count();

                // If this category does not own one of the 11 studio icons (or its static image is already used),
                // pick a distinct real product thumbnail from its matching products so circles don't repeat icons.
                $staticImg = $dbCat->getImageUrl();
                if ($lowerName === 'modern filipiniana' && str_contains($staticImg, 'polo_casual')) {
                    $staticImg = '/uploads/categories/women_filipiniana.png';
                }

                $needsDistinctProductImg = !in_array($lowerName, $dedicatedStudioIconOwners, true)
                    || in_array($staticImg, $usedImages, true);

                if ($needsDistinctProductImg && $count > 0) {
                    $candidates = (clone $q)->orderBy('views', 'desc')->limit(12)->get();
                    foreach ($candidates as $cand) {
                        $candImg = method_exists($cand, 'getImageUrl') ? $cand->getImageUrl() : null;
                        if ($candImg && !str_contains($candImg, 'default.jpg') && !in_array($candImg, $usedImages, true)) {
                            $representativeImg = $candImg;
                            break;
                        }
                    }
                }

                $finalImg = $representativeImg ?: $staticImg;
                $usedImages[] = $finalImg;
            } catch (\Throwable $e) {
                $finalImg = $dbCat->getImageUrl();
            }

            $items[] = [
                'name'    => $dbName,
                'cat'     => $dbName,
                'group'   => $targetGroupStr,
                'section' => self::resolveCategorySection($dbName, $dbCat->target_group),
                'img'     => $finalImg,
                'count'   => $count,
            ];
        }

        // Sort: Populated categories (count > 0) first by descending count, then alphabetical;
        // 0-product categories placed at the end alphabetically.
        usort($items, function (array $a, array $b): int {
            $aHas = $a['count'] > 0 ? 1 : 0;
            $bHas = $b['count'] > 0 ? 1 : 0;
            if ($aHas !== $bHas) {
                return $bHas <=> $aHas;
            }
            if ($a['count'] !== $b['count']) {
                return $b['count'] <=> $a['count'];
            }
            return strcasecmp($a['name'], $b['name']);
        });

        return $items;
    }
}

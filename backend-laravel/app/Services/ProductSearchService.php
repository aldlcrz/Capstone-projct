<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProductSearchService
{
    /**
     * Stop words to filter out when tokenizing multi-word queries.
     * Note: High-value terms like 'men', 'women', 'kids' are preserved.
     */
    protected const STOP_WORDS = [
        'for', 'fro', 'the', 'in', 'and', 'with', 'of', 'to', 'a', 'an',
        'at', 'by', 'on', 'all', 'any', 'is', 'it', 'or', 'from'
    ];

    /**
     * Common plural to singular stem mappings for Barong and apparel domain.
     */
    protected const STEM_MAPPINGS = [
        'barongs'      => 'barong',
        'polos'        => 'polo',
        'dresses'      => 'dress',
        'filipinianas' => 'filipiniana',
        'mestizas'     => 'mestiza',
        'embroideries' => 'embroidery',
        'shirts'       => 'shirt',
        'coats'        => 'coat',
        'collars'      => 'collar',
        'jackets'      => 'jacket',
        'fabrics'      => 'fabric',
        'organzas'     => 'organza',
        'patterns'     => 'pattern',
        'mens'         => 'men',
        'womens'       => 'women',
        'ladies'       => 'women',
        'lady'         => 'women',
        'males'        => 'men',
        'females'      => 'women',
        'kids'         => 'kid',
        'children'     => 'kid',
        'childrens'    => 'kid',
        'child'        => 'kid',
        'boys'         => 'boy',
        'girls'        => 'girl',
    ];

    /**
     * Normalize raw search string:
     * - Handle possessives ('s, ’s)
     * - Normalize accents (ñ -> n / pina -> piña compatibility)
     * - Strip unnecessary punctuation
     */
    public static function normalize(string $query): string
    {
        $clean = mb_strtolower(trim($query), 'UTF-8');
        // Normalize possessive 's (men's -> men, women's -> women, kid's -> kid, boy's -> boy, girl's -> girl)
        $clean = preg_replace("/['’]s\b/u", '', $clean);
        // Replace non-alphanumeric (except accented letters) with spaces
        $clean = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $clean);
        return trim((string) preg_replace('/\s+/', ' ', $clean));
    }

    /**
     * Detect intended target audience from search string.
     * Returns: 'Men', 'Women', 'Boys', 'Girls', 'Kids', or null.
     */
    public static function detectTargetAudience(string $normalized): ?string
    {
        $words = preg_split('/\s+/', $normalized);
        foreach ($words as $w) {
            if (in_array($w, ['boy', 'boys'], true)) {
                return 'Boys';
            }
            if (in_array($w, ['girl', 'girls'], true)) {
                return 'Girls';
            }
            if (in_array($w, ['kid', 'kids', 'child', 'children'], true)) {
                return 'Kids';
            }
            if (in_array($w, ['men', 'male', 'gentleman', 'gentlemen'], true)) {
                return 'Men';
            }
            if (in_array($w, ['women', 'woman', 'female', 'ladies', 'lady', 'filipiniana', 'mestiza', 'terno'], true)) {
                return 'Women';
            }
        }
        return null;
    }

    /**
     * Extract clean searchable tokens, stripping stop words and stemming plurals.
     * Returns array of token variants (singular and plural where applicable).
     *
     * @return array<int, array{original: string, variants: array<string>}>
     */
    public static function extractTokens(string $normalized): array
    {
        $words = preg_split('/\s+/', $normalized);
        $tokens = [];

        foreach ($words as $w) {
            $w = trim($w);
            if (empty($w)) {
                continue;
            }

            // Skip common stop words if we have more than one word
            if (count($words) > 1 && in_array($w, self::STOP_WORDS, true)) {
                continue;
            }

            $variants = [$w];

            // Stem mapping
            if (isset(self::STEM_MAPPINGS[$w])) {
                $stemmed = self::STEM_MAPPINGS[$w];
                if (!in_array($stemmed, $variants, true)) {
                    $variants[] = $stemmed;
                }
            } elseif (str_ends_with($w, 's') && strlen($w) > 3) {
                $singular = substr($w, 0, -1);
                if (!in_array($singular, $variants, true)) {
                    $variants[] = $singular;
                }
            } else {
                // Plural variant
                $plural = $w . 's';
                if (!in_array($plural, $variants, true)) {
                    $variants[] = $plural;
                }
            }

            // Piña / Pina accent variant
            if (str_contains($w, 'pina') || str_contains($w, 'piña')) {
                $variants[] = 'piña';
                $variants[] = 'pina';
            }

            $tokens[] = [
                'original' => $w,
                'variants' => array_values(array_unique($variants))
            ];
        }

        return $tokens;
    }

    /**
     * Apply flexible, attribute-combining search and relevance ranking to Eloquent Product Query.
     */
    public static function apply(Builder $query, ?string $rawSearch, ?string $sort = null): Builder
    {
        if (empty($rawSearch) || trim($rawSearch) === '') {
            return $query;
        }

        $rawSearch = trim((string)$rawSearch);
        $normalized = self::normalize($rawSearch);

        // For very short 1-2 char inputs, match name strictly to avoid random noisy matches
        if (mb_strlen($rawSearch, 'UTF-8') <= 2) {
            return $query->where('name', 'like', '%' . $rawSearch . '%');
        }

        $detectedAudience = self::detectTargetAudience($normalized);
        $tokens = self::extractTokens($normalized);

        if (empty($tokens)) {
            return $query->where('name', 'like', '%' . $rawSearch . '%');
        }

        // Apply WHERE clauses combining keywords across product attributes
        $query->where(function (Builder $mainQuery) use ($rawSearch, $normalized, $tokens, $detectedAudience) {
            // Path 1: Direct full phrase match on name, description, SKU, or category
            $mainQuery->where('name', 'like', '%' . $rawSearch . '%')
                      ->orWhere('name', 'like', '%' . $normalized . '%')
                      ->orWhere('description', 'like', '%' . $rawSearch . '%')
                      ->orWhere('description', 'like', '%' . $normalized . '%')
                      ->orWhere('sku', 'like', '%' . $rawSearch . '%')
                      ->orWhereHas('category', function ($cq) use ($rawSearch, $normalized) {
                          $cq->where('name', 'like', '%' . $rawSearch . '%')
                             ->orWhere('name', 'like', '%' . $normalized . '%');
                      });

            // Path 2: Token-based multi-attribute match
            // Each meaningful token must match AT LEAST ONE searchable field
            $mainQuery->orWhere(function (Builder $tokenQuery) use ($tokens, $detectedAudience) {
                foreach ($tokens as $tokenData) {
                    $orig = $tokenData['original'];
                    $variants = $tokenData['variants'];

                    // Men token
                    if (in_array($orig, ['men', 'mens', 'male', 'gentleman', 'gentlemen'], true)) {
                        $tokenQuery->where(function (Builder $aq) {
                            $aq->where(function ($sq) {
                                // Safe Men check: contains 'men' but NOT 'women'
                                $sq->where('target_group', 'like', '%men%')
                                   ->where('target_group', 'not like', '%women%');
                            })->orWhere('name', 'like', '%men%')
                              ->orWhere('description', 'like', '%men%')
                              ->orWhereHas('category', function ($cq) {
                                  $cq->where('name', 'like', '%men%')
                                     ->where('name', 'not like', '%women%');
                              });
                        });
                        continue;
                    }

                    // Women token
                    if (in_array($orig, ['women', 'womens', 'female', 'ladies', 'lady', 'filipiniana', 'mestiza', 'terno'], true)) {
                        $tokenQuery->where(function (Builder $aq) use ($variants) {
                            $aq->where(function ($sq) {
                                $sq->where('target_group', 'like', '%women%')
                                   ->orWhere('target_group', 'like', '%female%');
                            });
                            foreach ($variants as $v) {
                                $aq->orWhere('name', 'like', '%' . $v . '%')
                                   ->orWhere('description', 'like', '%' . $v . '%')
                                   ->orWhereHas('category', function ($cq) use ($v) {
                                       $cq->where('name', 'like', '%' . $v . '%');
                                   });
                            }
                        });
                        continue;
                    }

                    // Boy token: primary Kids/Boys, related male attire (Barongs), excludes women's apparel
                    if (in_array($orig, ['boy', 'boys'], true)) {
                        $tokenQuery->where(function (Builder $aq) use ($variants) {
                            $aq->where(function ($sq) {
                                $sq->where('target_group', 'like', '%kid%')
                                   ->orWhere('target_group', 'like', '%child%')
                                   ->orWhere('target_group', 'like', '%men%'); // Related male attire
                            });
                            foreach ($variants as $v) {
                                $aq->orWhere('name', 'like', '%' . $v . '%')
                                   ->orWhere('description', 'like', '%' . $v . '%')
                                   ->orWhereHas('category', function ($cq) use ($v) {
                                       $cq->where('name', 'like', '%' . $v . '%')
                                          ->orWhere('name', 'like', '%barong%')
                                          ->orWhere('name', 'like', '%boy%');
                                   });
                            }
                        });
                        // Filter out women's dresses/apparel
                        $tokenQuery->where(function (Builder $nq) {
                            $nq->where('target_group', 'not like', '%women%')
                               ->orWhereNull('target_group');
                        });
                        continue;
                    }

                    // Girl token: primary Kids/Girls, related female formal wear (Filipiniana / Lady Barong), excludes men's barongs
                    if (in_array($orig, ['girl', 'girls'], true)) {
                        $tokenQuery->where(function (Builder $aq) use ($variants) {
                            $aq->where(function ($sq) {
                                $sq->where('target_group', 'like', '%kid%')
                                   ->orWhere('target_group', 'like', '%child%')
                                   ->orWhere('target_group', 'like', '%women%')
                                   ->orWhere('target_group', 'like', '%female%');
                            });
                            foreach ($variants as $v) {
                                $aq->orWhere('name', 'like', '%' . $v . '%')
                                   ->orWhere('description', 'like', '%' . $v . '%')
                                   ->orWhereHas('category', function ($cq) use ($v) {
                                       $cq->where('name', 'like', '%' . $v . '%')
                                          ->orWhere('name', 'like', '%filipiniana%')
                                          ->orWhere('name', 'like', '%gown%')
                                          ->orWhere('name', 'like', '%lady%')
                                          ->orWhere('name', 'like', '%girl%');
                                   });
                            }
                            $aq->orWhere('name', 'like', '%filipiniana%')
                               ->orWhere('name', 'like', '%dress%')
                               ->orWhere('name', 'like', '%bolero%')
                               ->orWhere('name', 'like', '%mestiza%')
                               ->orWhere('name', 'like', '%lady%');
                        });
                        // Filter out pure male barongs unless lady barong
                        $tokenQuery->where(function (Builder $nq) {
                            $nq->where(function ($sq) {
                                $sq->where('target_group', '!=', 'Men')
                                   ->orWhere('name', 'like', '%lady%')
                                   ->orWhere('name', 'like', '%filipiniana%');
                            });
                        });
                        continue;
                    }

                    // Kid / Child token
                    if (in_array($orig, ['kid', 'kids', 'child', 'children'], true)) {
                        $tokenQuery->where(function (Builder $aq) use ($variants) {
                            $aq->where(function ($sq) {
                                $sq->where('target_group', 'like', '%kid%')
                                   ->orWhere('target_group', 'like', '%child%');
                            });
                            foreach ($variants as $v) {
                                $aq->orWhere('name', 'like', '%' . $v . '%')
                                   ->orWhere('description', 'like', '%' . $v . '%')
                                   ->orWhereHas('category', function ($cq) use ($v) {
                                       $cq->where('name', 'like', '%' . $v . '%')
                                          ->orWhere('name', 'like', '%kid%')
                                          ->orWhere('name', 'like', '%boy%')
                                          ->orWhere('name', 'like', '%girl%');
                                   });
                            }
                            // Fallback so catalog is not completely empty if store has not yet tagged Kids products
                            $aq->orWhereIn('target_group', ['Men', 'Women']);
                        });
                        continue;
                    }

                    // Check if query is in a female demographic context (e.g. 'barong for girl' or 'barong for women')
                    $isFemaleSearch = in_array($detectedAudience, ['Women', 'Girls'], true);

                    // General attribute matching for non-audience keywords
                    $tokenQuery->where(function (Builder $subQuery) use ($variants, $orig, $isFemaleSearch) {
                        foreach ($variants as $v) {
                            $subQuery->orWhere('name', 'like', '%' . $v . '%')
                                     ->orWhere('description', 'like', '%' . $v . '%')
                                     ->orWhere('fabric_type', 'like', '%' . $v . '%')
                                     ->orWhere('collar_type', 'like', '%' . $v . '%')
                                     ->orWhere('artisan_region', 'like', '%' . $v . '%')
                                     ->orWhere('sku', 'like', '%' . $v . '%')
                                     ->orWhere('categories', 'like', '%' . $v . '%')
                                     ->orWhere('variations', 'like', '%' . $v . '%')
                                     ->orWhereHas('category', function ($cq) use ($v) {
                                         $cq->where('name', 'like', '%' . $v . '%');
                                     })
                                     ->orWhereHas('seller', function ($sq) use ($v) {
                                         $sq->where('shopName', 'like', '%' . $v . '%')
                                            ->orWhere('name', 'like', '%' . $v . '%');
                                     });

                            // If 'barong' is searched in female context (e.g. 'barong for girl' or 'barong for women'),
                            // also match Filipiniana / Lady Barong attire
                            if ($v === 'barong' && $isFemaleSearch) {
                                $subQuery->orWhere('name', 'like', '%filipiniana%')
                                         ->orWhere('name', 'like', '%lady%')
                                         ->orWhere('name', 'like', '%bolero%')
                                         ->orWhere('name', 'like', '%mestiza%')
                                         ->orWhereHas('category', function ($cq) {
                                             $cq->where('name', 'like', '%filipiniana%')
                                                ->orWhere('name', 'like', '%lady%')
                                                ->orWhere('name', 'like', '%gown%');
                                         });
                            }
                        }
                    });
                }
            });
        });

        // Apply Relevance Ranking when no explicit sort (like price or newest) is requested
        self::applyRelevanceOrder($query, $rawSearch, $normalized, $tokens, $detectedAudience, $sort);

        return $query;
    }

    /**
     * Compute and apply relevance score ordering.
     */
    protected static function applyRelevanceOrder(
        Builder $query,
        string $rawSearch,
        string $normalized,
        array $tokens,
        ?string $detectedAudience,
        ?string $sort
    ): void {
        // If customer explicitly requested a standard sort (e.g. Price Low->High), honor that order first
        if ($sort && in_array($sort, ['price_asc', 'price_desc', 'newest', 'price_low', 'price_high', 'cheapest', 'expensive'])) {
            return;
        }

        $relevanceCases = [];
        $bindings = [];

        // 1. Exact full name match (+250 points)
        $relevanceCases[] = "CASE WHEN LOWER(products.name) = ? THEN 250 ELSE 0 END";
        $bindings[] = $normalized;

        // 2. Full phrase in name (+150 points)
        $relevanceCases[] = "CASE WHEN LOWER(products.name) LIKE ? THEN 150 ELSE 0 END";
        $bindings[] = '%' . $normalized . '%';

        // 3. Demographic & Target Audience Alignment (+300 for Kids top boost, -300 for gender conflict)
        if ($detectedAudience === 'Boys') {
            $relevanceCases[] = "CASE 
                WHEN (LOWER(products.target_group) = 'kids' OR LOWER(products.target_group) LIKE '%kid%') THEN 300 
                WHEN LOWER(products.name) LIKE '%boy%' THEN 150
                WHEN (LOWER(products.target_group) = 'men' OR (LOWER(products.target_group) LIKE '%men%' AND LOWER(products.target_group) NOT LIKE '%women%')) THEN 50
                WHEN (LOWER(products.target_group) = 'women' OR LOWER(products.target_group) LIKE '%women%') THEN -300
                ELSE 0 END";
        } elseif ($detectedAudience === 'Girls') {
            $relevanceCases[] = "CASE 
                WHEN (LOWER(products.target_group) = 'kids' OR LOWER(products.target_group) LIKE '%kid%') THEN 300 
                WHEN LOWER(products.name) LIKE '%girl%' THEN 150
                WHEN (LOWER(products.target_group) = 'women' OR LOWER(products.target_group) LIKE '%women%' OR LOWER(products.target_group) LIKE '%female%') THEN 80 
                WHEN (LOWER(products.target_group) = 'men' AND LOWER(products.target_group) NOT LIKE '%women%') THEN -300
                ELSE 0 END";
        } elseif ($detectedAudience === 'Kids') {
            $relevanceCases[] = "CASE 
                WHEN (LOWER(products.target_group) = 'kids' OR LOWER(products.target_group) LIKE '%kid%' OR LOWER(products.target_group) LIKE '%child%') THEN 300 
                WHEN (LOWER(products.name) LIKE '%kid%' OR LOWER(products.description) LIKE '%kid%') THEN 150
                ELSE 0 END";
        } elseif ($detectedAudience === 'Men') {
            $relevanceCases[] = "CASE 
                WHEN (LOWER(products.target_group) = 'men' OR (LOWER(products.target_group) LIKE '%men%' AND LOWER(products.target_group) NOT LIKE '%women%')) THEN 100 
                WHEN (LOWER(products.target_group) = 'women' OR LOWER(products.target_group) LIKE '%women%') THEN -150
                ELSE 0 END";
        } elseif ($detectedAudience === 'Women') {
            $relevanceCases[] = "CASE 
                WHEN (LOWER(products.target_group) = 'women' OR LOWER(products.target_group) LIKE '%women%' OR LOWER(products.target_group) LIKE '%female%') THEN 100 
                WHEN (LOWER(products.target_group) = 'men' AND LOWER(products.target_group) NOT LIKE '%women%') THEN -150
                ELSE 0 END";
        }

        // 4. Per-token name match (+40 points per token) and fabric match (+30 points)
        foreach ($tokens as $tokenData) {
            $orig = $tokenData['original'];
            if (in_array($orig, ['men', 'women', 'kids', 'boy', 'boys', 'girl', 'girls', 'for', 'the'], true)) {
                continue;
            }
            $relevanceCases[] = "CASE WHEN LOWER(products.name) LIKE ? THEN 40 ELSE 0 END";
            $bindings[] = '%' . $orig . '%';

            $relevanceCases[] = "CASE WHEN LOWER(COALESCE(products.fabric_type, '')) LIKE ? THEN 30 ELSE 0 END";
            $bindings[] = '%' . $orig . '%';
        }

        if (!empty($relevanceCases)) {
            $sqlRelevance = '(' . implode(' + ', $relevanceCases) . ')';
            $query->orderByRaw($sqlRelevance . ' DESC', $bindings);
        }
    }
}

<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class RecommendationEngine
{
    /**
     * Extract structured shopping preferences from user message & session context.
     */
    public static function extractPreferences(string $message, array $sessionContext = []): array
    {
        $lower = strtolower(trim($message));
        $pref = $sessionContext;

        // 1. Budget extraction (e.g. "under 3500", "₱3,500", "budget is 4000", "3k", "3.5k")
        if (preg_match('/(?:under|below|less than|max|budget(?: is| of)?|around|upto|up to)\s*(?:php|p|₱)?\s*(\d+(?:,\d+)*(?:\.\d+)?|\d+k|\d+\.\d+k)/i', $lower, $m)) {
            $val = str_replace(',', '', strtolower($m[1]));
            if (str_ends_with($val, 'k')) {
                $pref['max_budget'] = (float) rtrim($val, 'k') * 1000;
            } else {
                $pref['max_budget'] = (float) $val;
            }
        } elseif (preg_match('/(?:php|p|₱)\s*(\d+(?:,\d+)*(?:\.\d+)?)/i', $lower, $m)) {
            $pref['max_budget'] = (float) str_replace(',', '', $m[1]);
        }

        // Check for cheaper / budget request refinement
        if (str_contains($lower, 'cheaper') || str_contains($lower, 'affordable') || str_contains($lower, 'budget option') || str_contains($lower, 'lower price') || str_contains($lower, 'mas mura')) {
            $pref['sort_preference'] = 'budget';
            if (isset($pref['max_budget'])) {
                $pref['max_budget'] = $pref['max_budget'] * 0.85; // Lower target budget by 15%
            } else {
                $pref['max_budget'] = 3000.0;
            }
        }

        // Check for premium request refinement
        if (str_contains($lower, 'premium') || str_contains($lower, 'high end') || str_contains($lower, 'luxury') || str_contains($lower, 'piña only') || str_contains($lower, 'pina only') || str_contains($lower, 'best quality')) {
            $pref['sort_preference'] = 'premium';
            $pref['fabric'] = 'piña';
        }

        // 2. Occasion extraction
        if (preg_match('/\b(wedding|groom|kasal|bride|principal sponsor|ninong|groomsmen|entourage)\b/i', $lower)) {
            $pref['occasion'] = 'wedding';
            if (preg_match('/\b(groom)\b/i', $lower)) $pref['role'] = 'groom';
            if (preg_match('/\b(ninong|sponsor)\b/i', $lower)) $pref['role'] = 'ninong';
        } elseif (preg_match('/\b(graduation|moving up|diploma|baccalaureate|student)\b/i', $lower)) {
            $pref['occasion'] = 'graduation';
        } elseif (preg_match('/\b(casual|everyday|work|office|semi-formal|modern|barong tagalog for work)\b/i', $lower)) {
            $pref['occasion'] = 'casual';
        } elseif (preg_match('/\b(gala|diplomatic|formal event|oath taking|inauguration|state dinner)\b/i', $lower)) {
            $pref['occasion'] = 'formal';
        }

        // 3. Fabric extraction
        if (str_contains($lower, 'piña') || str_contains($lower, 'pina')) {
            $pref['fabric'] = 'piña';
        } elseif (str_contains($lower, 'cocoon')) {
            $pref['fabric'] = 'cocoon';
        } elseif (str_contains($lower, 'jusi')) {
            $pref['fabric'] = 'jusi';
        } elseif (str_contains($lower, 'organza') || str_contains($lower, 'monoray')) {
            $pref['fabric'] = 'organza';
        } elseif (str_contains($lower, 'linen')) {
            $pref['fabric'] = 'linen';
        }

        // 4. Collar & Style extraction
        if (str_contains($lower, 'mandarin') || str_contains($lower, 'chinese collar') || str_contains($lower, 'standing collar')) {
            $pref['collar'] = 'mandarin';
        } elseif (str_contains($lower, 'pointed') || str_contains($lower, 'classic collar') || str_contains($lower, 'traditional collar')) {
            $pref['collar'] = 'pointed';
        } elseif (str_contains($lower, 'polo') || str_contains($lower, 'short sleeve')) {
            $pref['style'] = 'polo';
        }

        // 5. Gender / Demographic extraction
        if (preg_match('/\b(filipiniana|dress|terno|gown|women|lady barong|babae)\b/i', $lower)) {
            $pref['gender'] = 'women';
        } elseif (preg_match('/\b(kids?|boys?|girls?|bata|child|children)\b/i', $lower)) {
            $pref['gender'] = 'kids';
        } elseif (preg_match('/\b(men|lalaki|gentlemen)\b/i', $lower)) {
            $pref['gender'] = 'men';
        }

        return $pref;
    }

    /**
     * Compute multi-factor recommendation score for a product against user preferences.
     * 7-Factor Weighted Formula (100% Total):
     * - Budget Match: 20%
     * - Occasion Match: 20%
     * - Fabric Match: 20%
     * - Demographic Match: 10%
     * - Collar & Style Match: 10%
     * - Popularity & Reviews: 10%
     * - Stock & Tailoring Readiness: 10%
     *
     * @param Product|object $product
     * @param array $pref
     * @return array
     */
    public static function scoreProduct(object $product, array $pref): array
    {
        $score = 0;
        $reasons = [];
        $price = (float) ($product->price ?? 0);
        $nameLower = strtolower($product->name . ' ' . $product->description . ' ' . ($product->fabric_type ?? '') . ' ' . ($product->collar_type ?? ''));
        $targetGroup = strtolower($product->target_group ?? '');

        // 1. BUDGET MATCH (20 Pts)
        if (!empty($pref['max_budget'])) {
            $maxBudget = (float) $pref['max_budget'];
            if ($price <= $maxBudget) {
                $score += 20;
                $savings = $maxBudget - $price;
                if ($savings > 0) {
                    $reasons[] = "✓ Within budget (₱" . number_format($price, 2) . " fits your ₱" . number_format($maxBudget) . " limit)";
                } else {
                    $reasons[] = "✓ Exactly matches your ₱" . number_format($maxBudget) . " budget";
                }
            } elseif ($price <= $maxBudget * 1.10) {
                $score += 12; // Near budget within 10%
                $reasons[] = "✓ Close to budget at ₱" . number_format($price, 2);
            } else {
                $score += 4;
            }
        } else {
            $score += 18; // Neutral budget score
            $reasons[] = "✓ Priced at ₱" . number_format($price, 2);
        }

        // 2. OCCASION MATCH (20 Pts)
        $occasion = $pref['occasion'] ?? 'general';
        if ($occasion === 'wedding') {
            if (str_contains($nameLower, 'piña') || str_contains($nameLower, 'cocoon') || str_contains($nameLower, 'wedding') || str_contains($nameLower, 'groom') || str_contains($nameLower, 'jusi')) {
                $score += 20;
                $reasons[] = "✓ Ideal for wedding ceremonies & entourage";
            } elseif (str_contains($nameLower, 'polo') || str_contains($nameLower, 'short')) {
                $score += 6;
            } else {
                $score += 15;
                $reasons[] = "✓ Formal tailoring suitable for weddings";
            }
        } elseif ($occasion === 'graduation') {
            if (str_contains($nameLower, 'organza') || str_contains($nameLower, 'jusi') || str_contains($nameLower, 'graduation')) {
                $score += 20;
                $reasons[] = "✓ Lightweight comfort ideal for graduation ceremonies";
            } else {
                $score += 15;
                $reasons[] = "✓ Suitable for academic commencement";
            }
        } elseif ($occasion === 'casual') {
            if (str_contains($nameLower, 'polo') || str_contains($nameLower, 'short') || str_contains($nameLower, 'casual') || str_contains($nameLower, 'linen')) {
                $score += 20;
                $reasons[] = "✓ Comfortable modern cut for everyday & office wear";
            } else {
                $score += 10;
            }
        } else {
            $score += 16;
            $reasons[] = "✓ Versatile formalwear for banquets and celebrations";
        }

        // 3. FABRIC MATCH (20 Pts)
        $prefFabric = $pref['fabric'] ?? null;
        if ($prefFabric) {
            if (str_contains($nameLower, $prefFabric)) {
                $score += 20;
                $reasons[] = "✓ Authentic handcrafted " . ucfirst($prefFabric) . " fabric";
            } else {
                $score += 8;
            }
        } else {
            $score += 18;
            $fabricType = $product->fabric_type ?? 'Lumban Hand-Embroidered';
            $reasons[] = "✓ Hand-embroidered in Lumban ({$fabricType})";
        }

        // 4. DEMOGRAPHIC MATCH (10 Pts)
        $prefGender = $pref['gender'] ?? null;
        if ($prefGender === 'women') {
            if (str_contains($nameLower, 'filipiniana') || str_contains($nameLower, 'terno') || str_contains($nameLower, 'dress') || str_contains($targetGroup, 'women')) {
                $score += 10;
                $reasons[] = "✓ Tailored for Filipiniana women's silhouette";
            } else {
                $score += 2;
            }
        } elseif ($prefGender === 'kids') {
            if (str_contains($nameLower, 'kid') || str_contains($nameLower, 'boy') || str_contains($nameLower, 'girl') || str_contains($targetGroup, 'kid')) {
                $score += 10;
                $reasons[] = "✓ Sized specifically for children & youth";
            } else {
                $score += 2;
            }
        } else {
            // Default Men / Unisex
            if (!str_contains($nameLower, 'filipiniana') && !str_contains($nameLower, 'terno') && !str_contains($targetGroup, 'women')) {
                $score += 10;
            } else {
                $score += 5;
            }
        }

        // 5. COLLAR & STYLE MATCH (10 Pts)
        $prefCollar = $pref['collar'] ?? null;
        if ($prefCollar) {
            if (str_contains($nameLower, $prefCollar) || strtolower($product->collar_type ?? '') === $prefCollar) {
                $score += 10;
                $reasons[] = "✓ Features requested " . ucfirst($prefCollar) . " collar style";
            } else {
                $score += 4;
            }
        } else {
            $score += 9;
        }

        // 6. POPULARITY & REPUTATION (10 Pts)
        $views = (int) ($product->views ?? 0);
        if ($views > 40) {
            $score += 10;
            $reasons[] = "✓ Top viewed & highly rated by customers";
        } elseif ($views > 10) {
            $score += 8;
        } else {
            $score += 6;
        }

        // 7. STOCK & TAILORING READINESS (10 Pts)
        $stock = (int) ($product->stock ?? 0);
        if ($stock > 0) {
            $score += 10;
            $reasons[] = "✓ In stock & ready for custom sizing";
        } else {
            $score += 5;
        }

        // Normalize score to percentage (max 98% for realism)
        $finalScore = min(98, max(60, (int) round($score)));

        return [
            'score' => $finalScore,
            'reasons' => array_slice(array_unique($reasons), 0, 3),
        ];
    }

    /**
     * Recommend Top 3 diverse products with Hard Constraint Pre-Filtering and 7-Factor Weighted Scoring.
     */
    public static function recommend(string $message, array $sessionContext = []): array
    {
        $pref = self::extractPreferences($message, $sessionContext);

        try {
            $query = Product::where('status', 'approved');

            // 1. HARD DEMOGRAPHIC FILTER
            if (!empty($pref['gender'])) {
                if ($pref['gender'] === 'women') {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%filipiniana%')
                          ->orWhere('name', 'like', '%terno%')
                          ->orWhere('name', 'like', '%dress%')
                          ->orWhere('name', 'like', '%lady%')
                          ->orWhere('description', 'like', '%women%')
                          ->orWhere('description', 'like', '%filipiniana%');
                    });
                } elseif ($pref['gender'] === 'kids') {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%kid%')
                          ->orWhere('name', 'like', '%boy%')
                          ->orWhere('name', 'like', '%girl%')
                          ->orWhere('name', 'like', '%bata%')
                          ->orWhere('description', 'like', '%kid%')
                          ->orWhere('description', 'like', '%child%');
                    });
                } elseif ($pref['gender'] === 'men') {
                    $query->where(function ($q) {
                        $q->where('name', 'not like', '%filipiniana%')
                          ->where('name', 'not like', '%terno dress%')
                          ->where('name', 'not like', '%gown%');
                    });
                }
            }

            // 2. HARD BUDGET FILTER (Up to 10% tolerance for close matching)
            if (!empty($pref['max_budget'])) {
                $maxAllowedPrice = (float) $pref['max_budget'] * 1.10;
                $query->where('price', '<=', $maxAllowedPrice);
            }

            // 3. HARD FABRIC FILTER (If specifically required)
            if (!empty($pref['fabric'])) {
                $fabricKeyword = $pref['fabric'];
                $fabricQuery = clone $query;
                $fabricQuery->where(function ($q) use ($fabricKeyword) {
                    $q->where('name', 'like', "%{$fabricKeyword}%")
                      ->orWhere('description', 'like', "%{$fabricKeyword}%");
                });

                // If products match this fabric, enforce it; otherwise keep previous pool
                if ($fabricQuery->exists()) {
                    $query = $fabricQuery;
                }
            }

            $products = $query->take(15)->get();

            // Fallback if strict filter yields 0 in test or sparse database
            if ($products->isEmpty()) {
                $products = Product::where('status', 'approved')->take(10)->get();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("RecommendationEngine query error: " . $e->getMessage());
            try {
                $products = Product::take(10)->get();
            } catch (\Throwable $ex) {
                $products = collect();
            }
        }

        if ($products->isEmpty()) {
            return [
                'preferences' => $pref,
                'products' => [],
                'refinements' => self::generateRefinementChips($pref, []),
            ];
        }

        // Score eligible products with the 7-Factor Weighted Formula
        $scored = $products->map(function ($p) use ($pref) {
            $res = self::scoreProduct($p, $pref);
            return [
                'product' => $p,
                'score' => $res['score'],
                'reasons' => $res['reasons'],
                'price' => (float) ($p->price ?? 0),
            ];
        })->sortByDesc('score')->values();

        if ($scored->isEmpty()) {
            return [
                'preferences' => $pref,
                'products' => [],
                'refinements' => self::generateRefinementChips($pref, []),
            ];
        }

        // Select 3 diverse items:
        // 1. 🥇 Best Overall Match
        $bestMatch = $scored->first();

        // 2. 💰 Budget Value Pick (Lowest price among eligible candidates)
        $candidates = $scored->slice(1);
        $budgetPick = $candidates->sortBy('price')->first();

        // 3. 🥈 Alternative Choice (Different fabric or collar than Best Match)
        $bestFabric = strtolower($bestMatch['product']->fabric_type ?? '');
        $alternative = $candidates->first(function ($item) use ($bestFabric, $budgetPick) {
            $f = strtolower($item['product']->fabric_type ?? '');
            return $f !== $bestFabric && (!isset($budgetPick) || $item['product']->id !== $budgetPick['product']->id);
        }) ?: $candidates->first(function ($item) use ($budgetPick) {
            return !isset($budgetPick) || $item['product']->id !== $budgetPick['product']->id;
        }) ?: $scored->get(1);

        $selected = collect([
            array_merge($bestMatch, ['badge' => '🥇 Best Overall Match', 'tier' => 'best']),
        ]);

        if ($alternative && $alternative['product']->id !== $bestMatch['product']->id) {
            $selected->push(array_merge($alternative, ['badge' => '✨ Modern Alternative', 'tier' => 'alternative']));
        }

        if ($budgetPick && !$selected->contains('product.id', $budgetPick['product']->id)) {
            $selected->push(array_merge($budgetPick, ['badge' => '💰 Best Budget Value', 'tier' => 'budget']));
        } elseif ($scored->count() > 2 && $selected->count() < 3) {
            $third = $scored->first(fn($x) => !$selected->contains('product.id', $x['product']->id));
            if ($third) {
                $selected->push(array_merge($third, ['badge' => '⭐ Recommended Option', 'tier' => 'recommended']));
            }
        }

        $formattedProducts = $selected->map(function ($item) {
            $p = $item['product'];
            return [
                'id' => $p->id,
                'name' => $p->name,
                'price' => number_format((float) ($p->price ?? 0), 2),
                'raw_price' => (float) ($p->price ?? 0),
                'image' => method_exists($p, 'getImageUrl') ? $p->getImageUrl() : asset('uploads/products/default.jpg'),
                'url' => url('/products/' . $p->id),
                'fabric' => $p->fabric_type ?? 'Lumban Hand-Embroidered',
                'collar' => $p->collar_type ?? 'Classic Pointed',
                'score' => $item['score'],
                'badge' => $item['badge'],
                'tier' => $item['tier'],
                'reasons' => $item['reasons'],
            ];
        })->toArray();

        // Generate context-aware refinement chips
        $refinements = self::generateRefinementChips($pref, $formattedProducts);

        return [
            'preferences' => $pref,
            'products' => $formattedProducts,
            'refinements' => $refinements,
        ];
    }

    /**
     * Generate context-aware interactive refinement chips based on current filters and results.
     */
    private static function generateRefinementChips(array $pref, array $products): array
    {
        $chips = [];

        // 1. Budget refinement (only if not already low budget)
        if (empty($pref['max_budget']) || $pref['max_budget'] > 3000) {
            $chips[] = ['label' => '💰 Show budget options (< ₱3,000)', 'prompt' => 'Show me more affordable Barongs under ₱3,000'];
        }

        // 2. Fabric refinement (only suggest if not already filtered to that fabric)
        $currentFabric = $pref['fabric'] ?? '';
        if ($currentFabric !== 'piña') {
            $chips[] = ['label' => '👑 Show Piña-Seda only', 'prompt' => 'Show me authentic Piña-Seda Barongs'];
        } elseif ($currentFabric !== 'jusi') {
            $chips[] = ['label' => '🧵 Compare with Jusi Silk', 'prompt' => 'Show me Jusi Silk Barong alternatives'];
        }

        // 3. Collar refinement (only if collar not already chosen)
        if (empty($pref['collar'])) {
            $chips[] = ['label' => '👔 Modern Mandarin Collar', 'prompt' => 'Show Barongs with Mandarin / Chinese collar'];
        }

        // 4. Comparison refinement (only if multiple products exist)
        if (count($products) >= 2) {
            $chips[] = ['label' => '⚖️ Compare Top 2 options', 'prompt' => 'Compare the top 2 recommended barongs'];
        }

        // 5. Occasion refinement fallback
        if (empty($pref['occasion'])) {
            $chips[] = ['label' => '🤵 Wedding Groom Collection', 'prompt' => 'Recommend a Barong for a wedding groom'];
        }

        return array_slice($chips, 0, 4);
    }
}

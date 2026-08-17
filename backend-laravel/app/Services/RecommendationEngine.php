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

        // 5. Gender / Category extraction
        if (preg_match('/\b(filipiniana|dress|terno|gown|women|lady barong|babae)\b/i', $lower)) {
            $pref['gender'] = 'women';
        } elseif (preg_match('/\b(kids?|boys?|girls?|bata)\b/i', $lower)) {
            $pref['gender'] = 'kids';
        }

        return $pref;
    }

    /**
     * Compute multi-factor recommendation score for a product against user preferences.
     * Weights:
     * - Budget Match: 25%
     * - Occasion Match: 25%
     * - Fabric / Style Match: 25%
     * - Popularity & Views: 15%
     * - Availability / Stock: 10%
     */
    public static function scoreProduct(Product $product, array $pref): array
    {
        $score = 0;
        $reasons = [];
        $price = (float) ($product->price ?? 0);
        $nameLower = strtolower($product->name . ' ' . $product->description . ' ' . ($product->fabric_type ?? ''));

        // 1. BUDGET MATCH (25 Pts)
        if (!empty($pref['max_budget'])) {
            $maxBudget = (float) $pref['max_budget'];
            if ($price <= $maxBudget) {
                $score += 25;
                $savings = $maxBudget - $price;
                if ($savings > 0) {
                    $reasons[] = "✓ Within budget (₱" . number_format($price, 2) . " fits your ₱" . number_format($maxBudget) . " limit)";
                } else {
                    $reasons[] = "✓ Exactly matches your ₱" . number_format($maxBudget) . " budget";
                }
            } elseif ($price <= $maxBudget * 1.15) {
                $score += 15; // Close to budget
                $reasons[] = "✓ Near budget at ₱" . number_format($price, 2);
            } else {
                $score += 5;
            }
        } else {
            $score += 22; // Default neutral budget score
            $reasons[] = "✓ Great value at ₱" . number_format($price, 2);
        }

        // 2. OCCASION MATCH (25 Pts)
        $occasion = $pref['occasion'] ?? 'general';
        if ($occasion === 'wedding') {
            if (str_contains($nameLower, 'piña') || str_contains($nameLower, 'cocoon') || str_contains($nameLower, 'wedding') || str_contains($nameLower, 'groom') || str_contains($nameLower, 'jusi')) {
                $score += 25;
                $reasons[] = "✓ Ideal for wedding ceremonies & formal entourage";
            } elseif (str_contains($nameLower, 'polo') || str_contains($nameLower, 'short')) {
                $score += 8;
            } else {
                $score += 18;
                $reasons[] = "✓ Elegant formalwear suitable for weddings";
            }
        } elseif ($occasion === 'graduation') {
            if (str_contains($nameLower, 'organza') || str_contains($nameLower, 'jusi') || str_contains($nameLower, 'graduation')) {
                $score += 25;
                $reasons[] = "✓ Perfect lightweight comfort for graduation ceremonies";
            } else {
                $score += 18;
                $reasons[] = "✓ Suitable for academic & formal commencement";
            }
        } elseif ($occasion === 'casual') {
            if (str_contains($nameLower, 'polo') || str_contains($nameLower, 'short') || str_contains($nameLower, 'casual') || str_contains($nameLower, 'linen')) {
                $score += 25;
                $reasons[] = "✓ Comfortable modern cut for everyday & office wear";
            } else {
                $score += 14;
            }
        } else {
            $score += 20;
            $reasons[] = "✓ Versatile for formal events, ceremonies, and galas";
        }

        // 3. FABRIC & STYLE MATCH (25 Pts)
        $prefFabric = $pref['fabric'] ?? null;
        if ($prefFabric) {
            if (str_contains($nameLower, $prefFabric)) {
                $score += 25;
                $reasons[] = "✓ Authentic handcrafted " . ucfirst($prefFabric) . " fabric";
            } else {
                $score += 10;
            }
        } else {
            $score += 22;
            $fabricType = $product->fabric_type ?? 'Lumban Hand-Embroidered';
            $reasons[] = "✓ Hand-embroidered in Lumban ({$fabricType})";
        }

        // 4. POPULARITY & REVIEWS (15 Pts)
        $views = (int) ($product->views ?? 0);
        if ($views > 50) {
            $score += 15;
            $reasons[] = "✓ Customer favorite & highly viewed piece";
        } elseif ($views > 10) {
            $score += 12;
        } else {
            $score += 10;
        }

        // 5. STOCK & READY TAILORING (10 Pts)
        $stock = (int) ($product->stock ?? 0);
        if ($stock > 0) {
            $score += 10;
            $reasons[] = "✓ In stock & ready for custom sizing";
        } else {
            $score += 5;
        }

        // Normalize score to percentage (max 97% for realism)
        $finalScore = min(97, max(60, (int) round($score)));

        return [
            'score' => $finalScore,
            'reasons' => array_slice(array_unique($reasons), 0, 3),
        ];
    }

    /**
     * Recommend Top 3 diverse products (Best Match, Alternative Choice, Budget Value) with calculated scores.
     */
    public static function recommend(string $message, array $sessionContext = []): array
    {
        $pref = self::extractPreferences($message, $sessionContext);

        try {
            $query = Product::where('status', 'approved');

            // Gender filter if requested
            if (!empty($pref['gender']) && $pref['gender'] === 'women') {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%filipiniana%')
                      ->orWhere('name', 'like', '%terno%')
                      ->orWhere('name', 'like', '%dress%')
                      ->orWhere('name', 'like', '%lady%')
                      ->orWhere('target_group', 'like', '%Women%');
                });
            }

            $products = $query->get();

            if ($products->isEmpty()) {
                $products = Product::where('status', 'approved')->take(10)->get();
            }
        } catch (\Throwable $e) {
            $products = collect();
        }

        if ($products->isEmpty()) {
            return [
                'preferences' => $pref,
                'products' => [],
                'refinements' => self::generateRefinementChips($pref, []),
            ];
        }

        // Score all products
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
                'refinements' => [],
            ];
        }

        // Pick 3 diverse items:
        // 1. 🥇 Best Overall Match
        $bestMatch = $scored->first();

        // 2. 💰 Budget Value Pick (Lowest price among top 60% suitable)
        $candidates = $scored->slice(1);
        $budgetPick = $candidates->sortBy('price')->first();

        // 3. 🥈 Alternative Choice (Different fabric or style than Best Match)
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
                'score' => $item['score'],
                'badge' => $item['badge'],
                'tier' => $item['tier'],
                'reasons' => $item['reasons'],
            ];
        })->toArray();

        // Generate intelligent refinement chips
        $refinements = self::generateRefinementChips($pref, $formattedProducts);

        return [
            'preferences' => $pref,
            'products' => $formattedProducts,
            'refinements' => $refinements,
        ];
    }

    /**
     * Generate 3-4 interactive refinement suggestion chips.
     */
    private static function generateRefinementChips(array $pref, array $products): array
    {
        $chips = [];

        if (empty($pref['max_budget']) || $pref['max_budget'] > 3000) {
            $chips[] = ['label' => '💰 Show budget options (< ₱3,000)', 'prompt' => 'Show me more affordable Barongs under ₱3,000'];
        }

        if (empty($pref['fabric']) || $pref['fabric'] !== 'piña') {
            $chips[] = ['label' => '👑 Show Piña-Seda only', 'prompt' => 'Show me authentic Piña-Seda Barongs'];
        }

        if (empty($pref['collar']) || $pref['collar'] !== 'mandarin') {
            $chips[] = ['label' => '👔 Modern Mandarin Collar', 'prompt' => 'Show Barongs with Mandarin / Chinese collar'];
        }

        if (count($products) >= 2) {
            $chips[] = ['label' => '⚖️ Compare Top 2 options', 'prompt' => 'Compare the top 2 recommended barongs'];
        }

        return array_slice($chips, 0, 4);
    }
}

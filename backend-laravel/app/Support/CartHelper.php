<?php

namespace App\Support;

use App\Models\Product;
use App\Support\VariationFormatter;

class CartHelper
{
    /**
     * Normalize size string (trim, standard uppercase for apparel sizes, null for empty/default).
     */
    public static function normalizeSize(?string $size): ?string
    {
        if ($size === null) {
            return null;
        }

        $s = trim($size);
        if ($s === '' || strcasecmp($s, 'Standard') === 0 || strcasecmp($s, 'Standard Size') === 0 || strcasecmp($s, 'Default') === 0 || strcasecmp($s, 'N/A') === 0) {
            return null;
        }

        // Standardize common apparel sizes to uppercase (S, M, L, XL, 2XL, 3XL, etc.)
        if (preg_match('/^(xs|s|m|l|xl|xxl|2xl|3xl|4xl|5xl|free\s*size)$/i', $s)) {
            return strtoupper(str_replace(' ', '', $s));
        }

        return $s;
    }

    /**
     * Resolve and normalize variation string/object/path into a canonical variation label.
     */
    public static function normalizeVariation(mixed $variation, ?Product $product = null): ?string
    {
        if ($variation === null) {
            return null;
        }

        $rawVar = is_array($variation)
            ? ($variation['name'] ?? $variation['label'] ?? $variation['title'] ?? '')
            : (string) $variation;

        $v = trim($rawVar);
        if ($v === '') {
            return null;
        }

        if (!$product) {
            if (strcasecmp($v, 'Original') === 0 || strcasecmp($v, 'Default') === 0) {
                return null;
            }
            return $v;
        }

        $variants = VariationFormatter::buildStyleVariants($product);

        // If product has no multi-variants configured, any "Original" / "Default" / image path points to base product
        if (empty($variants) || count($variants) <= 1) {
            // Check if variation matches the only variant's name or is default
            if (strcasecmp($v, 'Original') === 0 || strcasecmp($v, 'Default') === 0) {
                return null;
            }

            if (!empty($variants[0]['name']) && strcasecmp($v, trim($variants[0]['name'])) === 0) {
                return $variants[0]['name'];
            }

            return null;
        }

        // Match against existing product variants
        // 1. Exact match or case-insensitive match on variant name
        foreach ($variants as $variant) {
            $variantName = trim($variant['name'] ?? '');
            if ($variantName !== '' && strcasecmp($v, $variantName) === 0) {
                return $variantName;
            }
        }

        // 2. Match by variant image path or URL
        foreach ($variants as $variant) {
            $imgPath = $variant['image_path'] ?? '';
            $imgUrl = $variant['image_url'] ?? '';
            if ($imgPath !== '' && (VariationFormatter::normalizePath($v) === $imgPath || str_contains($v, $imgPath))) {
                return $variant['name'];
            }
            if ($imgUrl !== '' && (strcasecmp($v, $imgUrl) === 0 || basename($v) === basename($imgUrl))) {
                return $variant['name'];
            }
        }

        // 3. Fallback to VariationFormatter label resolution
        $resolvedLabel = VariationFormatter::label($v, $product->image);
        if ($resolvedLabel && strcasecmp($resolvedLabel, 'Original') !== 0) {
            return $resolvedLabel;
        }

        // If it resolved to "Original" and variant 0 has a specific name
        if (isset($variants[0]['name']) && !empty($variants[0]['name']) && strcasecmp($variants[0]['name'], 'Original') !== 0) {
            return $variants[0]['name'];
        }

        return null;
    }

    /**
     * Generate the canonical cart item key.
     * Guaranteed to match identical product + size + variation configuration regardless of input formatting.
     */
    public static function getCanonicalKey(string $productId, ?string $size = null, mixed $variation = null, ?Product $product = null): string
    {
        $cleanId = trim($productId);
        $normSize = self::normalizeSize($size);
        $normVar = self::normalizeVariation($variation, $product);

        return $cleanId . '_' . ($normSize ?? '') . '_' . ($normVar ?? '');
    }

    /**
     * Consolidate and deduplicate an existing cart array into canonical keys.
     * If duplicate configurations exist, their quantities are safely summed up to current stock.
     */
    public static function consolidateCart(array $cart): array
    {
        $consolidated = [];

        foreach ($cart as $existingKey => $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $productId = (string) $item['id'];
            $product = Product::with('seller')->find($productId);
            if (!$product) {
                continue;
            }

            $size = self::normalizeSize($item['size'] ?? null);
            $variation = self::normalizeVariation($item['variation'] ?? null, $product);
            $canonicalKey = self::getCanonicalKey($productId, $size, $variation, $product);

            // Compute available stock for size / product
            $isPreorder = $product->isPreorder();
            if ($isPreorder) {
                if ($product->status !== 'approved') {
                    continue;
                }
                if ($size && !empty($product->sizes) && !in_array($size, $product->sizes)) {
                    continue;
                }
                $itemQty = max(1, (int) ($item['quantity'] ?? 1));

                if (isset($consolidated[$canonicalKey])) {
                    $consolidated[$canonicalKey]['quantity'] = $consolidated[$canonicalKey]['quantity'] + $itemQty;
                } else {
                    $seller = $product->seller;
                    $image = VariationFormatter::getImageForVariation($variation, $product) ?: $product->getImageUrl();

                    $consolidated[$canonicalKey] = [
                        'key'                 => $canonicalKey,
                        'id'                  => $product->id,
                        'name'                => $product->name,
                        'price'               => (float) $product->sale_price,
                        'image'               => $image,
                        'quantity'            => $itemQty,
                        'size'                => $size,
                        'variation'           => $variation,
                        'sellerId'            => $product->sellerId,
                        'shippingFee'         => (float) ($product->shippingFee ?? 0),
                        'original_price'      => (float) $product->price,
                        'discount_percentage' => $product->isSaleActive() ? (float) $product->discount_percentage : 0,
                        'is_on_sale'          => $product->isSaleActive(),
                        'category_name'       => $product->category->name ?? 'Traditional',
                        'shop_name'           => $seller ? ($seller->shopName ?: $seller->name ?: 'Lumban Heritage Shop') : 'Lumban Heritage Shop',
                        'inventory_mode'      => 'preorder',
                        'handling_days'       => (int) ($product->handling_days ?? 2),
                    ];
                }
            } else {
                $availableStock = (int) $product->stock;
                if ($size && !empty($product->size_stocks) && isset($product->size_stocks[$size])) {
                    $availableStock = (int) $product->size_stocks[$size];
                }

                if ($availableStock <= 0) {
                    continue;
                }

                $itemQty = max(1, (int) ($item['quantity'] ?? 1));

                if (isset($consolidated[$canonicalKey])) {
                    // Merge quantity of duplicate configuration
                    $combinedQty = $consolidated[$canonicalKey]['quantity'] + $itemQty;
                    $consolidated[$canonicalKey]['quantity'] = min($combinedQty, $availableStock);
                } else {
                    // Ensure dynamic fields are refreshed
                    $seller = $product->seller;
                    $image = VariationFormatter::getImageForVariation($variation, $product) ?: $product->getImageUrl();

                    $consolidated[$canonicalKey] = [
                        'key'                 => $canonicalKey,
                        'id'                  => $product->id,
                        'name'                => $product->name,
                        'price'               => (float) $product->sale_price,
                        'image'               => $image,
                        'quantity'            => min($itemQty, $availableStock),
                        'size'                => $size,
                        'variation'           => $variation,
                        'sellerId'            => $product->sellerId,
                        'shippingFee'         => (float) ($product->shippingFee ?? 0),
                        'original_price'      => (float) $product->price,
                        'discount_percentage' => $product->isSaleActive() ? (float) $product->discount_percentage : 0,
                        'is_on_sale'          => $product->isSaleActive(),
                        'category_name'       => $product->category->name ?? 'Traditional',
                        'shop_name'           => $seller ? ($seller->shopName ?: $seller->name ?: 'Lumban Heritage Shop') : 'Lumban Heritage Shop',
                        'inventory_mode'      => 'available_stock',
                        'handling_days'       => (int) ($product->handling_days ?? 2),
                    ];
                }
            }
        }

        return $consolidated;
    }

    /**
     * Authoritatively merges two cart arrays (e.g., guest cart and saved user cart on login).
     */
    public static function mergeCarts(array $primaryCart, array $secondaryCart): array
    {
        $merged = self::consolidateCart($primaryCart);

        foreach ($secondaryCart as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $productId = (string) $item['id'];
            $product = Product::find($productId);
            if (!$product) {
                continue;
            }

            $size = self::normalizeSize($item['size'] ?? null);
            $variation = self::normalizeVariation($item['variation'] ?? null, $product);
            $canonicalKey = self::getCanonicalKey($productId, $size, $variation, $product);

            $isPreorder = $product->isPreorder();
            if ($isPreorder) {
                if ($product->status !== 'approved') {
                    continue;
                }
                if ($size && !empty($product->sizes) && !in_array($size, $product->sizes)) {
                    continue;
                }
                $secondaryQty = max(1, (int) ($item['quantity'] ?? 1));

                if (isset($merged[$canonicalKey])) {
                    $merged[$canonicalKey]['quantity'] = $merged[$canonicalKey]['quantity'] + $secondaryQty;
                } else {
                    $image = VariationFormatter::getImageForVariation($variation, $product) ?: $product->getImageUrl();
                    $seller = $product->seller;

                    $merged[$canonicalKey] = [
                        'key'                 => $canonicalKey,
                        'id'                  => $product->id,
                        'name'                => $product->name,
                        'price'               => (float) $product->sale_price,
                        'image'               => $image,
                        'quantity'            => $secondaryQty,
                        'size'                => $size,
                        'variation'           => $variation,
                        'sellerId'            => $product->sellerId,
                        'shippingFee'         => (float) ($product->shippingFee ?? 0),
                        'original_price'      => (float) $product->price,
                        'discount_percentage' => $product->isSaleActive() ? (float) $product->discount_percentage : 0,
                        'is_on_sale'          => $product->isSaleActive(),
                        'category_name'       => $product->category->name ?? 'Traditional',
                        'shop_name'           => $seller ? ($seller->shopName ?: $seller->name ?: 'Lumban Heritage Shop') : 'Lumban Heritage Shop',
                        'inventory_mode'      => 'preorder',
                        'handling_days'       => (int) ($product->handling_days ?? 2),
                    ];
                }
            } else {
                $availableStock = (int) $product->stock;
                if ($size && !empty($product->size_stocks) && isset($product->size_stocks[$size])) {
                    $availableStock = (int) $product->size_stocks[$size];
                }

                if ($availableStock <= 0) {
                    continue;
                }

                $secondaryQty = max(1, (int) ($item['quantity'] ?? 1));

                if (isset($merged[$canonicalKey])) {
                    // Sum quantities when merging, capped at available stock
                    $newQty = $merged[$canonicalKey]['quantity'] + $secondaryQty;
                    $merged[$canonicalKey]['quantity'] = min($newQty, $availableStock);
                } else {
                    $image = VariationFormatter::getImageForVariation($variation, $product) ?: $product->getImageUrl();
                    $seller = $product->seller;

                    $merged[$canonicalKey] = [
                        'key'                 => $canonicalKey,
                        'id'                  => $product->id,
                        'name'                => $product->name,
                        'price'               => (float) $product->sale_price,
                        'image'               => $image,
                        'quantity'            => min($secondaryQty, $availableStock),
                        'size'                => $size,
                        'variation'           => $variation,
                        'sellerId'            => $product->sellerId,
                        'shippingFee'         => (float) ($product->shippingFee ?? 0),
                        'original_price'      => (float) $product->price,
                        'discount_percentage' => $product->isSaleActive() ? (float) $product->discount_percentage : 0,
                        'is_on_sale'          => $product->isSaleActive(),
                        'category_name'       => $product->category->name ?? 'Traditional',
                        'shop_name'           => $seller ? ($seller->shopName ?: $seller->name ?: 'Lumban Heritage Shop') : 'Lumban Heritage Shop',
                        'inventory_mode'      => 'available_stock',
                        'handling_days'       => (int) ($product->handling_days ?? 2),
                    ];
                }
            }
        }

        return $merged;
    }
}

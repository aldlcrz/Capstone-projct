<?php

namespace App\Support;

class VariationFormatter
{
    public static function label(?string $variation, mixed $productImages = null): ?string
    {
        if ($variation === null || $variation === '') {
            return null;
        }

        if (!self::looksLikeImagePath($variation)) {
            return $variation;
        }

        $images = self::normalizeProductImages($productImages);
        if (empty($images)) {
            return 'Selected style';
        }

        foreach ($images as $i => $entry) {
            $url = is_array($entry) ? ($entry['url'] ?? $entry['path'] ?? '') : (string) $entry;
            $customLabel = is_array($entry)
                ? trim((string) ($entry['variation'] ?? $entry['label'] ?? ''))
                : '';

            if (self::pathsMatch($variation, $url)) {
                return $customLabel !== '' ? $customLabel : self::labelForIndex($i);
            }
        }

        return 'Selected style';
    }

    public static function labelForIndex(int $index, ?string $customLabel = null): string
    {
        $customLabel = trim((string) ($customLabel ?? ''));
        if ($customLabel !== '') {
            return $customLabel;
        }

        return $index === 0 ? 'Original' : 'Style ' . ($index + 1);
    }

    public static function normalizeProductImages(mixed $images): array
    {
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : [$images];
        }

        return is_array($images) ? array_values($images) : [];
    }

    public static function buildVariations(mixed $images, ?\App\Models\Product $product = null): array
    {
        $variations = [];

        // 1. Priority: If product has structured variations defined in $product->variations
        if ($product && !empty($product->variations) && is_array($product->variations)) {
            foreach ($product->variations as $i => $v) {
                if (is_array($v) && !empty($v['image'])) {
                    $variations[] = [
                        'url' => $product->getImageUrl($v['image']),
                        'label' => !empty($v['name']) ? trim($v['name']) : self::labelForIndex($i),
                    ];
                }
            }
        }

        // 2. Fallback to images array if variations is empty
        if (empty($variations)) {
            foreach (self::normalizeProductImages($images) as $i => $img) {
                if (is_array($img)) {
                    $url = $img['url'] ?? $img['path'] ?? '';
                    $label = trim((string) ($img['variation'] ?? $img['label'] ?? ''));
                } else {
                    $url = (string) $img;
                    $label = '';
                }

                if ($url === '') {
                    continue;
                }

                $resolvedUrl = $product ? $product->getImageUrl($url) : $url;

                $variations[] = [
                    'url' => $resolvedUrl,
                    'label' => self::labelForIndex($i, $label),
                ];
            }
        }

        if (empty($variations)) {
            $defaultUrl = $product ? $product->getImageUrl() : '/uploads/products/default.jpg';
            $variations[] = ['url' => $defaultUrl, 'label' => 'Original'];
        }

        return $variations;
    }

    public static function getImageForVariation(?string $variation, ?\App\Models\Product $product = null): ?string
    {
        if (!$product) {
            return null;
        }

        $allVariations = self::buildVariations($product->image, $product);
        if (empty($allVariations)) {
            return $product->getImageUrl();
        }

        if (empty($variation) || strcasecmp($variation, 'Original') === 0) {
            return $allVariations[0]['url'] ?? $product->getImageUrl();
        }

        $varTrimmed = trim($variation);

        // Match by variation label (e.g. "KOI", "CREAM", "GREEN")
        foreach ($allVariations as $v) {
            if (strcasecmp(trim($v['label']), $varTrimmed) === 0) {
                return $v['url'];
            }
        }

        // Match by index e.g. "Style 2" or "2"
        if (preg_match('/(?:style\s*(\d+)|^\s*(\d+)\s*$)/i', $varTrimmed, $m)) {
            $idx = (int) ($m[1] ?: $m[2]) - 1;
            if (isset($allVariations[$idx]['url'])) {
                return $allVariations[$idx]['url'];
            }
        }

        // Match by partial path / filename match
        foreach ($allVariations as $v) {
            if (self::pathsMatch($varTrimmed, $v['url'])) {
                return $v['url'];
            }
        }

        return $allVariations[0]['url'] ?? $product->getImageUrl();
    }

    private static function looksLikeImagePath(string $value): bool
    {
        return str_contains($value, '/')
            || str_contains($value, '\\')
            || (bool) preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $value);
    }

    private static function pathsMatch(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        return strcasecmp(basename($a), basename($b)) === 0;
    }
}

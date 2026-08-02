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

    public static function buildVariations(mixed $images): array
    {
        $variations = [];

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

            $variations[] = [
                'url' => $url,
                'label' => self::labelForIndex($i, $label),
            ];
        }

        if (empty($variations)) {
            $variations[] = ['url' => '', 'label' => 'Original'];
        }

        return $variations;
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

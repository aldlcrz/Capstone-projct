<?php

namespace App\Support;

class VariationFormatter
{
    /**
     * Normalize an image path to a canonical storage path for strict deduplication.
     * e.g., '/storage/products/cover/a.jpg' -> 'products/cover/a.jpg'
     */
    public static function normalizePath(?string $path): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        $p = str_replace('\\', '/', trim($path));
        $p = ltrim($p, '/');

        // Strip leading storage/ or uploads/
        if (str_starts_with($p, 'storage/')) {
            $p = substr($p, 8);
        } elseif (str_starts_with($p, 'uploads/')) {
            $p = substr($p, 8);
        }

        return ltrim($p, '/');
    }

    /**
     * Build the definitive list of product photographs for gallery carousels, thumbnails, and zoom inspection.
     * Includes the primary cover image, any distinct variant cover photos, and all uploaded gallery images.
     *
     * @return array<int, array{url: string, path: string}>
     */
    public static function buildGalleryImages(mixed $images, ?\App\Models\Product $product = null): array
    {
        $gallery = [];
        $seenPaths = [];

        // Map of canonical image path -> [variant_id, variant_name]
        $variantImageMap = [];
        $variantCandidateImages = [];

        if ($product && !empty($product->variations) && is_array($product->variations)) {
            foreach ($product->variations as $i => $v) {
                if (!is_array($v)) {
                    continue;
                }
                $varName = trim((string)($v['name'] ?? ''));
                if ($varName === '') {
                    $varName = self::labelForIndex($i);
                }

                // Collect all images belonging to this variant (from images[] array or single image)
                $vImagesList = [];
                if (!empty($v['images']) && is_array($v['images'])) {
                    $vImagesList = $v['images'];
                } elseif (!empty($v['image'])) {
                    $vImagesList = [$v['image']];
                }

                foreach ($vImagesList as $vImg) {
                    $rawPath = is_array($vImg) ? ($vImg['url'] ?? $vImg['path'] ?? '') : (string)$vImg;
                    $norm = self::normalizePath($rawPath);
                    if ($norm !== '') {
                        if (!isset($variantImageMap[$norm])) {
                            $variantImageMap[$norm] = [
                                'variant_id'   => $i,
                                'variant_name' => $varName,
                            ];
                        }
                        $variantCandidateImages[] = $rawPath;
                    }
                }
            }
        }

        // Gather images from $product->image or passed $images
        $rawImages = self::normalizeProductImages($images);
        if ($product && empty($rawImages)) {
            $rawImages = self::normalizeProductImages($product->image);
        }

        // Prepend variant images so the primary cover and variant photos are prioritized
        $allCandidates = array_merge($variantCandidateImages, $rawImages);

        foreach ($allCandidates as $candidate) {
            $rawPath = is_array($candidate) ? ($candidate['url'] ?? $candidate['path'] ?? '') : (string) $candidate;
            if (!$rawPath || $rawPath === 'Array' || $rawPath === '[]' || $rawPath === '[') {
                continue;
            }

            $canonical = self::normalizePath($rawPath);
            if ($canonical === '' || isset($seenPaths[$canonical])) {
                continue;
            }

            $seenPaths[$canonical] = true;
            $resolvedUrl = $product ? $product->getImageUrl($rawPath) : $rawPath;

            $meta = $variantImageMap[$canonical] ?? null;

            // Fallback: If this is the first image and no variation was explicitly assigned, attach to Variant 0 if variations exist
            if ($meta === null && count($gallery) === 0 && $product && !empty($product->variations) && is_array($product->variations)) {
                $firstVarName = trim((string)($product->variations[0]['name'] ?? ''));
                $meta = [
                    'variant_id'   => 0,
                    'variant_name' => $firstVarName !== '' ? $firstVarName : self::labelForIndex(0),
                ];
            }

            $gallery[] = [
                'url'          => $resolvedUrl,
                'path'         => $canonical,
                'variant_id'   => $meta ? $meta['variant_id'] : null,
                'variant_name' => $meta ? $meta['variant_name'] : null,
            ];
        }

        if (empty($gallery)) {
            $defaultUrl = $product ? $product->getImageUrl() : '/uploads/products/default.jpg';
            $gallery[] = [
                'url'          => $defaultUrl,
                'path'         => 'products/default.jpg',
                'variant_id'   => null,
                'variant_name' => null,
            ];
        }

        return $gallery;
    }

    /**
     * Build the definitive list of purchasable styles/variants.
     * Contains ONLY actual style options (e.g. "Long Sleeve", "Barong Tagalog"), NOT general gallery detail shots.
     *
     * @return array<int, array{id: int, name: string, image_url: ?string, image_path: ?string}>
     */
    public static function buildStyleVariants(?\App\Models\Product $product = null): array
    {
        if (!$product || empty($product->variations) || !is_array($product->variations)) {
            return [];
        }

        $variants = [];
        foreach ($product->variations as $i => $v) {
            if (!is_array($v)) {
                continue;
            }

            $name = trim((string) ($v['name'] ?? ''));
            if ($name === '') {
                $name = self::labelForIndex($i);
            }

            $imgPath = !empty($v['image']) ? (string) $v['image'] : null;
            $imgUrl = $imgPath ? $product->getImageUrl($imgPath) : null;

            $variants[] = [
                'id'         => $i,
                'name'       => $name,
                'image'      => $imgUrl,
                'image_url'  => $imgUrl,
                'image_path' => $imgPath ? self::normalizePath($imgPath) : null,
            ];
        }

        return $variants;
    }

    /**
     * Build the list of visual design cards for mobile Buy Now modal,
     * where each uploaded photo belonging to a variant is shown with the variant's name.
     *
     * @return array<int, array{card_id: int, variant_id: int, variant_name: string, image_url: string, image_path: string, gallery_index: int}>
     */
    public static function buildVariantImageCards(?\App\Models\Product $product = null, array $galleryImages = []): array
    {
        if (!$product || empty($product->variations) || !is_array($product->variations)) {
            return [];
        }

        if (empty($galleryImages)) {
            $galleryImages = self::buildGalleryImages($product->image, $product);
        }

        $cards = [];
        $cardId = 0;
        $seenPaths = [];

        foreach ($product->variations as $varIdx => $v) {
            if (!is_array($v)) {
                continue;
            }

            $varName = trim((string) ($v['name'] ?? ''));
            if ($varName === '') {
                $varName = self::labelForIndex($varIdx);
            }

            // 1. Gather all photos explicitly belonging to this variant from galleryImages
            $variantPhotos = [];
            foreach ($galleryImages as $gIdx => $g) {
                if (isset($g['variant_id']) && (int)$g['variant_id'] === (int)$varIdx) {
                    $variantPhotos[] = [
                        'url'           => $g['url'],
                        'path'          => $g['path'] ?? self::normalizePath($g['url']),
                        'gallery_index' => $gIdx,
                    ];
                }
            }

            // 2. Also check if $v['images'] or $v['image'] has images not yet in $variantPhotos
            $rawImagesList = [];
            if (!empty($v['images']) && is_array($v['images'])) {
                $rawImagesList = $v['images'];
            } elseif (!empty($v['image'])) {
                $rawImagesList = [$v['image']];
            }

            foreach ($rawImagesList as $raw) {
                $rawPath = is_array($raw) ? ($raw['url'] ?? $raw['path'] ?? '') : (string)$raw;
                if (!$rawPath) continue;

                $norm = self::normalizePath($rawPath);
                $alreadyInList = false;
                foreach ($variantPhotos as $vp) {
                    if (self::pathsMatch($norm, $vp['path'])) {
                        $alreadyInList = true;
                        break;
                    }
                }

                if (!$alreadyInList) {
                    $url = $product->getImageUrl($rawPath);
                    $matchedGIdx = 0;
                    foreach ($galleryImages as $gIdx => $g) {
                        if (self::pathsMatch($norm, $g['path'] ?? '')) {
                            $matchedGIdx = $gIdx;
                            break;
                        }
                    }
                    $variantPhotos[] = [
                        'url'           => $url,
                        'path'          => $norm,
                        'gallery_index' => $matchedGIdx,
                    ];
                }
            }

            // 3. Fallback: if variant has no photos at all, use default cover
            if (empty($variantPhotos)) {
                $defaultUrl = $product->getImageUrl();
                $variantPhotos[] = [
                    'url'           => $defaultUrl,
                    'path'          => 'products/default.jpg',
                    'gallery_index' => 0,
                ];
            }

            // 4. Create a card for each photo belonging to this variant
            foreach ($variantPhotos as $photo) {
                $dedupKey = $varIdx . '_' . $photo['path'];
                if (isset($seenPaths[$dedupKey])) {
                    continue;
                }
                $seenPaths[$dedupKey] = true;

                $cards[] = [
                    'card_id'       => $cardId++,
                    'variant_id'    => (int)$varIdx,
                    'variant_name'  => $varName,
                    'image_url'     => $photo['url'],
                    'image_path'    => $photo['path'],
                    'gallery_index' => (int)$photo['gallery_index'],
                ];
            }
        }

        return $cards;
    }

    /**
     * Legacy compatibility wrapper.
     */
    public static function buildVariations(mixed $images, ?\App\Models\Product $product = null): array
    {
        $gallery = self::buildGalleryImages($images, $product);
        $result = [];
        foreach ($gallery as $i => $item) {
            $result[] = [
                'url'   => $item['url'],
                'path'  => $item['path'],
                'label' => self::labelForIndex($i),
            ];
        }
        return $result;
    }

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

    public static function getImageForVariation(?string $variation, ?\App\Models\Product $product = null): ?string
    {
        if (!$product) {
            return null;
        }

        $styleVariants = self::buildStyleVariants($product);
        $varTrimmed = trim((string) $variation);

        // Match by variant name
        foreach ($styleVariants as $v) {
            if (strcasecmp(trim($v['name']), $varTrimmed) === 0 && !empty($v['image_url'])) {
                return $v['image_url'];
            }
        }

        // Match by canonical path
        foreach ($styleVariants as $v) {
            if (!empty($v['image_path']) && self::pathsMatch($varTrimmed, $v['image_path'])) {
                return $v['image_url'];
            }
        }

        // Fallback to primary gallery cover photo
        $gallery = self::buildGalleryImages($product->image, $product);
        return $gallery[0]['url'] ?? $product->getImageUrl();
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

        $normA = self::normalizePath($a);
        $normB = self::normalizePath($b);
        if ($normA !== '' && $normA === $normB) {
            return true;
        }

        return strcasecmp(basename($a), basename($b)) === 0;
    }
}

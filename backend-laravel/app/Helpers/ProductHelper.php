<?php

namespace App\Helpers;

class ProductHelper
{
    public static function resolveImageUrl($value, $fallback = '/images/placeholder.png')
    {
        if ($value == null) return $fallback;
        
        if (is_array($value) && isset($value['url'])) {
            return self::resolveImageUrl($value['url'], $fallback);
        }

        if (!is_string($value)) return $fallback;

        $trimmed = trim($value);
        if (empty($trimmed)) return $fallback;

        if (preg_match('/^https?:\/\//i', $trimmed)) {
            return $trimmed;
        }

        if (str_starts_with($trimmed, 'data:') || str_starts_with($trimmed, 'blob:')) {
            return $trimmed;
        }

        // In Laravel, we use the asset() helper which points to public/
        if (str_starts_with($trimmed, 'uploads/')) {
            return asset('storage/' . $trimmed);
        }

        if (str_starts_with($trimmed, '/')) {
            return $trimmed;
        }

        return asset('storage/' . $trimmed);
    }

    public static function parseImageList($value)
    {
        if (is_array($value)) return $value;
        if (!is_string($value)) return [];

        $trimmed = trim($value);
        if (empty($trimmed)) return [];

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) return $decoded;

        return [$trimmed];
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductColorDetectorService
{
    /**
     * Standard curated palette of Filipino fashion colors.
     */
    public const PALETTE = [
        'Pink'          => ['blush', 'dusty rose', 'rose', 'pink', 'magenta', 'salmon'],
        'Blue'          => ['blue', 'sky blue', 'light blue'],
        'Navy Blue'     => ['navy', 'navy blue', 'midnight blue'],
        'Royal Blue'    => ['royal blue', 'sapphire'],
        'Green'         => ['green', 'olive', 'forest green'],
        'Emerald Green' => ['emerald', 'emerald green', 'dark green'],
        'Sage Green'    => ['sage', 'sage green', 'mint'],
        'Red'           => ['red', 'crimson', 'scarlet'],
        'Maroon'        => ['maroon', 'burgundy', 'wine', 'bordeaux'],
        'Black'         => ['black', 'charcoal', 'jet black', 'onyx'],
        'White'         => ['white', 'pure white', 'snow white'],
        'Natural'       => ['natural', 'ecru', 'pina', 'piña', 'cream', 'beige', 'champagne', 'tan', 'off-white'],
        'Gold'          => ['gold', 'golden', 'yellow', 'mustard'],
        'Purple'        => ['purple', 'violet', 'lavender', 'lilac', 'plum'],
        'Orange'        => ['orange', 'rust', 'terracotta', 'peach', 'coral'],
        'Multicolor'    => ['multicolor', 'multi-color', 'colorful', 'rainbow', 'printed', 'floral'],
    ];

    /**
     * Detect dominant garment color from image and/or text metadata.
     *
     * @param array|string|null $imagePath Path or array of paths to image
     * @param string|null $name Product name
     * @param string|null $description Product description
     * @return array{primary_color: string, colors: array, source: string}
     */
    public static function detect(array|string|null $imagePath = null, ?string $name = null, ?string $description = null): array
    {
        if (is_array($imagePath)) {
            $first = reset($imagePath);
            $imagePath = is_string($first) ? $first : null;
        }

        // 1. Text-based heuristic check (explicit color mentioned in name or description)
        $textResult = self::detectFromText($name, $description);
        if ($textResult !== null) {
            return $textResult;
        }

        // 2. Resolve image full path
        $fullPath = self::resolveImagePath($imagePath);

        // 3. Gemini Vision AI check (if configured and file exists)
        if ($fullPath && File::exists($fullPath)) {
            $geminiResult = self::detectWithGemini($fullPath);
            if ($geminiResult !== null) {
                return $geminiResult;
            }

            // 4. Local GD Algorithm check (fast background-subtracted center sampling)
            $gdResult = self::detectWithGD($fullPath);
            if ($gdResult !== null) {
                return $gdResult;
            }
        }

        // 5. Default heritage fallback
        return [
            'primary_color' => 'Natural',
            'colors'        => ['Natural'],
            'source'        => 'default_heritage',
        ];
    }

    /**
     * Detect color from product title and description.
     */
    public static function detectFromText(?string $name = null, ?string $description = null): ?array
    {
        $haystack = strtolower(trim(($name ?? '') . ' ' . ($description ?? '')));
        if (empty($haystack)) {
            return null;
        }

        foreach (self::PALETTE as $standardColor => $aliases) {
            foreach ($aliases as $alias) {
                // Look for word boundaries to avoid false positives (e.g. 'read' matching 'red')
                if (preg_match('/\b' . preg_quote($alias, '/') . '\b/i', $haystack)) {
                    return [
                        'primary_color' => $standardColor,
                        'colors'        => [$standardColor],
                        'source'        => 'text_analysis',
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Resolve image path to a readable local filesystem path.
     */
    private static function resolveImagePath(?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        if (File::exists($imagePath)) {
            return $imagePath;
        }

        // Check storage/app/public
        $storagePath = storage_path('app/public/' . ltrim($imagePath, '/'));
        if (File::exists($storagePath)) {
            return $storagePath;
        }

        // Check public/uploads or public/
        $publicPath = public_path(ltrim($imagePath, '/'));
        if (File::exists($publicPath)) {
            return $publicPath;
        }

        $publicUploads = public_path('uploads/' . ltrim($imagePath, '/'));
        if (File::exists($publicUploads)) {
            return $publicUploads;
        }

        $storagePublic = public_path('storage/' . ltrim($imagePath, '/'));
        if (File::exists($storagePublic)) {
            return $storagePublic;
        }

        return null;
    }

    /**
     * Detect garment color using Google Gemini Vision AI.
     */
    private static function detectWithGemini(string $fullPath): ?array
    {
        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        if (!$apiKey) {
            return null;
        }

        try {
            $imageData = base64_encode(File::get($fullPath));
            $mimeType = File::mimeType($fullPath) ?: 'image/jpeg';

            $prompt = 'Look at the main clothing / garment in this photo. Ignore the background wall, backdrop, model skin, and hair. Identify the primary color of the attire itself. Return ONLY a JSON object formatted as: {"primary_color": "...", "colors": ["..."]}. Valid standard colors: Pink, Red, Maroon, Blue, Navy Blue, Royal Blue, Green, Emerald Green, Sage Green, Black, White, Natural, Gold, Purple, Orange, Multicolor.';

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inlineData' => [
                                    'mimeType' => $mimeType,
                                    'data'     => $imageData,
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature'     => 0.2,
                    'maxOutputTokens' => 150,
                ]
            ];

            $model = config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-2.5-flash');
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::withOptions([
                'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
            ])->timeout(4.0)->post($url, $payload);

            if ($response->successful()) {
                $rawText = $response->json('candidates.0.content.parts.0.text');
                if ($rawText) {
                    $cleaned = preg_replace('/```json\s*|\s*```/', '', trim($rawText));
                    $data = json_decode($cleaned, true);
                    if (is_array($data) && !empty($data['primary_color'])) {
                        $pColor = self::normalizeColorName($data['primary_color']);
                        return [
                            'primary_color' => $pColor,
                            'colors'        => !empty($data['colors']) ? array_map([self::class, 'normalizeColorName'], (array)$data['colors']) : [$pColor],
                            'source'        => 'gemini_vision',
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::info('[ProductColorDetector] Gemini vision fallback to GD: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Detect garment color using PHP GD (Background-subtracted center sampling).
     */
    public static function detectWithGD(string $fullPath): ?array
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $img = null;
        $info = @getimagesize($fullPath);
        if (!$info) {
            return null;
        }

        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $img = @imagecreatefromjpeg($fullPath);
                break;
            case IMAGETYPE_PNG:
                $img = @imagecreatefrompng($fullPath);
                break;
            case IMAGETYPE_WEBP:
                $img = @imagecreatefromwebp($fullPath);
                break;
        }

        if (!$img) {
            return null;
        }

        $w = imagesx($img);
        $h = imagesy($img);

        if ($w < 20 || $h < 20) {
            imagedestroy($img);
            return null;
        }

        // 1. Identify Background Color from 4 outer corners
        $cornerSamples = [
            imagecolorat($img, (int)($w * 0.05), (int)($h * 0.05)),
            imagecolorat($img, (int)($w * 0.95), (int)($h * 0.05)),
            imagecolorat($img, (int)($w * 0.05), (int)($h * 0.95)),
            imagecolorat($img, (int)($w * 0.95), (int)($h * 0.95)),
        ];

        $bgHues = [];
        foreach ($cornerSamples as $c) {
            $r = ($c >> 16) & 0xFF;
            $g = ($c >> 8) & 0xFF;
            $b = $c & 0xFF;
            $hsl = self::rgbToHsl($r, $g, $b);
            $bgHues[] = $hsl;
        }

        // 2. Sample Central Torso / Garment Zone (30%-70% width, 25%-75% height)
        $minX = (int)($w * 0.28);
        $maxX = (int)($w * 0.72);
        $minY = (int)($h * 0.22);
        $maxY = (int)($h * 0.78);

        $colorVotes = [];
        $stepX = max(1, (int)(($maxX - $minX) / 25));
        $stepY = max(1, (int)(($maxY - $minY) / 30));

        for ($x = $minX; $x <= $maxX; $x += $stepX) {
            for ($y = $minY; $y <= $maxY; $y += $stepY) {
                $c = imagecolorat($img, $x, $y);
                $r = ($c >> 16) & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = $c & 0xFF;

                $hsl = self::rgbToHsl($r, $g, $b);
                $hue = $hsl['h'];
                $sat = $hsl['s'];
                $lum = $hsl['l'];

                // Filter 1: Check if pixel matches background corner palette
                $isBg = false;
                foreach ($bgHues as $bg) {
                    $hueDiff = abs($hue - $bg['h']);
                    if ($hueDiff > 180) $hueDiff = 360 - $hueDiff;
                    if ($hueDiff < 25 && abs($sat - $bg['s']) < 0.25 && abs($lum - $bg['l']) < 0.25) {
                        $isBg = true;
                        break;
                    }
                }
                if ($isBg) continue;

                // Filter 2: Check if pixel is human skin tone (face/arms/legs)
                // Skin tones: Hue ~12-38, Saturation 0.20-0.65, Lightness 0.35-0.80
                if ($hue >= 12 && $hue <= 38 && $sat >= 0.20 && $sat <= 0.65 && $lum >= 0.35 && $lum <= 0.80) {
                    continue;
                }

                // Filter 3: Check if pixel is hair (very dark near head)
                if ($y < (int)($h * 0.30) && $lum < 0.15) {
                    continue;
                }

                // Map pixel to garment color bucket
                $colorName = self::classifyHsl($hue, $sat, $lum, $r, $g, $b);
                if ($colorName) {
                    $colorVotes[$colorName] = ($colorVotes[$colorName] ?? 0) + 1;
                }
            }
        }

        imagedestroy($img);

        if (empty($colorVotes)) {
            return null;
        }

        arsort($colorVotes);
        $totalVotes = array_sum($colorVotes);
        $topColor = array_key_first($colorVotes);
        $topPct = ($colorVotes[$topColor] / $totalVotes) * 100;

        // If top color has clear dominance (>40%)
        if ($topPct >= 40) {
            return [
                'primary_color' => $topColor,
                'colors'        => [$topColor],
                'source'        => 'image_analysis',
            ];
        }

        // If multiple distinct colors share the garment (e.g. 2 strong clusters)
        $keys = array_keys($colorVotes);
        if (count($keys) >= 2 && ($colorVotes[$keys[1]] / $totalVotes) * 100 >= 25) {
            return [
                'primary_color' => $topColor,
                'colors'        => [$topColor, $keys[1]],
                'source'        => 'image_analysis_multicolor',
            ];
        }

        return [
            'primary_color' => $topColor,
            'colors'        => [$topColor],
            'source'        => 'image_analysis',
        ];
    }

    /**
     * Classify an HSL pixel into a fashion color name.
     */
    private static function classifyHsl(float $h, float $s, float $l, int $r, int $g, int $b): ?string
    {
        // Black / Charcoal
        if ($l < 0.18) {
            return 'Black';
        }

        // Pure White
        if ($l > 0.88 && $s < 0.14) {
            return 'White';
        }

        // Natural / Cream / Ecru (Piña fabric)
        if ($s < 0.28 && $l >= 0.60 && $l <= 0.88 && $h >= 30 && $h <= 60) {
            return 'Natural';
        }

        // Gray / Silver
        if ($s < 0.12 && $l >= 0.20 && $l <= 0.85) {
            return 'Silver';
        }

        // Pink / Blush / Dusty Rose: Hue 310 - 355
        if ($h >= 310 && $h <= 355 && $s >= 0.18 && $l >= 0.35) {
            return 'Pink';
        }

        // Red / Crimson: Hue 355 - 12 (high saturation)
        if (($h >= 355 || $h <= 12) && $s >= 0.40) {
            return $l < 0.35 ? 'Maroon' : 'Red';
        }

        // Maroon / Burgundy
        if (($h >= 340 || $h <= 15) && $l <= 0.35 && $s >= 0.25) {
            return 'Maroon';
        }

        // Orange / Peach: Hue 12 - 40
        if ($h > 12 && $h <= 40 && $s >= 0.35) {
            return 'Orange';
        }

        // Gold / Yellow: Hue 40 - 65
        if ($h > 40 && $h <= 65 && $s >= 0.30) {
            return 'Gold';
        }

        // Green / Emerald / Sage: Hue 65 - 170
        if ($h > 65 && $h <= 170 && $s >= 0.20) {
            if ($l < 0.35) return 'Emerald Green';
            if ($s < 0.35) return 'Sage Green';
            return 'Green';
        }

        // Blue / Sky / Navy / Royal: Hue 170 - 260
        if ($h > 170 && $h <= 260 && $s >= 0.20) {
            if ($l < 0.28) return 'Navy Blue';
            if ($l > 0.68) return 'Blue';
            return 'Royal Blue';
        }

        // Purple / Violet: Hue 260 - 310
        if ($h > 260 && $h < 310 && $s >= 0.20) {
            return 'Purple';
        }

        return null;
    }

    /**
     * Convert RGB to HSL.
     *
     * @return array{h: float, s: float, l: float}
     */
    private static function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $diff = $max - $min;

        $l = ($max + $min) / 2;

        if ($diff == 0) {
            $h = 0;
            $s = 0;
        } else {
            $s = $l > 0.5 ? $diff / (2 - $max - $min) : $diff / ($max + $min);

            switch ($max) {
                case $r:
                    $h = 60 * fmod((($g - $b) / $diff), 6);
                    if ($b > $g) $h += 360;
                    break;
                case $g:
                    $h = 60 * ((($b - $r) / $diff) + 2);
                    break;
                case $b:
                    $h = 60 * ((($r - $g) / $diff) + 4);
                    break;
                default:
                    $h = 0;
                    break;
            }
        }

        return ['h' => round($h, 2), 's' => round($s, 2), 'l' => round($l, 2)];
    }

    /**
     * Standardize color naming.
     */
    private static function normalizeColorName(string $name): string
    {
        $trimmed = trim(ucwords(strtolower($name)));
        foreach (self::PALETTE as $standard => $aliases) {
            if (strtolower($standard) === strtolower($trimmed)) {
                return $standard;
            }
            foreach ($aliases as $alias) {
                if (strtolower($alias) === strtolower($trimmed)) {
                    return $standard;
                }
            }
        }
        return $trimmed;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductColorDetectorService
{
    /**
     * Standard curated palette of Filipino fashion colors and their searchable aliases.
     * Note: Fabric materials like piña, organza, jusilyn are strictly EXCLUDED from color palettes.
     */
    public const PALETTE = [
        'White'         => ['white', 'pure white', 'snow white', 'off-white', 'cream', 'ivory', 'ecru', 'natural'],
        'Natural'       => ['natural', 'ecru', 'cream', 'beige', 'champagne', 'tan', 'off-white', 'white'],
        'Pink'          => ['blush', 'dusty rose', 'rose', 'pink', 'magenta', 'salmon', 'fuchsia'],
        'Maroon'        => ['maroon', 'burgundy', 'wine', 'bordeaux', 'magenta'],
        'Red'           => ['red', 'crimson', 'scarlet'],
        'Blue'          => ['blue', 'sky blue', 'light blue', 'dusty blue'],
        'Navy Blue'     => ['navy', 'navy blue', 'midnight blue'],
        'Royal Blue'    => ['royal blue', 'sapphire'],
        'Green'         => ['green', 'olive', 'forest green'],
        'Emerald Green' => ['emerald', 'emerald green', 'dark green'],
        'Sage Green'    => ['sage', 'sage green', 'mint'],
        'Black'         => ['black', 'charcoal', 'jet black', 'onyx'],
        'Gold'          => ['gold', 'golden', 'yellow', 'mustard'],
        'Purple'        => ['purple', 'violet', 'lavender', 'lilac', 'plum'],
        'Orange'        => ['orange', 'rust', 'terracotta', 'peach', 'coral'],
        'Multicolor'    => ['multicolor', 'multi-color', 'colorful', 'rainbow', 'two tone', 'two-tone', 'ombre'],
    ];

    /**
     * Related color synonyms for indexing and search cross-matching.
     */
    public const SYNONYM_EXPANSIONS = [
        'White'         => ['White', 'Natural', 'Cream', 'Off-White'],
        'Natural'       => ['Natural', 'White', 'Cream', 'Off-White', 'Ecru'],
        'Pink'          => ['Pink', 'Rose', 'Blush', 'Magenta'],
        'Maroon'        => ['Maroon', 'Burgundy', 'Wine'],
        'Red'           => ['Red', 'Crimson'],
        'Blue'          => ['Blue', 'Navy Blue', 'Royal Blue', 'Sky Blue', 'Dusty Blue'],
        'Navy Blue'     => ['Navy Blue', 'Navy', 'Blue'],
        'Royal Blue'    => ['Royal Blue', 'Blue'],
        'Green'         => ['Green', 'Sage Green', 'Emerald Green', 'Olive'],
        'Emerald Green' => ['Emerald Green', 'Green'],
        'Sage Green'    => ['Sage Green', 'Green', 'Mint'],
        'Black'         => ['Black', 'Charcoal'],
        'Gold'          => ['Gold', 'Yellow', 'Golden'],
        'Purple'        => ['Purple', 'Violet', 'Lavender'],
        'Orange'        => ['Orange', 'Peach', 'Terracotta'],
        'Multicolor'    => ['Multicolor', 'Two Tone', 'Ombre'],
    ];

    /**
     * Detect dominant garment color from image and/or title metadata.
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

        // 1. Check title for explicit high-intent color words (e.g. "Sage Green", "Two Tone", "Dusty Blue")
        $titleColor = self::detectFromTitle($name);

        // 2. Resolve image full path
        $fullPath = self::resolveImagePath($imagePath);

        // 3. Gemini Vision AI check (if configured and file exists)
        if ($fullPath && File::exists($fullPath)) {
            $geminiResult = self::detectWithGemini($fullPath);
            if ($geminiResult !== null) {
                return self::finalizeDetection($geminiResult, $titleColor);
            }

            // 4. Local GD Algorithm check (fast background-subtracted center sampling)
            $gdResult = self::detectWithGD($fullPath);
            if ($gdResult !== null) {
                return self::finalizeDetection($gdResult, $titleColor);
            }
        }

        // 5. If image was unavailable or unreadable, fall back to title color
        if ($titleColor !== null) {
            return self::finalizeDetection($titleColor, null);
        }

        // 6. Default heritage attire fallback (Classic White / Natural Piña tone)
        return [
            'primary_color' => 'White',
            'colors'        => ['White', 'Natural', 'Cream'],
            'source'        => 'default_heritage',
        ];
    }

    /**
     * Finalize detection result by expanding color synonyms and merging title nuances.
     */
    public static function finalizeDetection(array $detection, ?array $titleColor = null): array
    {
        $primary = $detection['primary_color'] ?? 'White';
        $colors = [$primary];

        // Only allow secondary color if garment fabric is genuinely dual-toned/ombre or specified
        if (!empty($detection['secondary_color']) && strtolower((string)$detection['secondary_color']) !== 'null') {
            $sec = self::normalizeColorName($detection['secondary_color']);
            if ($sec !== $primary && !in_array($sec, ['White', 'Natural'], true)) {
                $colors[] = $sec;
                $colors[] = 'Multicolor';
            }
        } elseif (!empty($detection['colors']) && count($detection['colors']) > 1) {
            // White and Natural garments strictly NEVER have secondary colors (embroidery thread must never pollute base cloth)
            if (!in_array($primary, ['White', 'Natural'], true)) {
                foreach ($detection['colors'] as $c) {
                    $norm = self::normalizeColorName($c);
                    if ($norm !== $primary && !in_array($norm, $colors, true)) {
                        $colors[] = $norm;
                    }
                }
            }
        }

        // If title explicitly mentions a color (e.g. "Two Tone", "Sage Green"), merge it
        if ($titleColor !== null) {
            $titlePrimary = $titleColor['primary_color'] ?? null;
            if ($titlePrimary) {
                if (in_array($primary, ['White', 'Natural'], true) && !in_array($titlePrimary, ['White', 'Natural'], true)) {
                    $primary = $titlePrimary;
                    $colors = [$primary];
                } elseif (!in_array($titlePrimary, $colors, true)) {
                    $colors[] = $titlePrimary;
                }
            }
        }

        // Automatically expand synonym search terms so customers searching "white", "natural", or "cream" find this product
        $expanded = $colors;
        foreach ($colors as $c) {
            if (isset(self::SYNONYM_EXPANSIONS[$c])) {
                foreach (self::SYNONYM_EXPANSIONS[$c] as $syn) {
                    if (!in_array($syn, $expanded, true)) {
                        $expanded[] = $syn;
                    }
                }
            }
        }

        // Include lowercase forms so MySQL/MariaDB utf8mb4_bin JSON columns match case-insensitively
        $indexedColors = [];
        foreach ($expanded as $c) {
            $indexedColors[] = $c;
            $lower = strtolower($c);
            if (!in_array($lower, $indexedColors, true)) {
                $indexedColors[] = $lower;
            }
        }

        return [
            'primary_color' => $primary,
            'colors'        => array_values(array_unique($indexedColors)),
            'source'        => $detection['source'] ?? 'algorithm',
        ];
    }

    /**
     * Detect explicit color words strictly from the product TITLE.
     * (We deliberately avoid matching description to prevent fabric material terms from triggering false colors).
     */
    public static function detectFromTitle(?string $name = null): ?array
    {
        if (empty($name)) {
            return null;
        }

        $haystack = mb_strtolower(trim($name), 'UTF-8');

        // Check compound colors first (e.g. 'Sage Green', 'Navy Blue', 'Royal Blue', 'Two Tone')
        foreach (self::PALETTE as $standardColor => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($alias, ' ') || str_contains($alias, '-')) {
                    if (preg_match('/\b' . preg_quote($alias, '/') . '\b/i', $haystack)) {
                        return [
                            'primary_color' => $standardColor,
                            'colors'        => [$standardColor],
                            'source'        => 'title_analysis',
                        ];
                    }
                }
            }
        }

        // Check single-word colors
        foreach (self::PALETTE as $standardColor => $aliases) {
            foreach ($aliases as $alias) {
                // Avoid matching short 3-letter false positives
                if (preg_match('/\b' . preg_quote($alias, '/') . '\b/i', $haystack)) {
                    return [
                        'primary_color' => $standardColor,
                        'colors'        => [$standardColor],
                        'source'        => 'title_analysis',
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Backward-compatible alias for text detection.
     */
    public static function detectFromText(?string $name = null, ?string $description = null): ?array
    {
        return self::detectFromTitle($name);
    }

    /**
     * Resolve image path to a readable local filesystem path.
     */
    public static function resolveImagePath(?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        if (File::exists($imagePath)) {
            return $imagePath;
        }

        // 1. Check storage/app/public
        $storagePath = storage_path('app/public/' . ltrim($imagePath, '/'));
        if (File::exists($storagePath)) {
            return $storagePath;
        }

        // 2. Check public/
        $publicPath = public_path(ltrim($imagePath, '/'));
        if (File::exists($publicPath)) {
            return $publicPath;
        }

        // 3. Check public/uploads/
        $publicUploads = public_path('uploads/' . ltrim($imagePath, '/'));
        if (File::exists($publicUploads)) {
            return $publicUploads;
        }

        // 4. Check public/storage/
        $storagePublic = public_path('storage/' . ltrim($imagePath, '/'));
        if (File::exists($storagePublic)) {
            return $storagePublic;
        }

        return null;
    }

    /**
     * Detect garment color using Google Gemini Vision AI.
     */
    public static function detectWithGemini(string $fullPath): ?array
    {
        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        if (!$apiKey) {
            return null;
        }

        try {
            $imageData = base64_encode(File::get($fullPath));
            $mimeType = File::mimeType($fullPath) ?: 'image/jpeg';

            $prompt = 'You are a master fashion attire color classifier for Philippine Barong Tagalog and Filipiniana e-commerce.

TASK:
Classify the DOMINANT BASE FABRIC COLOR of the garment piece being sold in the photograph.

CRITICAL RULES:
1. BASE CLOTH ONLY: Look strictly at the body textile/fabric of the barong shirt, polo, dress, or gown.
2. COMPLETELY IGNORE EMBROIDERY THREAD & BUTTONS: Traditional Barongs feature intricate dark or colored embroidery stitches (Calado, handpainted, or thread patterns). You MUST IGNORE embroidery thread colors, collar piping, buttons, and inner lining. For example:
   - A white, cream, or ecru Barong Tagalog with black, dark navy, or brown embroidery is 100% "White" (or "Natural"). Never classify it as Black or Blue.
   - A piña/natural Barong Tagalog with colored embroidery is 100% "Natural".
3. COMPLETELY IGNORE STUDIO WALLS & BACKDROPS:
   - Photographers often use solid blue, cyan, green, or gray backdrops. If a model or mannequin wearing a white, cream, or gray garment stands in front of a blue studio wall, the garment is WHITE or NATURAL, NOT BLUE.
   - Mannequins, model face, skin, hair, and trousers must be completely ignored.
4. TWO-TONE / OMBRÉ FABRICS:
   - ONLY return a secondary color if the GARMENT FABRIC ITSELF is genuinely dual-colored (e.g., ombré dip-dye fading from Pink/Maroon at the shoulders to Black at the hem).
   - If the garment fabric is a single uniform color, return null for secondary_color.
5. VINTAGE PRINTS & HISTORICAL DRAWINGS:
   - If the image is a drawing/illustration of historical attire (e.g., ancient barong / boxer codex), classify the dominant costume color as "Natural" or "White".

Output strictly valid JSON:
{
  "primary_color": "<one of: White, Natural, Black, Pink, Blue, Navy Blue, Royal Blue, Green, Sage Green, Emerald Green, Maroon, Red, Gold, Purple, Orange, Multicolor>",
  "secondary_color": null
}';

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
                    'temperature'     => 0.1,
                    'maxOutputTokens' => 150,
                ]
            ];

            $model = config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-2.5-flash');
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            // Allow sufficient timeout (12s) for image upload and vision analysis
            $response = Http::withOptions([
                'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
            ])->timeout(12.0)->post($url, $payload);

            if ($response->successful()) {
                $rawText = $response->json('candidates.0.content.parts.0.text');
                if ($rawText) {
                    $cleaned = preg_replace('/```json\s*|\s*```/', '', trim($rawText));
                    $data = json_decode($cleaned, true);
                    if (is_array($data) && !empty($data['primary_color'])) {
                        $pColor = self::normalizeColorName($data['primary_color']);
                        $secColor = (!empty($data['secondary_color']) && strtolower((string)$data['secondary_color']) !== 'null') 
                            ? self::normalizeColorName($data['secondary_color']) 
                            : null;

                        return [
                            'primary_color'   => $pColor,
                            'secondary_color' => $secColor,
                            'colors'          => $secColor ? [$pColor, $secColor] : [$pColor],
                            'source'          => 'gemini_vision',
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

        $info = @getimagesize($fullPath);
        if (!$info) {
            return null;
        }

        $img = null;
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

        // 2. Sample Central Torso / Garment Zone (28%-72% width, 22%-78% height)
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
                if ($hue >= 12 && $hue <= 38 && $sat >= 0.20 && $sat <= 0.65 && $lum >= 0.35 && $lum <= 0.80) {
                    continue;
                }

                // Filter 3: Check if pixel is head/hair
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

        // If multiple distinct colors share the garment (e.g. 2 strong clusters like Ombre/Two-tone)
        $keys = array_keys($colorVotes);
        if (count($keys) >= 2 && ($colorVotes[$keys[1]] / $totalVotes) * 100 >= 20) {
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
     * Classify an HSL pixel into a fashion color name with realistic garment thresholds.
     */
    private static function classifyHsl(float $h, float $s, float $l, int $r, int $g, int $b): ?string
    {
        // Black / Charcoal
        if ($l < 0.18) {
            return 'Black';
        }

        // High Lightness Neutrals (Classic White / Natural Barong)
        // Indoor photographs of white/ecru barongs on mannequins have L between 0.58 and 0.95 and S <= 0.25
        if ($l >= 0.58 && $s < 0.25) {
            // Warm ivory/beige/ecru tone
            if ($h >= 25 && $h <= 65 && $s >= 0.10) {
                return 'Natural';
            }
            return 'White';
        }

        // True Gray / Charcoal (medium lightness, very low saturation)
        if ($s < 0.10 && $l >= 0.18 && $l < 0.58) {
            return 'Black';
        }

        // Pink / Blush / Magenta / Rose: Hue 300 - 355
        if ($h >= 300 && $h <= 355 && $s >= 0.16 && $l >= 0.25) {
            return 'Pink';
        }

        // Red / Crimson: Hue 355 - 12 (high saturation)
        if (($h >= 355 || $h <= 12) && $s >= 0.35) {
            return $l < 0.35 ? 'Maroon' : 'Red';
        }

        // Maroon / Burgundy / Wine / Deep Magenta
        if (($h >= 335 || $h <= 18) && $l < 0.38 && $s >= 0.20) {
            return 'Maroon';
        }

        // Orange / Rust / Terracotta / Peach: Hue 12 - 42
        if ($h > 12 && $h <= 42 && $s >= 0.30) {
            return $l > 0.65 ? 'Natural' : 'Orange';
        }

        // Gold / Mustard / Yellow: Hue 42 - 65
        if ($h > 42 && $h <= 65 && $s >= 0.25) {
            return 'Gold';
        }

        // Green / Emerald / Sage: Hue 65 - 170
        if ($h > 65 && $h <= 170 && $s >= 0.16) {
            if ($l < 0.35) return 'Emerald Green';
            if ($s < 0.32) return 'Sage Green';
            return 'Green';
        }

        // Blue / Sky / Navy / Royal: Hue 170 - 265
        if ($h > 170 && $h <= 265 && $s >= 0.16) {
            if ($l < 0.28) return 'Navy Blue';
            if ($l > 0.65) return 'Blue';
            return 'Royal Blue';
        }

        // Purple / Violet: Hue 265 - 300
        if ($h > 265 && $h < 300 && $s >= 0.18) {
            return 'Purple';
        }

        // Fallback for light items
        if ($l >= 0.50) {
            return 'White';
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
    public static function normalizeColorName(string $name): string
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

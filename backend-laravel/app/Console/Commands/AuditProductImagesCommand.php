<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AuditProductImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:audit-images {--details : Show detailed list of flagged products}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely audit product image consistency, missing physical files, duplicate paths, and legacy formats.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Product Image Audit...');

        $products = Product::all();
        $totalProducts = $products->count();

        $stats = [
            'total'                 => $totalProducts,
            'no_images'             => 0,
            'single_image'          => 0,
            'multiple_images'       => 0,
            'malformed_json'        => 0,
            'duplicate_paths'       => 0,
            'legacy_uploads_paths'  => 0,
            'missing_physical_files'=> 0,
            'variation_missing_files'=> 0,
        ];

        $flaggedProducts = [];

        foreach ($products as $product) {
            $issues = [];
            $rawImage = $product->getRawOriginal('image');
            $images = [];

            // 1. Check JSON structure
            if (is_string($rawImage)) {
                $trimmed = trim($rawImage);
                if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                    $decoded = json_decode($trimmed, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $stats['malformed_json']++;
                        $issues[] = 'Malformed JSON in image field';
                    } else {
                        $images = is_array($decoded) ? $decoded : [$decoded];
                    }
                } elseif (!empty($trimmed)) {
                    $images = [$trimmed];
                }
            } elseif (is_array($rawImage)) {
                $images = $rawImage;
            }

            // Image count metrics
            $imgCount = count($images);
            if ($imgCount === 0) {
                $stats['no_images']++;
                $issues[] = 'No images stored';
            } elseif ($imgCount === 1) {
                $stats['single_image']++;
            } else {
                $stats['multiple_images']++;
            }

            // 2. Duplicate paths
            $cleanImages = array_filter(array_map('trim', $images));
            if (count($cleanImages) !== count(array_unique($cleanImages))) {
                $stats['duplicate_paths']++;
                $issues[] = 'Duplicate image paths in product.image';
            }

            // 3. Inspect individual image paths
            foreach ($images as $img) {
                if (!is_string($img) || empty(trim($img))) {
                    continue;
                }
                $img = trim($img);

                // Legacy upload path check
                if (str_starts_with($img, '/uploads/') || str_starts_with($img, 'uploads/')) {
                    $stats['legacy_uploads_paths']++;
                    $issues[] = "Legacy uploads path: {$img}";
                }

                // Check physical file existence
                if (!$this->fileExistsLocally($img)) {
                    $stats['missing_physical_files']++;
                    $issues[] = "Missing physical file: {$img}";
                }
            }

            // 4. Check variation images
            $rawVariations = $product->getRawOriginal('variations');
            $variations = [];
            if (is_string($rawVariations)) {
                $vDecoded = json_decode($rawVariations, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($vDecoded)) {
                    $variations = $vDecoded;
                }
            } elseif (is_array($rawVariations)) {
                $variations = $rawVariations;
            }

            foreach ($variations as $varIndex => $variation) {
                if (!is_array($variation)) {
                    continue;
                }
                $varImg = $variation['image'] ?? $variation['imageUrl'] ?? null;
                if (!empty($varImg) && is_string($varImg)) {
                    if (!$this->fileExistsLocally($varImg)) {
                        $stats['variation_missing_files']++;
                        $issues[] = "Variation [{$varIndex}] missing physical file: {$varImg}";
                    }
                }
            }

            if (!empty($issues)) {
                $flaggedProducts[] = [
                    'id'     => $product->id,
                    'name'   => $product->name,
                    'seller' => $product->sellerId,
                    'count'  => $imgCount,
                    'issues' => $issues,
                ];
            }
        }

        $this->newLine();
        $this->info('=== PRODUCT IMAGE AUDIT REPORT ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Products Audited', $stats['total']],
                ['Products with Multiple Images (Cover + Gallery)', $stats['multiple_images']],
                ['Products with Single Image (Cover only)', $stats['single_image']],
                ['Products with No Images', $stats['no_images']],
                ['Products with Duplicate Paths', $stats['duplicate_paths']],
                ['Products with Legacy /uploads/ Paths', $stats['legacy_uploads_paths']],
                ['Products with Malformed JSON', $stats['malformed_json']],
                ['Missing Product Physical Files', $stats['missing_physical_files']],
                ['Missing Variation Physical Files', $stats['variation_missing_files']],
            ]
        );

        if ($this->option('details') && !empty($flaggedProducts)) {
            $this->newLine();
            $this->warn('--- FLAGGED PRODUCTS DETAILS ---');
            foreach ($flaggedProducts as $fp) {
                $this->line("<fg=yellow>ID: {$fp['id']}</> | <fg=cyan>{$fp['name']}</> | Images: {$fp['count']}");
                foreach ($fp['issues'] as $issue) {
                    $this->line("   - {$issue}");
                }
            }
        } elseif (!empty($flaggedProducts)) {
            $this->info("Tip: Run with --details to view specific flagged product records.");
        }

        $this->info('Audit completed successfully (READ-ONLY, no files or database records modified).');
        return self::SUCCESS;
    }

    /**
     * Check if an image path exists locally on disk or public storage.
     */
    protected function fileExistsLocally(string $path): bool
    {
        // Ignore external URLs or default placeholders
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return true;
        }
        if (str_contains($path, 'default.jpg') || str_contains($path, 'placeholder')) {
            return true;
        }

        // Clean path
        $clean = ltrim($path, '/');

        // Check 1: public disk storage
        if (str_starts_with($clean, 'storage/')) {
            $storageRel = substr($clean, 8); // strip 'storage/'
            if (Storage::disk('public')->exists($storageRel)) {
                return true;
            }
        }

        if (Storage::disk('public')->exists($clean)) {
            return true;
        }

        // Check 2: public_path directly
        if (file_exists(public_path($clean))) {
            return true;
        }

        // Check 3: storage_path app/public
        if (file_exists(storage_path('app/public/' . $clean))) {
            return true;
        }

        return false;
    }
}

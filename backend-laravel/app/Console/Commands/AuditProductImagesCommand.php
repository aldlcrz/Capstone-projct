<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuditProductImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:audit-images 
                            {--details : Show detailed list of flagged products}
                            {--json : Output the audit report as machine-readable JSON}
                            {--fail-on-missing : Exit with code 1 if missing files or errors are detected}';

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
        $isJson = (bool) $this->option('json');
        $showDetails = (bool) $this->option('details');
        $failOnMissing = (bool) $this->option('fail-on-missing');

        if (!$isJson) {
            $this->info('Starting Product Image Audit...');
        }

        $products = Product::all();
        $totalProducts = $products->count();

        $stats = [
            'total'                  => $totalProducts,
            'no_images'              => 0,
            'single_image'           => 0,
            'multiple_images'        => 0,
            'malformed_json'         => 0,
            'duplicate_paths'        => 0,
            'canonical_storage_paths'=> 0,
            'legacy_uploads_paths'   => 0,
            'remote_urls'            => 0,
            'invalid_paths'          => 0,
            'missing_physical_files' => 0,
            'variation_missing_files'=> 0,
        ];

        $flaggedProducts = [];

        foreach ($products as $product) {
            $issues = [];
            $rawImage = $product->getRawOriginal('image');
            $images = [];

            // 1. Check JSON / Array structure
            if (is_string($rawImage)) {
                $trimmed = trim($rawImage);
                if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                    $decoded = json_decode($trimmed, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $stats['malformed_json']++;
                        $issues[] = [
                            'role' => 'image_field',
                            'path' => $trimmed,
                            'type' => 'malformed_json',
                            'message' => 'Malformed JSON in product.image attribute',
                        ];
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
                $issues[] = [
                    'role' => 'cover',
                    'path' => null,
                    'type' => 'no_images',
                    'message' => 'No product images stored',
                ];
            } elseif ($imgCount === 1) {
                $stats['single_image']++;
            } else {
                $stats['multiple_images']++;
            }

            // 2. Duplicate paths check
            $cleanImages = array_filter(array_map('trim', array_filter($images, 'is_string')));
            if (count($cleanImages) !== count(array_unique($cleanImages))) {
                $stats['duplicate_paths']++;
                $issues[] = [
                    'role' => 'gallery',
                    'path' => implode(', ', $cleanImages),
                    'type' => 'duplicate_paths',
                    'message' => 'Duplicate image paths in product.image',
                ];
            }

            // 3. Inspect individual product images (Cover vs Gallery)
            foreach ($images as $imgIndex => $img) {
                $role = ($imgIndex === 0) ? 'cover' : "gallery_{$imgIndex}";

                if (!is_string($img) || empty(trim($img))) {
                    $stats['invalid_paths']++;
                    $issues[] = [
                        'role' => $role,
                        'path' => (string)$img,
                        'type' => 'invalid_path',
                        'message' => "Empty or non-string image reference at index [{$imgIndex}]",
                    ];
                    continue;
                }

                $img = trim($img);
                $classification = $this->classifyPath($img);

                switch ($classification['type']) {
                    case 'remote_url':
                        $stats['remote_urls']++;
                        break;
                    case 'placeholder':
                        // Fallback or placeholder reference
                        break;
                    case 'legacy_uploads':
                        $stats['legacy_uploads_paths']++;
                        break;
                    case 'canonical_storage':
                        $stats['canonical_storage_paths']++;
                        break;
                    default:
                        $stats['invalid_paths']++;
                        $issues[] = [
                            'role' => $role,
                            'path' => $img,
                            'type' => 'unsupported_path',
                            'message' => "Unsupported or unrecognized path format: {$img}",
                        ];
                        break;
                }

                // Check physical file existence for local paths
                if ($classification['type'] !== 'remote_url' && $classification['type'] !== 'placeholder') {
                    if (!$this->fileExistsLocally($img)) {
                        $stats['missing_physical_files']++;
                        $issues[] = [
                            'role' => $role,
                            'path' => $img,
                            'type' => 'missing_physical_file',
                            'storage_type' => $classification['type'],
                            'message' => "Missing physical file for {$role}: {$img}",
                        ];
                    }
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
                $varName = $variation['name'] ?? "Variation {$varIndex}";
                $varImg = $variation['image'] ?? $variation['imageUrl'] ?? null;

                if (!empty($varImg) && is_string($varImg)) {
                    $classification = $this->classifyPath($varImg);
                    if ($classification['type'] !== 'remote_url' && $classification['type'] !== 'placeholder') {
                        if (!$this->fileExistsLocally($varImg)) {
                            $stats['variation_missing_files']++;
                            $issues[] = [
                                'role' => "variation_{$varIndex}",
                                'variant_name' => $varName,
                                'path' => $varImg,
                                'type' => 'variation_missing_file',
                                'storage_type' => $classification['type'],
                                'message' => "Variation [{$varName}] missing physical file: {$varImg}",
                            ];
                        }
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

        // Log administrative warning if any files are missing
        if ($stats['missing_physical_files'] > 0 || $stats['variation_missing_files'] > 0) {
            Log::warning('[ImageAudit] Flagged products with missing or unresolvable physical image files', [
                'missing_products'   => $stats['missing_physical_files'],
                'missing_variations' => $stats['variation_missing_files'],
                'flagged_count'      => count($flaggedProducts),
            ]);
        }

        if ($isJson) {
            $this->output->write(json_encode([
                'success' => ($stats['missing_physical_files'] === 0 && $stats['variation_missing_files'] === 0),
                'stats'   => $stats,
                'flagged' => $flaggedProducts,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->output->newLine();
        } else {
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
                    ['Canonical Storage Paths (storage/app/public)', $stats['canonical_storage_paths']],
                    ['Legacy /uploads/ Paths', $stats['legacy_uploads_paths']],
                    ['Remote URLs (http/https)', $stats['remote_urls']],
                    ['Invalid / Malformed Paths', $stats['invalid_paths']],
                    ['Missing Product Physical Files', $stats['missing_physical_files']],
                    ['Missing Variation Physical Files', $stats['variation_missing_files']],
                ]
            );

            if ($showDetails && !empty($flaggedProducts)) {
                $this->newLine();
                $this->warn('--- FLAGGED PRODUCTS DETAILS ---');
                foreach ($flaggedProducts as $fp) {
                    $this->line("<fg=yellow>ID: {$fp['id']}</> | <fg=cyan>{$fp['name']}</> | Seller: {$fp['seller']} | Stored: {$fp['count']}");
                    foreach ($fp['issues'] as $issue) {
                        $roleTag = isset($issue['role']) ? "<fg=gray>[{$issue['role']}]</> " : '';
                        $this->line("   - {$roleTag}{$issue['message']}");
                    }
                }
            } elseif (!empty($flaggedProducts)) {
                $this->info("Tip: Run with --details to view specific flagged product records.");
            }

            $this->info('Audit completed successfully (READ-ONLY, no files or database records modified).');
        }

        if ($failOnMissing && ($stats['missing_physical_files'] > 0 || $stats['variation_missing_files'] > 0)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Classify an image path into its architectural storage category.
     *
     * @param string $path
     * @return array{type: string, clean: string}
     */
    public function classifyPath(string $path): array
    {
        $clean = ltrim(str_replace('\\', '/', trim($path)), '/');

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return ['type' => 'remote_url', 'clean' => $path];
        }

        if (str_contains($clean, 'default.jpg') || str_contains($clean, 'placeholder')) {
            return ['type' => 'placeholder', 'clean' => $clean];
        }

        if (str_starts_with($clean, 'products/') || str_starts_with($clean, 'storage/products/') || str_starts_with($clean, 'payments/')) {
            return ['type' => 'canonical_storage', 'clean' => $clean];
        }

        if (str_starts_with($clean, 'uploads/')) {
            return ['type' => 'legacy_uploads', 'clean' => $clean];
        }

        return ['type' => 'unrecognized', 'clean' => $clean];
    }

    /**
     * Check if an image path exists locally on disk or public storage.
     * Uses Laravel Storage APIs for storage disk and public_path() for legacy assets.
     */
    public function fileExistsLocally(string $path): bool
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return true;
        }
        if (str_contains($path, 'default.jpg') || str_contains($path, 'placeholder')) {
            return true;
        }

        $clean = ltrim(str_replace('\\', '/', trim($path)), '/');

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

        // Check 2: public_path directly for legacy uploads
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

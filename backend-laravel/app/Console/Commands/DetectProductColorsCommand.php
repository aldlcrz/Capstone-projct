<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductColorDetectorService;
use Illuminate\Console\Command;

class DetectProductColorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:detect-colors 
                            {--force : Force re-detection even if a product already has a color assigned}
                            {--product-id= : Run detection on a specific product ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically detect and populate garment colors for products without seller manual effort';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $productId = $this->option('product-id');

        $query = Product::query();

        if ($productId) {
            $query->where('id', $productId);
        } elseif (!$force) {
            $query->where(function ($q) {
                $q->whereNull('color')
                  ->orWhere('color', '');
            });
        }

        $total = $query->count();
        if ($total === 0) {
            $this->info('No products found matching the criteria.');
            return Command::SUCCESS;
        }

        $this->info("Starting color detection for {$total} product(s)...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updatedCount = 0;

        $query->chunk(50, function ($products) use (&$updatedCount, $bar) {
            foreach ($products as $product) {
                // Primary image path
                $imagePath = $product->image ?? null;
                if (is_array($imagePath)) {
                    $imagePath = reset($imagePath);
                }

                // Detect color
                $detection = ProductColorDetectorService::detect(
                    $imagePath,
                    $product->name,
                    $product->description
                );

                $detectedColor = $detection['primary_color'] ?? 'White';
                $allColors = $detection['colors'] ?? [$detectedColor];

                $product->color = $detectedColor;
                $product->colors = $allColors;
                $product->saveQuietly();

                $updatedCount++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Successfully updated colors for {$updatedCount} product(s).");

        return Command::SUCCESS;
    }
}

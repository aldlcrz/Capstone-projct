<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductImageIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected User $admin;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'approved',
            'isVerified' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'approved',
            'isVerified' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Barong Tagalog',
            'target_group' => ['Men'],
            'description' => 'Lumban barongs',
        ]);
    }

    /**
     * Test audit classifies canonical and valid legacy images properly.
     */
    public function test_audit_command_detects_canonical_images_and_valid_legacy_images(): void
    {
        // 1. Create a canonical image in fake public disk
        Storage::disk('public')->put('products/cover/canonical_barong.jpg', 'fake-image-binary-data');

        // 2. Create a legacy image in public/uploads/products
        $legacyDir = public_path('uploads/products');
        if (!File::isDirectory($legacyDir)) {
            File::makeDirectory($legacyDir, 0755, true);
        }
        $legacyFilePath = $legacyDir . '/test_legacy_barong.jpg';
        File::put($legacyFilePath, 'fake-legacy-image-content');

        try {
            $pCanonical = Product::create([
                'id' => (string) Str::uuid(),
                'sellerId' => $this->seller->id,
                'name' => 'Canonical Barong',
                'description' => 'Product with canonical image storage path',
                'fabric_type' => 'Piña',
                'price' => 2500,
                'CategoryId' => $this->category->id,
                'categories' => [$this->category->id],
                'target_group' => 'Men',
                'sizes' => ['M'],
                'stock' => 10,
                'status' => 'approved',
                'image' => ['products/cover/canonical_barong.jpg'],
            ]);

            $pLegacy = Product::create([
                'id' => (string) Str::uuid(),
                'sellerId' => $this->seller->id,
                'name' => 'Legacy Barong',
                'description' => 'Product with valid legacy public upload path',
                'fabric_type' => 'Jusi',
                'price' => 1800,
                'CategoryId' => $this->category->id,
                'categories' => [$this->category->id],
                'target_group' => 'Men',
                'sizes' => ['L'],
                'stock' => 5,
                'status' => 'approved',
                'image' => ['uploads/products/test_legacy_barong.jpg'],
            ]);

            $exitCode = \Illuminate\Support\Facades\Artisan::call('products:audit-images', ['--details' => true]);
            $output = \Illuminate\Support\Facades\Artisan::output();
            $this->assertEquals(0, $exitCode);
            $this->assertStringContainsString('Total Products Audited', $output);
            $this->assertStringContainsString('Canonical Storage Paths (storage/app/public)', $output);
            $this->assertStringContainsString('Legacy /uploads/ Paths', $output);

        } finally {
            if (File::exists($legacyFilePath)) {
                File::delete($legacyFilePath);
            }
        }
    }

    /**
     * Test audit identifies missing cover and gallery images without throwing unhandled exceptions.
     */
    public function test_audit_command_detects_missing_cover_and_gallery_images(): void
    {
        $missingProduct = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Barong With Missing Files',
            'description' => 'Product with missing cover and missing gallery images',
            'fabric_type' => 'Piña',
            'price' => 3200,
            'CategoryId' => $this->category->id,
            'categories' => [$this->category->id],
            'target_group' => 'Men',
            'sizes' => ['M'],
            'stock' => 4,
            'status' => 'approved',
            'image' => [
                'products/cover/non_existent_cover.jpg',
                'products/gallery/non_existent_gallery.jpg',
            ],
        ]);

        $exitCode = \Illuminate\Support\Facades\Artisan::call('products:audit-images', ['--details' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Missing physical file', $output);
        $this->assertStringContainsString('non_existent_cover.jpg', $output);
        $this->assertStringContainsString('non_existent_gallery.jpg', $output);
    }

    /**
     * Test machine-readable JSON output and --fail-on-missing exit code.
     */
    public function test_audit_json_output_and_failure_exit_status(): void
    {
        // When there is a missing file and --fail-on-missing is passed, exit code should be 1
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Missing Audit Product',
            'description' => 'Product with missing file to test fail-on-missing',
            'fabric_type' => 'Piña',
            'price' => 1500,
            'CategoryId' => $this->category->id,
            'categories' => [$this->category->id],
            'target_group' => 'Men',
            'sizes' => ['M'],
            'stock' => 2,
            'status' => 'approved',
            'image' => ['uploads/products/definitely_missing_12345.jpg'],
        ]);

        $failExitCode = \Illuminate\Support\Facades\Artisan::call('products:audit-images', ['--fail-on-missing' => true]);
        $this->assertEquals(1, $failExitCode);

        // Test that --json produces valid JSON output
        $exitCode = \Illuminate\Support\Facades\Artisan::call('products:audit-images', ['--json' => true]);
        $jsonOutput = \Illuminate\Support\Facades\Artisan::output();
        $this->assertEquals(0, $exitCode);
        $decoded = json_decode($jsonOutput, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('success', $decoded);
        $this->assertArrayHasKey('stats', $decoded);
        $this->assertArrayHasKey('flagged', $decoded);
        $this->assertArrayHasKey('total', $decoded['stats']);
        $this->assertArrayHasKey('missing_physical_files', $decoded['stats']);
    }

    /**
     * Test audit is strictly read-only and does not mutate database records.
     */
    public function test_audit_is_strictly_read_only(): void
    {
        $originalImages = ['uploads/products/missing_reference_preserve_me.jpg'];
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'BARONG PIPING TEST',
            'description' => 'Read only preservation test',
            'fabric_type' => 'Piña Organza',
            'price' => 4200,
            'CategoryId' => $this->category->id,
            'categories' => [$this->category->id],
            'target_group' => 'Men',
            'sizes' => ['S', 'M'],
            'stock' => 8,
            'status' => 'approved',
            'image' => $originalImages,
        ]);

        $exitCode = \Illuminate\Support\Facades\Artisan::call('products:audit-images', ['--details' => true]);
        $this->assertEquals(0, $exitCode);

        $fresh = Product::find($product->id);
        $this->assertEquals($originalImages, $fresh->image);
        $this->assertEquals('BARONG PIPING TEST', $fresh->name);
        $this->assertEquals(8, $fresh->stock);
    }

    /**
     * Test model hasMissingCoverImage and fallback to neutral placeholder.
     */
    public function test_product_model_cover_missing_detection_and_placeholder_fallback(): void
    {
        // 1. Existing file
        Storage::disk('public')->put('products/cover/valid_cover.jpg', 'binary-data');

        $validProduct = new Product([
            'image' => ['products/cover/valid_cover.jpg'],
        ]);
        $this->assertFalse($validProduct->hasMissingCoverImage());

        // 2. Missing file
        $missingProduct = new Product([
            'image' => ['products/cover/does_not_exist_999.jpg'],
        ]);
        $this->assertTrue($missingProduct->hasMissingCoverImage());

        // 3. Fallback URL returns neutral placeholder SVG
        $this->assertEquals('/images/placeholder-product.svg', Product::getPlaceholderImageUrl());
        $this->assertEquals('/images/placeholder-product.svg', (new Product(['image' => null]))->getImageUrl());
        $this->assertEquals('/images/placeholder-product.svg', (new Product(['image' => ['products/default.jpg']]))->getImageUrl());
        $this->assertEquals('/images/placeholder-product.svg', (new Product(['image' => ['uploads/products/default.jpg']]))->getImageUrl());
    }

    /**
     * Test transactional rollback on failed store cleans up uploaded files.
     */
    public function test_failed_product_store_cleans_up_newly_uploaded_files(): void
    {
        $coverFile = UploadedFile::fake()->image('cover_test.jpg', 600, 600);

        // Force DB error by causing a duplicate UUID or mock failure during save
        $existingId = (string) Str::uuid();
        Product::create([
            'id' => $existingId,
            'sellerId' => $this->seller->id,
            'name' => 'Existing Colliding Product',
            'description' => 'Pre-existing product',
            'fabric_type' => 'Piña',
            'price' => 1000,
            'CategoryId' => $this->category->id,
            'categories' => [$this->category->id],
            'target_group' => 'Men',
            'sizes' => ['M'],
            'stock' => 1,
            'status' => 'approved',
            'image' => ['products/cover/valid.jpg'],
        ]);

        Str::createUuidsUsing(fn() => $existingId);

        try {
            $this->actingAs($this->seller)->post(route('seller.products.store'), [
                'action' => 'publish',
                'name' => 'Will Fail Due To Collision',
                'description' => 'Testing file cleanup on store failure',
                'fabric_type' => 'Piña',
                'price' => 2000,
                'CategoryId' => $this->category->id,
                'category_ids' => [$this->category->id],
                'target_group' => 'Men',
                'sizes' => ['M'],
                'size_stocks' => ['M' => 2],
                'variant_names' => [0 => 'Default Style'],
                'variant_indexes' => [0],
                'variant_image_0' => $coverFile,
            ]);
        } catch (\Throwable $e) {
            // Expected database collision exception
        } finally {
            Str::createUuidsNormally();
        }

        // Verify that no orphaned files were leaked in storage
        $files = Storage::disk('public')->allFiles('products/cover');
        // Any newly written cover during the failed store must have been deleted
        $this->assertEmpty($files, 'Newly uploaded files must be cleaned up on transaction rollback.');
    }

    /**
     * Test that updating a product does not delete previous images if database transaction fails.
     */
    public function test_product_update_does_not_prematurely_delete_images_if_db_save_fails(): void
    {
        Storage::disk('public')->put('products/cover/original_valid_cover.jpg', 'original-content');

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Original Barong Product',
            'description' => 'Testing deferred image unlinking on update',
            'fabric_type' => 'Piña',
            'price' => 3000,
            'CategoryId' => $this->category->id,
            'categories' => [$this->category->id],
            'target_group' => 'Men',
            'sizes' => ['M'],
            'size_stocks' => ['M' => 5],
            'stock' => 5,
            'status' => 'approved',
            'image' => ['products/cover/original_valid_cover.jpg'],
            'variations' => [
                ['name' => 'Main Style', 'image' => 'products/cover/original_valid_cover.jpg', 'images' => ['products/cover/original_valid_cover.jpg']]
            ],
        ]);

        $this->assertTrue(Storage::disk('public')->exists('products/cover/original_valid_cover.jpg'));

        // Attempt an update with an invalid price to cause validation failure before commit,
        // or trigger an exception
        $response = $this->actingAs($this->seller)->put(route('seller.products.update', $product->id), [
            'name' => 'Updated Barong Product',
            'description' => 'Valid updated description',
            'price' => -50, // Invalid price (< 1.00)
            'CategoryId' => $this->category->id,
            'target_group' => 'Men',
            'sizes' => ['M'],
            'size_stocks' => ['M' => 5],
            'package_weight_per_unit' => 0.5,
            'handling_days' => 2,
            'remove_images' => ['products/cover/original_valid_cover.jpg'],
        ]);

        // The original image MUST still exist in storage because the update did not commit!
        $this->assertTrue(
            Storage::disk('public')->exists('products/cover/original_valid_cover.jpg'),
            'Original valid image must not be deleted if update fails or is rejected.'
        );
    }

    /**
     * Test authorized seller/admin sees missing image warning in UI.
     */
    public function test_seller_sees_missing_image_warning_in_management_views(): void
    {
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Barong Piping Broken File',
            'description' => 'Product missing image file test',
            'fabric_type' => 'Piña',
            'price' => 2200,
            'CategoryId' => $this->category->id,
            'categories' => [$this->category->id],
            'target_group' => 'Men',
            'sizes' => ['M'],
            'stock' => 5,
            'status' => 'approved',
            'image' => ['uploads/products/missing_photo_file.jpg'],
        ]);

        // 1. Seller product management index view
        $response = $this->actingAs($this->seller)->get(route('seller.products.index'));
        $response->assertStatus(200);
        $response->assertSee('Photo Missing');

        // 2. Product show page for the product owner
        $response = $this->actingAs($this->seller)->get('/products/' . $product->id);
        $response->assertStatus(200);
        $response->assertSee('Product Photograph Missing');
        $response->assertSee('Upload Replacement');
        $response->assertSee('/images/placeholder-product.svg');
    }
}

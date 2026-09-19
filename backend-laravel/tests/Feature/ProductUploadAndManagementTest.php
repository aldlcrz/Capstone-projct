<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductUploadAndManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'approved',
            'isVerified' => true,
            'gcashNumber' => '09171234567',
            'gcashQrCode' => 'uploads/qrcodes/seller_default_qr.png',
        ]);

        $this->category = Category::create([
            'name' => 'Formal Barong',
            'target_group' => ['Men'],
            'description' => 'Traditional formal Lumban barongs',
        ]);
    }

    /**
     * Test publishing a product with cover, variant, gallery, QR, and size guide images,
     * verifying canonical storage paths and URLs.
     */
    public function test_seller_can_publish_product_with_canonical_storage_paths(): void
    {
        $coverFile = UploadedFile::fake()->image('cover_barong.jpg', 1200, 1200);
        $variantFile = UploadedFile::fake()->image('variant_jusi.png', 800, 800);
        $galleryFile = UploadedFile::fake()->image('detail_embroidery.webp', 1000, 1000);
        $qrFile = UploadedFile::fake()->image('product_gcash_qr.jpg', 600, 600);
        $sizeGuideFile = UploadedFile::fake()->image('custom_size_guide.png', 800, 1000);

        DB::enableQueryLog();

        $response = $this->actingAs($this->seller)->post(route('seller.products.store'), [
            'action' => 'publish',
            'name' => 'Handcrafted Calado Barong Tagalog',
            'description' => 'Exquisitely embroidered Lumban artisan barong tagalog made of pure Piña.',
            'fabric_type' => '100% Piña',
            'price' => 4500,
            'shippingFee' => 150,
            'shippingDays' => 5,
            'CategoryId' => $this->category->id,
            'category_ids' => [$this->category->id],
            'target_group' => 'Men',
            'sizes' => ['M', 'L'],
            'size_stocks' => ['M' => 5, 'L' => 8],
            'product_is_gcash_available' => '1',
            'gcashNumber' => '09179876543',
            'variant_names' => [0 => 'Natural Ecru', 1 => 'Ivory White'],
            'variant_indexes' => [0, 1],
            'variant_image_0' => $coverFile,
            'variant_image_1' => $variantFile,
            'images' => [$galleryFile],
            'gcashQrCode' => $qrFile,
            'size_guide_image' => $sizeGuideFile,
        ]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertRedirect(route('seller.products.index'));
        $response->assertSessionHas('success');

        // Verify NO runtime ALTER TABLE statements were executed
        foreach ($queries as $query) {
            $this->assertStringNotContainsStringIgnoringCase('ALTER TABLE', $query['query'], 'Runtime ALTER TABLE DDL must not be executed in controller request.');
        }

        $product = Product::where('sellerId', $this->seller->id)->first();
        $this->assertNotNull($product, 'Product should be successfully created.');
        $this->assertEquals('pending', $product->status);
        $this->assertEquals(13, $product->stock);

        // Verify canonical file storage paths
        $this->assertNotNull($product->gcash_qr_code);
        $this->assertStringStartsWith('payments/qrcodes/', $product->gcash_qr_code);
        Storage::disk('public')->assertExists($product->gcash_qr_code);

        $this->assertNotNull($product->size_guide_image);
        $this->assertStringStartsWith('products/sizeguides/', $product->size_guide_image);
        Storage::disk('public')->assertExists($product->size_guide_image);

        // Verify product images array
        $images = is_array($product->image) ? $product->image : json_decode($product->image, true);
        $this->assertNotEmpty($images);
        $this->assertStringStartsWith('products/cover/', $images[0]);
        Storage::disk('public')->assertExists($images[0]);

        // Verify variants
        $variations = is_array($product->variations) ? $product->variations : json_decode($product->variations, true);
        $this->assertCount(2, $variations);
        $this->assertEquals('Natural Ecru', $variations[0]['name']);
        $this->assertStringStartsWith('products/cover/', $variations[0]['image']);
        $this->assertEquals('Ivory White', $variations[1]['name']);
        $this->assertStringStartsWith('products/variants/', $variations[1]['image']);
        Storage::disk('public')->assertExists($variations[1]['image']);

        // Verify URL accessors resolve to canonical /storage/... paths
        $this->assertEquals('/storage/' . $images[0], $product->getImageUrl());
        $this->assertEquals('/storage/' . $product->gcash_qr_code, $product->getGcashQrUrl());
        $this->assertEquals('/storage/' . $product->size_guide_image, $product->getSizeGuideUrl());

        $allUrls = $product->getAllImageUrls();
        $this->assertGreaterThanOrEqual(2, count($allUrls));
        foreach ($allUrls as $url) {
            $this->assertStringStartsWith('/storage/', $url);
        }
    }

    /**
     * Test saving product as a draft with minimal data and without images.
     */
    public function test_seller_can_save_minimal_product_as_draft(): void
    {
        $response = $this->actingAs($this->seller)->post(route('seller.products.store'), [
            'action' => 'draft',
            'name' => 'Work-in-Progress Heritage Gown',
        ]);

        $response->assertRedirect(route('seller.products.index'));
        $response->assertSessionHas('success');

        $product = Product::where('name', 'Work-in-Progress Heritage Gown')->first();
        $this->assertNotNull($product);
        $this->assertEquals('draft', $product->status);
    }

    /**
     * Test that server-side validation rejects invalid image MIME types.
     */
    public function test_server_side_validation_rejects_invalid_mime_types(): void
    {
        $fakeScript = UploadedFile::fake()->create('malicious.php', 10, 'text/x-php');

        $response = $this->actingAs($this->seller)->post(route('seller.products.store'), [
            'action' => 'publish',
            'name' => 'Test Barong Tagalog',
            'description' => 'Authentic handmade Barong Tagalog embroidery from Lumban Laguna.',
            'price' => 3000,
            'shippingFee' => 100,
            'shippingDays' => 3,
            'CategoryId' => $this->category->id,
            'category_ids' => [$this->category->id],
            'target_group' => 'Men',
            'sizes' => ['M'],
            'size_stocks' => ['M' => 5],
            'product_is_gcash_available' => '1',
            'gcashNumber' => '09171234567',
            'variant_indexes' => [0],
            'variant_names' => [0 => 'Default Variant'],
            'variant_image_0' => $fakeScript,
        ]);

        $response->assertSessionHasErrors(['variant_image_0']);
        $this->assertDatabaseMissing('products', ['name' => 'Test Barong Tagalog']);
    }

    /**
     * Test that server-side validation rejects files exceeding 5MB.
     */
    public function test_server_side_validation_rejects_oversized_file(): void
    {
        // 6MB image (exceeds max:5120 KB limit)
        $oversizedFile = UploadedFile::fake()->create('giant_photo.jpg', 6144, 'image/jpeg');

        $response = $this->actingAs($this->seller)->post(route('seller.products.store'), [
            'action' => 'publish',
            'name' => 'Test Barong Tagalog',
            'description' => 'Authentic handmade Barong Tagalog embroidery from Lumban Laguna.',
            'price' => 3000,
            'shippingFee' => 100,
            'shippingDays' => 3,
            'CategoryId' => $this->category->id,
            'category_ids' => [$this->category->id],
            'target_group' => 'Men',
            'sizes' => ['M'],
            'size_stocks' => ['M' => 5],
            'product_is_gcash_available' => '1',
            'gcashNumber' => '09171234567',
            'variant_indexes' => [0],
            'variant_names' => [0 => 'Default Variant'],
            'variant_image_0' => $oversizedFile,
        ]);

        $response->assertSessionHasErrors(['variant_image_0']);
    }

    /**
     * Test that a database transaction rollback deletes any files saved during the failed request.
     */
    public function test_failed_persistence_cleans_up_orphaned_files(): void
    {
        $coverFile = UploadedFile::fake()->image('rollback_cover.jpg', 1000, 1000);

        // Intentionally trigger a DB error during Product::saving or save by listening to event
        Product::saving(function ($product) {
            if ($product->name === 'Trigger Rollback Error Product') {
                throw new \RuntimeException('Simulated database write deadlock or crash.');
            }
        });

        $response = $this->actingAs($this->seller)->post(route('seller.products.store'), [
            'action' => 'publish',
            'name' => 'Trigger Rollback Error Product',
            'description' => 'This product will intentionally trigger a persistence failure.',
            'price' => 2000,
            'shippingFee' => 100,
            'shippingDays' => 3,
            'CategoryId' => $this->category->id,
            'category_ids' => [$this->category->id],
            'target_group' => 'Men',
            'sizes' => ['M'],
            'size_stocks' => ['M' => 5],
            'product_is_gcash_available' => '1',
            'gcashNumber' => '09171234567',
            'variant_indexes' => [0],
            'variant_names' => [0 => 'Default Variant'],
            'variant_image_0' => $coverFile,
        ]);

        $response->assertSessionHas('error');

        // Verify no orphan files remain in public storage
        $files = Storage::disk('public')->allFiles('products/cover');
        $this->assertEmpty($files, 'All uploaded files must be deleted if persistence fails.');
    }

    /**
     * Test backward compatibility of legacy image paths and default fallback.
     */
    public function test_legacy_product_image_paths_resolve_seamlessly(): void
    {
        $legacyProduct = Product::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Legacy Heritage Piece',
            'description' => 'Product created with old uploads/ path schema.',
            'price' => 3500,
            'stock' => 10,
            'CategoryId' => $this->category->id,
            'status' => 'approved',
            'image' => ['uploads/products/vintage_barong_1990.jpg'],
            'gcash_qr_code' => 'uploads/qrcodes/legacy_gcash_qr.png',
            'size_guide_image' => 'uploads/sizeguides/legacy_guide.png',
        ]);

        // Returns explicit uploads/ path for legacy data
        $this->assertEquals('/uploads/products/vintage_barong_1990.jpg', $legacyProduct->getImageUrl());
        $this->assertEquals('/uploads/qrcodes/legacy_gcash_qr.png', $legacyProduct->getGcashQrUrl());
        $this->assertEquals('/uploads/sizeguides/legacy_guide.png', $legacyProduct->getSizeGuideUrl());

        // Default placeholder for empty images
        $emptyProduct = new Product();
        $this->assertEquals('/uploads/products/default.jpg', $emptyProduct->getImageUrl());
    }
}

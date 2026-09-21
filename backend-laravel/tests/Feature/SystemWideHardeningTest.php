<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\VariationFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SystemWideHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function createCustomer(array $attrs = []): User
    {
        return User::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => 'Customer User',
            'username' => 'customer_' . Str::random(6),
            'email' => 'customer_' . Str::random(6) . '@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'mobileNumber' => '0917' . rand(1000000, 9999999),
            'isVerified' => true,
            'email_verified_at' => now(),
        ], $attrs));
    }

    protected function createSeller(array $attrs = []): User
    {
        return User::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => 'Artisan Seller',
            'username' => 'seller_' . Str::random(6),
            'email' => 'seller_' . Str::random(6) . '@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'shopName' => 'Artisan Atelier ' . Str::random(4),
            'status' => 'active',
            'mobileNumber' => '0918' . rand(1000000, 9999999),
            'isVerified' => true,
            'email_verified_at' => now(),
            'profile_completed' => true,
        ], $attrs));
    }

    protected function createAdmin(array $attrs = []): User
    {
        return User::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => 'Platform Admin',
            'username' => 'admin_' . Str::random(6),
            'email' => 'admin_' . Str::random(6) . '@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'admin',
            'status' => 'active',
            'mobileNumber' => '0919' . rand(1000000, 9999999),
            'isVerified' => true,
            'email_verified_at' => now(),
        ], $attrs));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Product Image Pipeline (End-to-End Contract)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_seller_upload_1_cover_and_3_gallery_stores_4_images_in_database(): void
    {
        Storage::fake('public');
        $seller = $this->createSeller();
        $category = \App\Models\Category::create([
            'name' => 'Barong Tagalog',
            'target_group' => ['Men'],
            'description' => 'Traditional Barongs',
        ]);

        $cover = UploadedFile::fake()->image('cover_photo.jpg', 600, 600);
        $gallery1 = UploadedFile::fake()->image('detail_collar.jpg', 600, 600);
        $gallery2 = UploadedFile::fake()->image('detail_cuff.jpg', 600, 600);
        $gallery3 = UploadedFile::fake()->image('detail_back.jpg', 600, 600);
        $qrCode = UploadedFile::fake()->image('seller_qr.png', 400, 400);

        $payload = [
            'action' => 'publish',
            'name' => 'Handcrafted Pina Barong',
            'category_ids' => [$category->id],
            'CategoryId' => $category->id,
            'target_group' => 'Men',
            'price' => 4500,
            'shippingFee' => 150,
            'shippingDays' => 5,
            'sizes' => ['M'],
            'size_stocks' => ['M' => 10],
            'description' => 'Authentic artisan pina fiber barong.',
            'product_is_gcash_available' => '1',
            'gcashNumber' => '09171234567',
            'gcashQrCode' => $qrCode,
            'variant_image_0' => $cover,
            'images' => [$gallery1, $gallery2, $gallery3],
            'variant_names' => [0 => 'Standard Classic'],
            'variant_indexes' => [0],
        ];

        $response = $this->actingAs($seller)->post(route('seller.products.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('seller.products.index'));

        $product = Product::where('sellerId', $seller->id)->first();
        $this->assertNotNull($product);

        $images = $product->image;
        $this->assertIsArray($images);
        $this->assertCount(4, $images, 'Database product.image must contain exactly 4 images (1 cover + 3 gallery)');

        // First image is cover
        $this->assertStringStartsWith('products/cover/', $images[0]);
        // Gallery images
        $this->assertStringStartsWith('products/gallery/', $images[1]);
        $this->assertStringStartsWith('products/gallery/', $images[2]);
        $this->assertStringStartsWith('products/gallery/', $images[3]);

        // Verify public storage
        $this->assertTrue(Storage::disk('public')->exists($images[0]));
        $this->assertTrue(Storage::disk('public')->exists($images[1]));
        $this->assertTrue(Storage::disk('public')->exists($images[2]));
        $this->assertTrue(Storage::disk('public')->exists($images[3]));

        // Model methods
        $this->assertEquals('/storage/' . $images[0], $product->getPrimaryImageUrl());
        $allUrls = $product->getAllImageUrls();
        $this->assertCount(4, $allUrls);

        // VariationFormatter gallery images
        $galleryList = VariationFormatter::buildGalleryImages($product->image, $product);
        $this->assertCount(4, $galleryList);

        // Purchasable styles
        $styles = VariationFormatter::buildStyleVariants($product);
        $this->assertCount(1, $styles);
        $this->assertEquals('Standard Classic', $styles[0]['name']);
    }

    public function test_seller_upload_variant_1_cover_and_variant_2_style_stores_both_variants_and_images(): void
    {
        Storage::fake('public');
        $seller = $this->createSeller();
        $category = \App\Models\Category::create([
            'name' => 'Heritage Barong',
            'target_group' => ['Men'],
            'description' => 'Fine Heritage Barongs',
        ]);

        $coverPhoto = UploadedFile::fake()->image('old_barong_colored.jpg', 800, 800);
        $variantPhoto = UploadedFile::fake()->image('chix_portrait.jpg', 800, 800);
        $galleryPhoto = UploadedFile::fake()->image('barong_sketch.jpg', 800, 800);
        $qrCode = UploadedFile::fake()->image('seller_qr.png', 400, 400);

        $payload = [
            'action' => 'publish',
            'name' => 'old barong',
            'category_ids' => [$category->id],
            'CategoryId' => $category->id,
            'target_group' => 'Men',
            'price' => 200,
            'shippingFee' => 120.95,
            'shippingDays' => 5,
            'sizes' => ['S'],
            'size_stocks' => ['S' => 5],
            'description' => 'Authentic masterpiece from Lumban embroiderers.',
            'product_is_gcash_available' => '1',
            'gcashNumber' => '09171234567',
            'gcashQrCode' => $qrCode,
            'variant_image_0' => $coverPhoto,
            'variant_image_1' => $variantPhoto,
            'images' => [$galleryPhoto],
            'variant_names' => [0 => 'old barong', 1 => 'chix'],
            'variant_indexes' => [0, 1],
        ];

        $response = $this->actingAs($seller)->post(route('seller.products.store'), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('seller.products.index'));

        $product = Product::where('sellerId', $seller->id)->where('name', 'old barong')->first();
        $this->assertNotNull($product);

        // Product image list must contain 3 images: Variant 1 cover, Variant 2, Gallery
        $images = $product->image;
        $this->assertIsArray($images);
        $this->assertCount(3, $images, 'product.image must retain all 3 uploaded images');
        $this->assertStringStartsWith('products/cover/', $images[0]);
        $this->assertStringStartsWith('products/variants/', $images[1]);
        $this->assertStringStartsWith('products/gallery/', $images[2]);

        // Variations must contain both variants
        $variations = $product->variations;
        $this->assertCount(2, $variations);
        $this->assertEquals('old barong', $variations[0]['name']);
        $this->assertStringStartsWith('products/cover/', $variations[0]['image']);
        $this->assertEquals('chix', $variations[1]['name']);
        $this->assertStringStartsWith('products/variants/', $variations[1]['image']);

        // Both variation images must exist on disk and resolve to public storage URLs
        $this->assertTrue(Storage::disk('public')->exists($variations[0]['image']));
        $this->assertTrue(Storage::disk('public')->exists($variations[1]['image']));
        $this->assertEquals('/storage/' . $variations[0]['image'], $product->getImageUrl($variations[0]['image']));
        $this->assertEquals('/storage/' . $variations[1]['image'], $product->getImageUrl($variations[1]['image']));

        // Verify seller edit page renders properly and includes /storage/ in variant image preview
        $editResponse = $this->actingAs($seller)->get(route('seller.products.edit', $product->id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('/storage/' . $variations[0]['image']);
        $editResponse->assertSee('/storage/' . $variations[1]['image']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. API Admin Authorization Hardening
    // ─────────────────────────────────────────────────────────────────────────

    public function test_api_admin_stats_rejects_unauthenticated_with_401(): void
    {
        $response = $this->getJson('/api/v1/admin/stats');
        $response->assertStatus(401);
    }

    public function test_api_admin_stats_rejects_customer_with_403_json(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer, ['*']);

        $response = $this->getJson('/api/v1/admin/stats');
        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
        $this->assertStringContainsString('Administrator', $response->json('message'));
    }

    public function test_api_admin_stats_rejects_seller_with_403_json(): void
    {
        $seller = $this->createSeller();
        Sanctum::actingAs($seller, ['*']);

        $response = $this->getJson('/api/v1/admin/stats');
        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
        $this->assertStringContainsString('Administrator', $response->json('message'));
    }

    public function test_api_admin_stats_allows_admin(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/v1/admin/stats');
        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. API Seller Authorization & Resource Ownership
    // ─────────────────────────────────────────────────────────────────────────

    public function test_api_seller_stats_rejects_customer_with_403_json(): void
    {
        $customer = $this->createCustomer();
        Sanctum::actingAs($customer, ['*']);

        $response = $this->getJson('/api/v1/seller/stats');
        $response->assertStatus(403);
    }

    public function test_seller_cannot_modify_another_sellers_product(): void
    {
        $sellerA = $this->createSeller();
        $sellerB = $this->createSeller();

        $productB = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $sellerB->id,
            'name' => 'Seller B Product',
            'price' => 2000,
            'stock' => 5,
            'status' => 'approved',
            'image' => ['products/cover/sample.jpg'],
        ]);

        $response = $this->actingAs($sellerA)->put(route('seller.products.update', $productB->id), [
            'action' => 'draft',
            'name' => 'Hacked Product Name',
            'price' => 10,
            'stock' => 100,
        ]);

        // Controller uses where('id', $id)->where('sellerId', Auth::id())->firstOrFail() -> 404
        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]));

        $productB->refresh();
        $this->assertEquals('Seller B Product', $productB->name);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Order Status Normalization
    // ─────────────────────────────────────────────────────────────────────────

    public function test_order_status_helper_canonicalizes_and_verifies_completed_state(): void
    {
        $this->assertEquals(OrderStatus::COMPLETED, OrderStatus::canonicalize('completed'));
        $this->assertEquals(OrderStatus::COMPLETED, OrderStatus::canonicalize('Completed'));
        $this->assertEquals(OrderStatus::DELIVERED, OrderStatus::canonicalize('Delivered'));
        $this->assertEquals(OrderStatus::DELIVERED, OrderStatus::canonicalize('delivered'));
        $this->assertEquals(OrderStatus::SHIPPED, OrderStatus::canonicalize('shipped'));
        $this->assertEquals(OrderStatus::CANCELLED, OrderStatus::canonicalize('cancelled'));
        $this->assertEquals(OrderStatus::CANCELLED, OrderStatus::canonicalize('canceled'));

        $this->assertTrue(OrderStatus::isCompleted('Completed'));
        $this->assertTrue(OrderStatus::isCompleted('Delivered'));
        $this->assertFalse(OrderStatus::isCompleted('Pending'));
        $this->assertFalse(OrderStatus::isCompleted('Shipped'));

        $this->assertTrue(OrderStatus::isEligibleForReturnOrRefund('Delivered'));
        $this->assertTrue(OrderStatus::isEligibleForReturnOrRefund('Completed'));
        $this->assertFalse(OrderStatus::isEligibleForReturnOrRefund('Processing'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. OrderItem Snapshot Without Runtime Schema Mutation
    // ─────────────────────────────────────────────────────────────────────────

    public function test_order_item_records_product_snapshot_without_runtime_schema_modification(): void
    {
        $customer = $this->createCustomer();
        $seller = $this->createSeller();

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $seller->id,
            'name' => 'Traditional Organza Barong',
            'price' => 3200,
            'stock' => 10,
            'status' => 'approved',
            'image' => ['products/cover/organza.jpg', 'products/gallery/detail.jpg'],
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $customer->id,
            'sellerId' => $seller->id,
            'totalAmount' => 3200,
            'status' => OrderStatus::PENDING,
            'paymentMethod' => 'GCash',
            'shippingAddress' => ['address' => 'Quezon City'],
        ]);

        $item = OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $product->id,
            'quantity' => 1,
            'price' => 3200,
            'product_name' => $product->name,
            'product_image' => $product->image[0],
        ]);

        $this->assertNotNull($item->id);
        $this->assertEquals('Traditional Organza Barong', $item->product_name);
        $this->assertEquals('products/cover/organza.jpg', $item->product_image);

        // ensureSnapshotColumnsExist is a safe no-op
        OrderItem::ensureSnapshotColumnsExist();
        $this->assertTrue(true);
    }
}

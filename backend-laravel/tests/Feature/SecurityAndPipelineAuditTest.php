<?php

namespace Tests\Feature;

use App\Models\CommissionRecord;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\VariationFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityAndPipelineAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new \Database\Seeders\ShippingLogisticsSeeder())->run();
    }

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
            'shopProvince' => 'Metro Manila',
            'shopCity' => 'Manila',
            'shopBarangay' => 'Barangay 1',
            'shopPostalCode' => '1000',
            'status' => 'active',
            'mobileNumber' => '0918' . rand(1000000, 9999999),
            'isVerified' => true,
            'email_verified_at' => now(),
        ], $attrs));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. SellerMiddleware Verification & Exemption Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_unverified_seller_is_redirected_to_verify_email(): void
    {
        $seller = $this->createSeller([
            'status' => 'awaiting_email_verification',
            'email_verified_at' => null,
            'isVerified' => false,
        ]);

        $response = $this->actingAs($seller)->get('/seller/dashboard');
        $response->assertRedirect(route('seller.verify-email'));
    }

    public function test_unverified_seller_can_access_verify_email_endpoint_without_loop(): void
    {
        $seller = $this->createSeller([
            'status' => 'awaiting_email_verification',
            'email_verified_at' => null,
            'isVerified' => false,
        ]);

        $response = $this->actingAs($seller)->get(route('seller.verify-email'));
        // Allowed by middleware exemption: should return 200 view, not redirect back into a loop
        $response->assertStatus(200);
    }

    public function test_ajax_unverified_seller_request_receives_403_json(): void
    {
        $seller = $this->createSeller([
            'status' => 'awaiting_email_verification',
            'email_verified_at' => null,
            'isVerified' => false,
        ]);

        $response = $this->actingAs($seller)->getJson('/seller/dashboard');
        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Email verification required.']);
    }

    public function test_verified_seller_is_not_blocked_by_verification_guard(): void
    {
        $seller = $this->createSeller([
            'status' => 'active',
            'email_verified_at' => now(),
            'isVerified' => true,
            'profile_completed' => true,
        ]);

        $response = $this->actingAs($seller)->get('/seller/dashboard');
        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Commission Route Protection & Ownership Authorization Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_submit_commission_payment(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->post('/submit-commission-payment', [
            'reference_number' => 'REF12345678',
            'payment_proof' => $file,
        ]);

        $response->assertRedirect('/login');
    }

    public function test_customer_cannot_submit_commission_payment(): void
    {
        Storage::fake('public');
        $customer = $this->createCustomer();
        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->actingAs($customer)->post('/submit-commission-payment', [
            'reference_number' => 'REF12345678',
            'payment_proof' => $file,
        ]);

        // Guarded by seller middleware / authorization
        $this->assertTrue(in_array($response->getStatusCode(), [302, 403]));
    }

    public function test_seller_cannot_submit_or_manipulate_another_sellers_commission(): void
    {
        Storage::fake('public');
        $sellerA = $this->createSeller(['email' => 'sellerA@example.com']);
        $sellerB = $this->createSeller(['email' => 'sellerB@example.com']);

        $commissionB = CommissionRecord::create([
            'sellerId' => $sellerB->id,
            'period' => 'September 2026',
            'commissionAmount' => 500.00,
            'status' => 'unpaid',
            'dueDate' => now()->addDays(5),
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg');

        // Seller A tries to target Seller B's commission record
        $response = $this->actingAs($sellerA)->post('/submit-commission-payment', [
            'commission_id' => $commissionB->id,
            'reference_number' => 'HACK123456',
            'payment_proof' => $file,
        ]);

        // Model query scoped to authenticated seller -> firstOrFail() produces 404
        $response->assertStatus(404);

        $commissionB->refresh();
        $this->assertEquals('unpaid', $commissionB->status);
        $this->assertNull($commissionB->referenceNumber);
    }

    public function test_seller_can_submit_own_commission_settlement(): void
    {
        Storage::fake('public');
        $seller = $this->createSeller();

        $commission = CommissionRecord::create([
            'sellerId' => $seller->id,
            'period' => 'September 2026',
            'commissionAmount' => 750.00,
            'status' => 'unpaid',
            'dueDate' => now()->addDays(3),
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->actingAs($seller)->post('/submit-commission-payment', [
            'commission_id' => $commission->id,
            'reference_number' => 'GCASH987654321',
            'payment_method' => 'GCash',
            'payment_proof' => $file,
            'notes' => 'Settling September fee',
        ]);

        $response->assertSessionHas('success');

        $commission->refresh();
        $this->assertEquals('verification_pending', $commission->status);
        $this->assertEquals('GCASH987654321', $commission->referenceNumber);
        $this->assertNotNull($commission->paymentProof);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Checkout Stock & Concurrency Validation Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_checkout_rejects_when_requested_quantity_exceeds_total_stock(): void
    {
        Storage::fake('public');
        $customer = $this->createCustomer();
        $seller = $this->createSeller();

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $seller->id,
            'name' => 'Limited Barong',
            'price' => 2500.00,
            'stock' => 2,
            'package_weight_per_unit' => 1.00,
            'package_length_per_unit' => 30.00,
            'package_width_per_unit' => 20.00,
            'package_height_per_unit' => 5.00,
            'status' => 'approved',
            'image' => ['cover.jpg'],
        ]);

        // Put 5 items into cart (stock is only 2)
        $cart = [
            [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 5,
                'sellerId' => $seller->id,
                'size' => 'M',
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'is_receipt' => true,
                                        'wallet' => 'GCash',
                                        'reference' => '1029384756123',
                                        'detected_amount' => null,
                                        'amount_confidence' => 0.0,
                                        'reference_confidence' => 0.99,
                                        'confidence' => 0.99,
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $screenshot = UploadedFile::fake()->image('gcash_receipt.jpg', 400, 800);

        $response = $this->actingAs($customer)
            ->withSession(['cart' => $cart])
            ->post('/checkout', [
                'paymentMethod' => 'GCash',
                'paymentReference' => '1029384756123',
                'paymentScreenshot' => $screenshot,
                'shippingAddress' => json_encode([
                    'province' => 'Metro Manila',
                    'city' => 'Manila',
                    'barangay' => 'Barangay 1',
                    'postalCode' => '1000',
                    'address' => 'Manila, Philippines',
                ]),
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Insufficient stock', session('error'));

        // Ensure stock was not decremented
        $product->refresh();
        $this->assertEquals(2, $product->stock);
    }

    public function test_checkout_rejects_when_requested_quantity_exceeds_size_stock(): void
    {
        Storage::fake('public');
        $customer = $this->createCustomer();
        $seller = $this->createSeller();

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $seller->id,
            'name' => 'Sized Barong',
            'price' => 3000.00,
            'stock' => 10,
            'size_stocks' => ['S' => 5, 'M' => 1, 'L' => 4],
            'package_weight_per_unit' => 1.00,
            'package_length_per_unit' => 30.00,
            'package_width_per_unit' => 20.00,
            'package_height_per_unit' => 5.00,
            'status' => 'approved',
            'image' => ['cover.jpg'],
        ]);

        // Request 2 Mediums when only 1 Medium is in size_stocks
        $cart = [
            [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 2,
                'sellerId' => $seller->id,
                'size' => 'M',
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'is_receipt' => true,
                                        'wallet' => 'GCash',
                                        'reference' => '1029384756123',
                                        'detected_amount' => null,
                                        'amount_confidence' => 0.0,
                                        'reference_confidence' => 0.99,
                                        'confidence' => 0.99,
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $screenshot = UploadedFile::fake()->image('gcash_receipt.jpg', 400, 800);

        $response = $this->actingAs($customer)
            ->withSession(['cart' => $cart])
            ->post('/checkout', [
                'paymentMethod' => 'GCash',
                'paymentReference' => '1029384756123',
                'paymentScreenshot' => $screenshot,
                'shippingAddress' => json_encode([
                    'province' => 'Metro Manila',
                    'city' => 'Manila',
                    'barangay' => 'Barangay 1',
                    'postalCode' => '1000',
                    'address' => 'Manila, Philippines',
                ]),
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Insufficient stock for size', session('error'));

        $product->refresh();
        $this->assertEquals(10, $product->stock);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. VariationFormatter Gallery vs Style Variants Decoupling Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_variation_formatter_handles_single_cover_image(): void
    {
        $seller = $this->createSeller();
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $seller->id,
            'name' => 'Single Photo Barong',
            'price' => 1500.00,
            'stock' => 5,
            'status' => 'approved',
            'image' => ['products/cover/single.jpg'],
        ]);

        $gallery = VariationFormatter::buildGalleryImages($product->image, $product);
        $styles = VariationFormatter::buildStyleVariants($product);

        $this->assertCount(1, $gallery);
        $this->assertEquals('products/cover/single.jpg', $gallery[0]['path']);
        // No purchasable style options defined
        $this->assertCount(0, $styles);
    }

    public function test_variation_formatter_preserves_multiple_gallery_images_without_polluting_styles(): void
    {
        $seller = $this->createSeller();
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $seller->id,
            'name' => 'Variant 1 With Gallery Photos',
            'price' => 2000.00,
            'stock' => 5,
            'status' => 'approved',
            // Variant 1 cover is in variations
            'variations' => [
                [
                    'name' => 'Original Style',
                    'image' => 'products/cover/cover1.webp',
                ]
            ],
            // Additional gallery shots are in product.image
            'image' => [
                'products/cover/cover1.webp',
                'products/gallery/embroidery_detail.webp',
                'products/gallery/collar_detail.webp',
                'products/gallery/back_view.webp',
            ],
        ]);

        $gallery = VariationFormatter::buildGalleryImages($product->image, $product);
        $styles = VariationFormatter::buildStyleVariants($product);

        // 1. Gallery MUST contain all 4 distinct photos (deduplicating cover1)
        $this->assertCount(4, $gallery);
        $galleryPaths = array_column($gallery, 'path');
        $this->assertContains('products/cover/cover1.webp', $galleryPaths);
        $this->assertContains('products/gallery/embroidery_detail.webp', $galleryPaths);
        $this->assertContains('products/gallery/collar_detail.webp', $galleryPaths);
        $this->assertContains('products/gallery/back_view.webp', $galleryPaths);

        // 2. Style variants MUST ONLY contain the 1 actual purchasable style, NOT the 4 gallery detail shots!
        $this->assertCount(1, $styles);
        $this->assertEquals('Original Style', $styles[0]['name']);
        $this->assertEquals('products/cover/cover1.webp', $styles[0]['image_path']);
    }

    public function test_variation_formatter_deduplicates_different_path_representations(): void
    {
        $seller = $this->createSeller();
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $seller->id,
            'name' => 'Deduplication Test Barong',
            'price' => 1800.00,
            'stock' => 3,
            'status' => 'approved',
            'image' => [
                'products/cover/item.jpg',
                '/storage/products/cover/item.jpg',
                'storage/products/cover/item.jpg',
                'uploads/products/cover/item.jpg',
            ],
        ]);

        $gallery = VariationFormatter::buildGalleryImages($product->image, $product);

        // All 4 represent the canonical 'products/cover/item.jpg' and must deduplicate to 1
        $this->assertCount(1, $gallery);
        $this->assertEquals('products/cover/item.jpg', $gallery[0]['path']);
    }

    public function test_variation_formatter_supports_multiple_distinct_style_variants(): void
    {
        $seller = $this->createSeller();
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $seller->id,
            'name' => 'Multi Style Barong',
            'price' => 3500.00,
            'stock' => 8,
            'status' => 'approved',
            'variations' => [
                [
                    'name' => 'Long Sleeve Traditional',
                    'image' => 'products/variants/long_sleeve.jpg',
                ],
                [
                    'name' => 'Short Sleeve Modern',
                    'image' => 'products/variants/short_sleeve.jpg',
                ],
            ],
            'image' => [
                'products/gallery/fabric_close_up.jpg',
            ],
        ]);

        $gallery = VariationFormatter::buildGalleryImages($product->image, $product);
        $styles = VariationFormatter::buildStyleVariants($product);

        // Gallery has 3 photos: 2 variant covers + 1 gallery photo
        $this->assertCount(3, $gallery);

        // Styles has exactly 2 purchasable options with explicit image paths
        $this->assertCount(2, $styles);
        $this->assertEquals('Long Sleeve Traditional', $styles[0]['name']);
        $this->assertEquals('products/variants/long_sleeve.jpg', $styles[0]['image_path']);
        $this->assertEquals('Short Sleeve Modern', $styles[1]['name']);
        $this->assertEquals('products/variants/short_sleeve.jpg', $styles[1]['image_path']);
    }
}

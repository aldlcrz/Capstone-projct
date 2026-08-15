<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\SellerSubscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SellerSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test subscription index page for seller.
     */
    public function test_seller_can_access_subscription_page(): void
    {
        $seller = User::create([
            'name' => 'Artisan Seller',
            'email' => 'artisan@example.com',
            'password' => bcrypt('password123'),
            'role' => 'seller',
            'isVerified' => true,
        ]);

        $response = $this->actingAs($seller)->get('/seller/subscription');

        $response->assertStatus(200);
        $response->assertSee('Artisan Upgrade');
    }

    /**
     * Test subscription request submission.
     */
    public function test_seller_can_submit_subscription_request(): void
    {
        $seller = User::create([
            'name' => 'Artisan Seller',
            'email' => 'artisan@example.com',
            'password' => bcrypt('password123'),
            'role' => 'seller',
            'isVerified' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@lumbarong.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($seller)->post('/seller/subscription/subscribe', [
            'paymentMethod' => 'GCash',
            'paymentReference' => 'REF123456789',
            'paymentProof' => UploadedFile::fake()->create('receipt.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertRedirect('/seller/subscription');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('seller_subscriptions', [
            'userId' => $seller->id,
            'paymentMethod' => 'GCash',
            'paymentReference' => 'REF123456789',
            'status' => 'pending',
        ]);

        // Check notification for admin
        $this->assertDatabaseHas('notifications', [
            'userId' => $admin->id,
            'targetRole' => 'admin',
            'title' => 'New Premium Subscription Request',
        ]);
    }

    /**
     * Test admin subscription approval.
     */
    public function test_admin_can_approve_subscription_request(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@lumbarong.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $seller = User::create([
            'name' => 'Artisan Seller',
            'email' => 'artisan@example.com',
            'password' => bcrypt('password123'),
            'role' => 'seller',
            'isVerified' => true,
        ]);

        $subscription = SellerSubscription::create([
            'userId' => $seller->id,
            'status' => 'pending',
            'planName' => 'Premium Tier',
            'amount' => 299.00,
            'paymentMethod' => 'GCash',
            'paymentReference' => 'REF123456',
            'paymentProof' => 'proofs/receipt.jpg',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/subscriptions/{$subscription->id}/approve");

        $response->assertRedirect('/admin/subscriptions');
        $response->assertSessionHas('success');

        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
        $this->assertNotNull($subscription->startsAt);
        $this->assertNotNull($subscription->endsAt);

        $seller->refresh();
        $this->assertTrue($seller->isPremium);
        $this->assertNotNull($seller->premiumEndsAt);

        // Check notification for seller
        $this->assertDatabaseHas('notifications', [
            'userId' => $seller->id,
            'targetRole' => 'seller',
            'title' => 'Premium Subscription Approved',
        ]);
    }

    /**
     * Test admin subscription rejection.
     */
    public function test_admin_can_reject_subscription_request(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@lumbarong.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $seller = User::create([
            'name' => 'Artisan Seller',
            'email' => 'artisan@example.com',
            'password' => bcrypt('password123'),
            'role' => 'seller',
            'isVerified' => true,
        ]);

        $subscription = SellerSubscription::create([
            'userId' => $seller->id,
            'status' => 'pending',
            'planName' => 'Premium Tier',
            'amount' => 299.00,
            'paymentMethod' => 'GCash',
            'paymentReference' => 'REF123456',
            'paymentProof' => 'proofs/receipt.jpg',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/subscriptions/{$subscription->id}/reject", [
            'rejectionReason' => 'Invalid reference number.',
        ]);

        $response->assertRedirect('/admin/subscriptions');
        $response->assertSessionHas('success');

        $subscription->refresh();
        $this->assertEquals('rejected', $subscription->status);
        $this->assertEquals('Invalid reference number.', $subscription->rejectionReason);

        $seller->refresh();
        $this->assertFalse($seller->isPremium);

        // Check notification for seller
        $this->assertDatabaseHas('notifications', [
            'userId' => $seller->id,
            'targetRole' => 'seller',
            'title' => 'Premium Subscription Rejected',
            'message' => 'Your Premium subscription request was rejected. Reason: Invalid reference number.',
        ]);
    }

    /**
     * Test product listing limits for standard vs premium.
     */
    public function test_product_listing_limits_for_standard_vs_premium(): void
    {
        $seller = User::create([
            'name' => 'Artisan Seller',
            'email' => 'artisan@example.com',
            'password' => bcrypt('password123'),
            'role' => 'seller',
            'isVerified' => true,
            'isPremium' => false,
        ]);

        $category = Category::create([
            'name' => 'Barong Tagalog',
        ]);

        // Create 10 products for the seller
        for ($i = 0; $i < 10; $i++) {
            Product::create([
                'sellerId' => $seller->id,
                'CategoryId' => $category->id,
                'name' => "Product {$i}",
                'description' => 'Test description',
                'price' => 100.00,
                'stock' => 5,
                'image' => ['products/test.jpg'],
                'status' => 'approved',
            ]);
        }

        // Attempting to create the 11th product page
        $response = $this->actingAs($seller)->get('/seller/products/create');
        $response->assertRedirect('/seller/products');
        $response->assertSessionHas('error', 'Free accounts are limited to 10 product listings. Upgrade to Premium for unlimited listings!');

        // Attempting to store the 11th product
        $response = $this->actingAs($seller)->post('/seller/products', [
            'name' => '11th Product',
            'description' => 'Test',
            'price' => 100,
            'CategoryId' => $category->id,
            'images' => [UploadedFile::fake()->create('p.jpg', 100, 'image/jpeg')],
        ]);
        $response->assertRedirect('/seller/products');
        $response->assertSessionHas('error', 'Free accounts are limited to 10 product listings. Upgrade to Premium for unlimited listings!');

        // Now, upgrade the seller to premium
        $seller->update([
            'isPremium' => true,
            'premiumEndsAt' => now()->addDays(30),
        ]);

        // Accessing listing creation page should now work
        $response = $this->actingAs($seller)->get('/seller/products/create');
        $response->assertStatus(200);

        // Storing the 11th product should now succeed
        $response = $this->actingAs($seller)->post('/seller/products', [
            'name' => '11th Product',
            'description' => 'Test description',
            'price' => 100,
            'CategoryId' => $category->id,
            'images' => [UploadedFile::fake()->create('p.jpg', 100, 'image/jpeg')],
        ]);

        $response->assertRedirect('/seller/products');
        $response->assertSessionHas('success', 'Product listed and awaiting approval.');
    }

    /**
     * Test that the Product's artisan accessor resolves to the seller's display name.
     */
    public function test_product_artisan_accessor_resolves_to_seller_display_name(): void
    {
        $seller = User::create([
            'name' => 'Dan Karindirya',
            'shopName' => 'Karindirya Loom Weavers',
            'email' => 'dan@example.com',
            'password' => bcrypt('password123'),
            'role' => 'seller',
            'isVerified' => true,
        ]);

        $product = Product::create([
            'sellerId' => $seller->id,
            'name' => 'Premium Barong',
            'description' => 'Test description',
            'price' => 100.00,
            'stock' => 5,
            'image' => ['products/test.jpg'],
            'status' => 'approved',
        ]);

        // Eager loaded or dynamically fetched, the artisan attribute should resolve to the shopName
        $this->assertEquals('Karindirya Loom Weavers', $product->artisan);

        // If shopName is not set, it should fallback to user's name
        $seller->update(['shopName' => null]);
        $product->refresh();
        $this->assertEquals('Dan Karindirya', $product->artisan);
    }
}

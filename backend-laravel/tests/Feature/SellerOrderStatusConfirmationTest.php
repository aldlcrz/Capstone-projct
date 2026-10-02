<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ShippingLogisticsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SellerOrderStatusConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected User $otherSeller;
    protected User $buyer;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\SystemSetting::firstOrCreate(
            ['key' => 'maintenanceMode'],
            ['value' => 'false']
        );

        $this->seed(ShippingLogisticsSeeder::class);

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'name' => 'Lumban Master Tailor',
            'shopName' => 'Lumban Tailoring Studio',
            'shopCity' => 'Lumban',
            'shopProvince' => 'Laguna',
            'shopPostalCode' => '4014',
            'email' => 'tailor@lumban.test',
            'status' => 'approved',
            'isVerified' => true,
            'email_verified_at' => now(),
        ]);

        $this->otherSeller = User::factory()->create([
            'role' => 'seller',
            'name' => 'Other Artisan',
            'shopName' => 'Other Studio',
            'shopCity' => 'Pagsanjan',
            'shopProvince' => 'Laguna',
            'shopPostalCode' => '4008',
            'email' => 'other@lumban.test',
            'status' => 'approved',
            'isVerified' => true,
            'email_verified_at' => now(),
        ]);

        $this->buyer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Crisostomo Ibarra',
            'email' => 'ibarra@customer.test',
            'status' => 'active',
            'isVerified' => true,
            'email_verified_at' => now(),
        ]);

        $this->category = Category::create([
            'name' => 'Formal Barong',
            'description' => 'Formal Wear',
        ]);

        $this->product = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Piña Organza Barong',
            'description' => 'Handcrafted embroidered barong',
            'price' => 3500.00,
            'stock' => 10,
            'status' => 'active',
            'images' => ['products/pina_sample.jpg'],
        ]);
    }

    protected function createTestOrder(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->buyer->id,
            'sellerId' => $this->seller->id,
            'shippingAddress' => json_encode([
                'fullName' => 'Crisostomo Ibarra',
                'phone' => '09171234567',
                'address' => 'Plaza Rizal',
                'city' => 'Lumban',
                'province' => 'Laguna',
            ]),
            'totalAmount' => 3500.00,
            'shippingFee' => 150.00,
            'status' => 'To Ship',
            'paymentStatus' => 'paid',
            'paymentMethod' => 'GCASH',
            'courierName' => 'J&T Express',
        ], $attributes));
    }

    public function test_seller_orders_view_contains_status_confirmation_modal_and_bindings(): void
    {
        $response = $this->actingAs($this->seller)->get(route('seller.orders'));

        $response->assertStatus(200);
        $response->assertSee('Confirm Order Status Update');
        $response->assertSee('showStatusConfirmModal');
        $response->assertSee('requestStatusUpdate');
        $response->assertSee('cancelStatusConfirm');
        $response->assertSee('executeConfirmedStatusUpdate');
        $response->assertSee('Confirm Update');
        $response->assertSee('Are you sure you want to update this order?');
    }

    public function test_seller_can_update_order_status_to_shipped_after_confirmation(): void
    {
        $order = $this->createTestOrder([
            'status' => 'To Ship',
        ]);

        $response = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'Shipped',
            'courierName' => 'J&T Express',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'Shipped',
        ]);
    }

    public function test_seller_can_update_order_to_in_transit_with_valid_tracking(): void
    {
        $order = $this->createTestOrder([
            'status' => 'Shipped',
        ]);

        $response = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'In Transit',
            'courierName' => 'J&T Express',
            'trackingNumber' => 'JT1234567890',
            'trackingLink' => 'https://www.jtexpress.ph/index/query/gzquery.html?bills=JT1234567890',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'In Transit',
            'trackingNumber' => 'JT1234567890',
        ]);
    }

    public function test_special_delivery_order_can_transition_to_in_transit_without_tracking_number(): void
    {
        $order = $this->createTestOrder([
            'status' => 'Shipped',
            'paymentMethod' => 'COD',
            'courierName' => 'Special Delivery (Local Artisan Rider)',
        ]);

        $response = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'In Transit',
            'courierName' => 'Special Delivery (Local Artisan Rider)',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'In Transit',
            'courierName' => 'Special Delivery (Local Artisan Rider)',
        ]);
    }

    public function test_store_pickup_order_cannot_transition_to_in_transit(): void
    {
        $order = $this->createTestOrder([
            'status' => 'Shipped',
            'courierName' => 'Store Pickup',
        ]);

        $response = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'In Transit',
        ]);

        $response->assertStatus(400);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'Shipped',
        ]);
    }

    public function test_unauthorized_seller_cannot_update_another_sellers_order(): void
    {
        $order = $this->createTestOrder([
            'status' => 'To Ship',
        ]);

        $response = $this->actingAs($this->otherSeller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'Shipped',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'To Ship',
        ]);
    }

    public function test_invalid_backward_status_transition_is_rejected(): void
    {
        $order = $this->createTestOrder([
            'status' => 'Delivered',
        ]);

        $response = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'To Ship',
        ]);

        $response->assertStatus(400);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'Delivered',
        ]);
    }

    public function test_seller_cannot_manually_mark_order_as_completed(): void
    {
        $order = $this->createTestOrder([
            'status' => 'Delivered',
        ]);

        $response = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'Completed',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'Delivered',
        ]);
    }
}

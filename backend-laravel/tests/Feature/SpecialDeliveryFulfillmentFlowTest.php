<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipping;
use App\Models\Product;
use App\Models\ShippingProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SpecialDeliveryFulfillmentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $seller;
    protected Product $product;
    protected ShippingProvider $specialDeliveryProvider;
    protected ShippingProvider $courierProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Maria Santos',
            'username' => 'mariasantos',
            'email' => 'maria@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'mobileNumber' => '09171234567',
            'isVerified' => true,
        ]);

        $this->seller = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Lumban Local Artisan',
            'username' => 'lumbanartisan',
            'email' => 'seller@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'shopName' => 'Laguna Heritage Studio',
            'status' => 'active',
            'mobileNumber' => '09181234567',
            'isVerified' => true,
        ]);

        $this->product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Custom Bordado Kimona',
            'description' => 'Handcrafted embroidery in Lumban',
            'price' => 2800.00,
            'stock' => 8,
            'status' => 'approved',
            'image' => ['kimona.jpg'],
        ]);

        $this->specialDeliveryProvider = ShippingProvider::firstOrCreate(
            ['code' => 'seller_direct'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Special Delivery (Local Artisan Rider)',
                'service_type' => 'local_delivery',
                'is_active' => true,
                'settings' => ['description' => 'Nearby delivery within Lumban and nearby areas'],
            ]
        );

        $this->courierProvider = ShippingProvider::firstOrCreate(
            ['code' => 'jnt'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'J&T Express',
                'service_type' => 'standard',
                'is_active' => true,
                'settings' => ['tracking_url' => 'https://www.jtexpress.ph/index/query/gzquery.html'],
            ]
        );
    }

    protected function attachShipping(Order $order, ShippingProvider $provider, array $attrs = []): OrderShipping
    {
        return OrderShipping::create(array_merge([
            'order_id' => $order->id,
            'provider_id' => $provider->id,
            'provider_name' => $provider->name,
            'pricing_provider_name' => $provider->name,
            'fulfillment_provider_name' => $provider->name,
            'origin_zone_name' => 'Lumban Workshop',
            'destination_zone_name' => $provider->code === 'seller_direct' ? 'Pagsanjan / Lumban Local Area' : 'Metro Manila',
            'actual_weight' => 0.5,
            'volumetric_weight' => 0.4,
            'chargeable_weight' => 0.5,
            'rate_base_snapshot' => $provider->code === 'seller_direct' ? 50.00 : 150.00,
            'additional_weight_rate_snapshot' => 0.00,
            'volumetric_divisor_snapshot' => 3500,
            'shipping_fee' => $provider->code === 'seller_direct' ? 50.00 : 150.00,
            'estimated_days_min' => 1,
            'estimated_days_max' => 2,
            'shipping_status' => 'pending',
            'tracking_number' => $provider->code === 'seller_direct' ? null : '781234567890',
        ], $attrs));
    }

    protected function createSpecialDeliveryOrder(string $status = 'Pending'): Order
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 2850.00,
            'status' => $status,
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Pending',
            'shippingAddress' => [
                'recipientName' => 'Maria Santos',
                'street' => '123 Rizal Street',
                'barangay' => 'Barangay Segunda',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postalCode' => '4014',
                'phone' => '09171234567',
            ],
            'notes' => 'Please deliver in the afternoon.',
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'quantity' => 1,
            'price' => 2800.00,
        ]);

        $this->attachShipping($order, $this->specialDeliveryProvider);

        return $order->fresh(['shipping', 'items.product', 'customer', 'seller']);
    }

    protected function createCourierOrder(string $status = 'Pending'): Order
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 2950.00,
            'status' => $status,
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Pending',
            'shippingAddress' => [
                'recipientName' => 'Maria Santos',
                'street' => '456 Ayala Ave',
                'barangay' => 'Bel-Air',
                'city' => 'Makati City',
                'province' => 'Metro Manila',
                'postalCode' => '1209',
                'phone' => '09171234567',
            ],
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'quantity' => 1,
            'price' => 2800.00,
        ]);

        $this->attachShipping($order, $this->courierProvider, [
            'destination_zone_name' => 'Metro Manila',
            'shipping_fee' => 150.00,
        ]);

        return $order->fresh(['shipping', 'items.product', 'customer', 'seller']);
    }

    public function test_order_model_correctly_identifies_special_delivery()
    {
        $specialOrder = $this->createSpecialDeliveryOrder();
        $courierOrder = $this->createCourierOrder();

        $this->assertTrue($specialOrder->isSpecialDelivery());
        $this->assertTrue($specialOrder->is_special_delivery);

        $this->assertFalse($courierOrder->isSpecialDelivery());
        $this->assertFalse($courierOrder->is_special_delivery);
    }

    public function test_seller_can_advance_special_delivery_to_to_ship()
    {
        $order = $this->createSpecialDeliveryOrder('Pending');

        $response = $this->actingAs($this->seller)
            ->patchJson('/seller/api/orders/' . $order->id . '/status', [
                'status' => 'To Ship',
            ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('To Ship', $order->status);
    }

    public function test_seller_can_advance_special_delivery_from_to_ship_to_shipped_without_tracking_number()
    {
        $order = $this->createSpecialDeliveryOrder('To Ship');

        $response = $this->actingAs($this->seller)
            ->patchJson('/seller/api/orders/' . $order->id . '/status', [
                'status' => 'Shipped',
            ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('Shipped', $order->status);
        $this->assertNull($order->trackingNumber);
    }

    public function test_seller_can_advance_special_delivery_to_in_transit_without_tracking_number()
    {
        $order = $this->createSpecialDeliveryOrder('Shipped');

        $response = $this->actingAs($this->seller)
            ->patchJson('/seller/api/orders/' . $order->id . '/status', [
                'status' => 'In Transit',
            ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('In Transit', $order->status);
        $this->assertNull($order->trackingNumber);
        $this->assertEquals('Special Delivery (Local Artisan Rider)', $order->shipping->fulfillment_provider_name);
    }

    public function test_server_cleanses_and_rejects_courier_tracking_info_for_special_delivery()
    {
        $order = $this->createSpecialDeliveryOrder('Shipped');

        // Attempting to send third-party courier and tracking data on Special Delivery
        $response = $this->actingAs($this->seller)
            ->patchJson('/seller/api/orders/' . $order->id . '/status', [
                'status' => 'In Transit',
                'courierName' => 'LBC Express',
                'trackingNumber' => '123456789012',
                'trackingLink' => 'https://www.lbcexpress.com/track',
            ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('In Transit', $order->status);
        // Tracking number and URL must be nullified for Special Delivery
        $this->assertNull($order->trackingNumber);
        $this->assertNull($order->trackingLink);
        $this->assertEquals('Special Delivery (Local Artisan Rider)', $order->shipping->fulfillment_provider_name);
    }

    public function test_seller_can_mark_special_delivery_as_delivered_and_customer_can_confirm_receipt()
    {
        $order = $this->createSpecialDeliveryOrder('In Transit');

        $response = $this->actingAs($this->seller)
            ->patchJson('/seller/api/orders/' . $order->id . '/status', [
                'status' => 'Delivered',
            ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('Delivered', $order->status);

        // Customer confirms receipt
        $responseConfirm = $this->actingAs($this->customer)
            ->patch('/orders/' . $order->id . '/confirm');

        $responseConfirm->assertRedirect();
        $order->refresh();
        $this->assertEquals('Completed', $order->status);
    }

    public function test_standard_courier_order_still_requires_valid_tracking_number_for_in_transit()
    {
        $order = $this->createCourierOrder('Shipped');

        // Transitioning to In Transit without a tracking number on a standard courier order should fail
        $response = $this->actingAs($this->seller)
            ->patchJson('/seller/api/orders/' . $order->id . '/status', [
                'status' => 'In Transit',
                'trackingNumber' => '',
            ]);

        $response->assertStatus(422);

        // Transitioning with a valid tracking number succeeds
        $responseValid = $this->actingAs($this->seller)
            ->patchJson('/seller/api/orders/' . $order->id . '/status', [
                'status' => 'In Transit',
                'courierName' => 'J&T Express',
                'trackingNumber' => '781234567890',
            ]);

        $responseValid->assertStatus(200);
        $order->refresh();
        $this->assertEquals('In Transit', $order->status);
        $this->assertEquals('781234567890', $order->trackingNumber);
    }

    public function test_buyer_order_page_renders_special_delivery_fulfillment_progress()
    {
        $order = $this->createSpecialDeliveryOrder('In Transit');

        $response = $this->actingAs($this->customer)
            ->get('/orders/' . $order->id);

        $response->assertStatus(200);
        $response->assertSee('Special Delivery');
        $response->assertSee('Special Delivery Fulfillment Progress');
        $response->assertSee('Local Artisan Rider');
        $response->assertSee('Out for Special Delivery');
        $response->assertDontSee('Track on J&T Express');
    }
}

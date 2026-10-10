<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderShipping;
use App\Models\Product;
use App\Models\ShippingProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorePickupFulfillmentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $seller;
    protected Product $product;
    protected ShippingProvider $storePickupProvider;
    protected ShippingProvider $courierProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Juan dela Cruz',
            'username' => 'juandelacruz',
            'email' => 'juan@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'mobileNumber' => '09171234567',
            'isVerified' => true,
        ]);

        $this->seller = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Lumban Embroidery Master',
            'username' => 'lumbanartisan',
            'email' => 'seller@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'shopName' => 'Lumban Heritage Craft',
            'status' => 'active',
            'mobileNumber' => '09181234567',
            'isVerified' => true,
        ]);

        $this->product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Hand-Embroidered Barong Tagalog',
            'description' => 'Pure Piña Seda fabric crafted in Lumban',
            'price' => 3500.00,
            'stock' => 10,
            'status' => 'approved',
            'image' => ['barong.jpg'],
        ]);

        $this->storePickupProvider = ShippingProvider::firstOrCreate(
            ['code' => 'store_pickup'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Store Pickup',
                'service_type' => 'pickup',
                'is_active' => true,
                'settings' => ['description' => 'Claim in Lumban workshop'],
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
            'destination_zone_name' => $provider->code === 'store_pickup' ? 'Store Pickup Point' : 'Metro Manila',
            'actual_weight' => 0.5,
            'volumetric_weight' => 0.4,
            'chargeable_weight' => 0.5,
            'rate_base_snapshot' => $provider->code === 'store_pickup' ? 0.00 : 200.00,
            'additional_weight_rate_snapshot' => 0.00,
            'volumetric_divisor_snapshot' => 3500,
            'shipping_fee' => $provider->code === 'store_pickup' ? 0.00 : 200.00,
            'estimated_days_min' => 1,
            'estimated_days_max' => 3,
            'shipping_status' => 'pending',
            'tracking_number' => $provider->code === 'store_pickup' ? 'PICKUP-' . strtoupper(Str::random(6)) : 'JT' . rand(10000000, 99999999) . 'PH',
        ], $attrs));
    }

    /**
     * Test Order Model isStorePickup helper and is_store_pickup attribute.
     */
    public function test_order_model_recognizes_store_pickup_accurately(): void
    {
        // 1. Store pickup order via shipping provider
        $pickupOrder = Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'shippingAddress' => json_encode([
                'fullName' => 'Juan dela Cruz',
                'phone' => '09171234567',
                'address' => 'Barangay 1',
                'city' => 'Lumban',
                'province' => 'Laguna',
            ]),
            'totalAmount' => 3500.00,
            'shippingFee' => 0.00,
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Unpaid',
            'status' => 'Pending',
        ]);

        $this->attachShipping($pickupOrder, $this->storePickupProvider);

        $pickupOrder->refresh();
        $this->assertTrue($pickupOrder->isStorePickup());
        $this->assertTrue($pickupOrder->is_store_pickup);

        // 2. Courier order
        $courierOrder = Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'shippingAddress' => json_encode([
                'fullName' => 'Juan dela Cruz',
                'phone' => '09171234567',
                'address' => 'Ayala Ave',
                'city' => 'Makati',
                'province' => 'Metro Manila',
            ]),
            'totalAmount' => 3700.00,
            'shippingFee' => 200.00,
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Unpaid',
            'status' => 'Pending',
        ]);

        $this->attachShipping($courierOrder, $this->courierProvider);

        $courierOrder->refresh();
        $this->assertFalse($courierOrder->isStorePickup());
        $this->assertFalse($courierOrder->is_store_pickup);
    }

    /**
     * Test Store Pickup with COD:
     * Fulfillment is Store Pickup, payment is COD (Cash on Pickup).
     * Courier fields must not be required on status transition to shipped/ready.
     */
    public function test_store_pickup_with_cod_flow(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'shippingAddress' => json_encode([
                'fullName' => 'Juan dela Cruz',
                'phone' => '09171234567',
                'address' => 'Barangay 1',
                'city' => 'Lumban',
                'province' => 'Laguna',
            ]),
            'totalAmount' => 3500.00,
            'shippingFee' => 0.00,
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Unpaid',
            'status' => 'To Ship',
        ]);

        $this->attachShipping($order, $this->storePickupProvider);

        $this->actingAs($this->seller);

        // Seller marks order as Ready for Pickup (status: shipped)
        $response = $this->patchJson("/seller/api/orders/{$order->id}/status", [
            'status' => 'shipped',
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('shipped', strtolower($order->status));
        $this->assertEquals('Store Pickup', $order->courierName);

        // Seller directly marks as Claimed / Delivered
        $deliverResponse = $this->patchJson("/seller/api/orders/{$order->id}/status", [
            'status' => 'delivered',
        ]);

        $deliverResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals('delivered', strtolower($order->status));
    }

    /**
     * Test Store Pickup with Online Payment (GCash):
     * Payment is Verified, Store Pickup fulfillment proceeds without courier waybill.
     */
    public function test_store_pickup_with_online_payment_flow(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'shippingAddress' => json_encode([
                'fullName' => 'Juan dela Cruz',
                'phone' => '09171234567',
                'address' => 'Barangay 1',
                'city' => 'Lumban',
                'province' => 'Laguna',
            ]),
            'totalAmount' => 3500.00,
            'shippingFee' => 0.00,
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Verified',
            'paymentReference' => 'GCASH-987654321',
            'status' => 'To Ship',
        ]);

        $this->attachShipping($order, $this->storePickupProvider);

        $this->actingAs($this->seller);

        // Transition To Ship -> Shipped (Ready for Pickup)
        $response = $this->patchJson("/seller/api/orders/{$order->id}/status", [
            'status' => 'shipped',
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('shipped', strtolower($order->status));
        $this->assertTrue($order->is_store_pickup);
    }

    /**
     * Test Courier Delivery with COD:
     * Requires tracking number or validates courier shipment fields when marked in transit.
     */
    public function test_courier_delivery_with_cod_retains_courier_behavior(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'shippingAddress' => json_encode([
                'fullName' => 'Juan dela Cruz',
                'phone' => '09171234567',
                'address' => 'Ayala Ave',
                'city' => 'Makati',
                'province' => 'Metro Manila',
            ]),
            'totalAmount' => 3700.00,
            'shippingFee' => 200.00,
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Unpaid',
            'status' => 'To Ship',
        ]);

        $this->attachShipping($order, $this->courierProvider);

        $this->actingAs($this->seller);

        $response = $this->patchJson("/seller/api/orders/{$order->id}/status", [
            'status' => 'shipped',
            'courierName' => 'J&T Express',
            'trackingNumber' => 'JT123456789012',
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('shipped', strtolower($order->status));
        $this->assertEquals('J&T Express', $order->courierName);
        $this->assertEquals('JT123456789012', $order->trackingNumber);
        $this->assertFalse($order->is_store_pickup);
    }

    /**
     * Test Seller Orders Dashboard view loads json order data with is_store_pickup attribute.
     */
    public function test_seller_orders_view_passes_store_pickup_flag(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'shippingAddress' => json_encode([
                'fullName' => 'Juan dela Cruz',
                'phone' => '09171234567',
                'address' => 'Barangay 1',
                'city' => 'Lumban',
                'province' => 'Laguna',
            ]),
            'totalAmount' => 3500.00,
            'shippingFee' => 0.00,
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Unpaid',
            'status' => 'Pending',
        ]);

        $this->attachShipping($order, $this->storePickupProvider);

        $this->actingAs($this->seller);

        $response = $this->get(route('seller.orders'));
        $response->assertStatus(200);
        $response->assertSee('Store Pickup');
    }

    /**
     * Test Store Pickup with Maya Payment:
     * Payment is Verified, Store Pickup fulfillment proceeds without courier waybill.
     */
    public function test_store_pickup_with_maya_flow(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'shippingAddress' => json_encode([
                'fullName' => 'Juan dela Cruz',
                'phone' => '09171234567',
                'address' => 'Barangay 1',
                'city' => 'Lumban',
                'province' => 'Laguna',
            ]),
            'totalAmount' => 3500.00,
            'shippingFee' => 0.00,
            'paymentMethod' => 'Maya',
            'paymentStatus' => 'Verified',
            'paymentReference' => 'MAYA-12345678',
            'status' => 'To Ship',
        ]);

        $this->attachShipping($order, $this->storePickupProvider);

        $this->actingAs($this->seller);

        // Transition To Ship -> Shipped (Ready for Pickup)
        $response = $this->patchJson("/seller/api/orders/{$order->id}/status", [
            'status' => 'shipped',
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('shipped', strtolower($order->status));
        $this->assertTrue($order->is_store_pickup);
        $this->assertEquals('Store Pickup', $order->courierName);
        $this->assertNull($order->trackingNumber);
    }

    /**
     * Test Store Pickup submitted with courier fields:
     * Must ignore/sanitize inappropriate courier data and not fail on courier validation.
     */
    public function test_store_pickup_ignores_and_sanitizes_submitted_courier_fields(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'shippingAddress' => json_encode([
                'fullName' => 'Juan dela Cruz',
                'phone' => '09171234567',
                'address' => 'Barangay 1',
                'city' => 'Lumban',
                'province' => 'Laguna',
            ]),
            'totalAmount' => 3500.00,
            'shippingFee' => 0.00,
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Unpaid',
            'status' => 'To Ship',
        ]);

        $this->attachShipping($order, $this->storePickupProvider);

        $this->actingAs($this->seller);

        // Submit extraneous/invalid courier data with Store Pickup
        $response = $this->patchJson("/seller/api/orders/{$order->id}/status", [
            'status' => 'shipped',
            'courierName' => 'J&T Express',
            'trackingNumber' => 'INVALID_TRACKING_123',
            'trackingLink' => 'https://example.com/track',
        ]);

        // Should succeed without courier validation errors, and courier data must be sanitized to null / 'Store Pickup'
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('shipped', strtolower($order->status));
        $this->assertEquals('Store Pickup', $order->courierName);
        $this->assertNull($order->trackingNumber);
        $this->assertNull($order->trackingLink);
    }

    /**
     * Test Store Pickup cannot transition to courier-only in-transit states.
     */
    public function test_store_pickup_cannot_transition_to_courier_in_transit_states(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'shippingAddress' => json_encode([
                'fullName' => 'Juan dela Cruz',
                'phone' => '09171234567',
                'address' => 'Barangay 1',
                'city' => 'Lumban',
                'province' => 'Laguna',
            ]),
            'totalAmount' => 3500.00,
            'shippingFee' => 0.00,
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Unpaid',
            'status' => 'shipped',
        ]);

        $this->attachShipping($order, $this->storePickupProvider);

        $this->actingAs($this->seller);

        // Attempting to move Store Pickup to In Transit must fail
        $response = $this->patchJson("/seller/api/orders/{$order->id}/status", [
            'status' => 'In Transit',
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'message' => 'Store pickup orders do not use physical courier shipment and cannot transition to in-transit states.',
        ]);

        $order->refresh();
        $this->assertEquals('shipped', strtolower($order->status));
    }

    /**
     * Test Customer Order Show view renders Store Pickup details.
     */
    public function test_customer_order_show_view_renders_store_pickup(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'shippingAddress' => json_encode([
                'fullName' => 'Juan dela Cruz',
                'phone' => '09171234567',
                'address' => 'Barangay 1',
                'city' => 'Lumban',
                'province' => 'Laguna',
            ]),
            'totalAmount' => 3500.00,
            'shippingFee' => 0.00,
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Unpaid',
            'status' => 'shipped',
        ]);

        \App\Models\OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 3500.00,
        ]);

        $this->attachShipping($order, $this->storePickupProvider);

        $this->actingAs($this->customer);

        $response = $this->get(route('orders.show', $order->id));
        $response->assertStatus(200);
        $response->assertSee('Store Pickup');
        $response->assertSee('Ready for Pickup');
        $response->assertSee('Self-Pickup at Workshop');
    }

    public function test_seller_can_set_appointment_when_accepting_store_pickup_order(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'shippingAddress' => json_encode([
                'fullName' => 'Juan dela Cruz',
                'phone' => '09171234567',
                'address' => 'Barangay 1',
                'city' => 'Lumban',
                'province' => 'Laguna',
            ]),
            'totalAmount' => 3500.00,
            'shippingFee' => 0.00,
            'paymentMethod' => 'Pay in Shop',
            'paymentStatus' => 'Unpaid',
            'status' => 'Pending',
        ]);

        $this->attachShipping($order, $this->storePickupProvider);

        $this->actingAs($this->seller);

        $apptDate = date('Y-m-d', strtotime('+2 days'));
        $response = $this->patchJson("/seller/api/orders/{$order->id}/status", [
            'status' => 'Shipped',
            'appointment_date' => $apptDate,
            'appointment_time' => 'Morning (9:00 AM - 12:00 PM)',
            'appointment_notes' => 'Please ask for Mang Juan upon arrival.',
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals($apptDate, $order->appointment_date->format('Y-m-d'));
        $this->assertEquals('Morning (9:00 AM - 12:00 PM)', $order->appointment_time);
        $this->assertEquals('Please ask for Mang Juan upon arrival.', $order->appointment_notes);

        // Verify customer view renders the appointment banner
        $this->actingAs($this->customer);
        $customerView = $this->get(route('orders.show', $order->id));
        $customerView->assertStatus(200);
        $customerView->assertSee('In-Shop Store Visit Appointment');
        $customerView->assertSee('Morning (9:00 AM - 12:00 PM)');
        $customerView->assertSee('Please ask for Mang Juan upon arrival.');
    }
}


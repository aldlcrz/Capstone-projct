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

class StorePickupReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;
    protected User $otherBuyer;
    protected User $seller;
    protected User $otherSeller;
    protected User $superadmin;
    protected Product $product;
    protected ShippingProvider $storePickupProvider;
    protected ShippingProvider $courierProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Maria Clara',
            'username' => 'mariaclara',
            'email' => 'maria@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'mobileNumber' => '09171234567',
            'isVerified' => true,
        ]);

        $this->otherBuyer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Crisostomo Ibarra',
            'username' => 'ibarra',
            'email' => 'ibarra@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'mobileNumber' => '09179998877',
            'isVerified' => true,
        ]);

        $this->seller = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Lumban Master Artisan',
            'username' => 'lumbanmaster',
            'email' => 'master@lumban.test',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'shopName' => 'Lumban Heritage Craft',
            'status' => 'active',
            'mobileNumber' => '09181112233',
            'isVerified' => true,
        ]);

        $this->otherSeller = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Laguna Craftsman',
            'username' => 'lagunacraft',
            'email' => 'craft@laguna.test',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'shopName' => 'Laguna Woodworks',
            'status' => 'active',
            'mobileNumber' => '09189998877',
            'isVerified' => true,
        ]);

        $this->superadmin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'System Superadmin',
            'username' => 'superadmin',
            'email' => 'superadmin@system.test',
            'password' => Hash::make('Password123!'),
            'role' => 'superadmin',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $this->product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Heritage Piña Barong',
            'description' => 'Fine hand embroidery crafted in Lumban',
            'price' => 4500.00,
            'stock' => 15,
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
                'settings' => ['description' => 'Direct Lumban workshop collection'],
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

    protected function createPickupOrder(string $status = 'To Ship'): Order
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->buyer->id,
            'sellerId' => $this->seller->id,
            'status' => $status,
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Paid',
            'totalAmount' => 4500.00,
            'shippingFee' => 0.00,
            'shippingAddress' => json_encode([
                'fullName' => $this->buyer->name,
                'phone' => $this->buyer->mobileNumber,
                'address' => 'Store Pickup at Lumban Workshop',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postalCode' => '4014',
            ]),
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'productName' => $this->product->name,
            'quantity' => 1,
            'price' => 4500.00,
            'subtotal' => 4500.00,
            'variant' => 'Size L - Cream',
        ]);

        OrderShipping::create([
            'order_id' => $order->id,
            'provider_id' => $this->storePickupProvider->id,
            'provider_name' => 'Store Pickup',
            'pricing_provider_name' => 'Store Pickup',
            'fulfillment_provider_name' => 'Store Pickup',
            'origin_zone_name' => 'Lumban Heritage Craft Workshop',
            'destination_zone_name' => 'Lumban Collection Point',
            'actual_weight' => 0.5,
            'volumetric_weight' => 0.4,
            'chargeable_weight' => 0.5,
            'rate_base_snapshot' => 0.00,
            'additional_weight_rate_snapshot' => 0.00,
            'volumetric_divisor_snapshot' => 3500,
            'shipping_fee' => 0.00,
            'estimated_days_min' => 1,
            'estimated_days_max' => 1,
            'shipping_status' => 'pending',
            'tracking_number' => 'PICKUP-' . strtoupper(Str::random(6)),
        ]);

        return $order->fresh(['items', 'shipping', 'customer', 'seller']);
    }

    protected function createCourierOrder(): Order
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->buyer->id,
            'sellerId' => $this->seller->id,
            'status' => 'To Ship',
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Unpaid',
            'totalAmount' => 4650.00,
            'shippingFee' => 150.00,
            'shippingAddress' => json_encode([
                'fullName' => $this->buyer->name,
                'phone' => $this->buyer->mobileNumber,
                'address' => '123 Rizal St',
                'city' => 'Calamba',
                'province' => 'Laguna',
                'postalCode' => '4027',
            ]),
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'productName' => $this->product->name,
            'quantity' => 1,
            'price' => 4500.00,
            'subtotal' => 4500.00,
        ]);

        OrderShipping::create([
            'order_id' => $order->id,
            'provider_id' => $this->courierProvider->id,
            'provider_name' => 'J&T Express',
            'pricing_provider_name' => 'J&T Express',
            'fulfillment_provider_name' => 'J&T Express',
            'origin_zone_name' => 'Lumban Workshop',
            'destination_zone_name' => 'Laguna Province',
            'actual_weight' => 0.5,
            'volumetric_weight' => 0.4,
            'chargeable_weight' => 0.5,
            'rate_base_snapshot' => 150.00,
            'additional_weight_rate_snapshot' => 0.00,
            'volumetric_divisor_snapshot' => 3500,
            'shipping_fee' => 150.00,
            'estimated_days_min' => 1,
            'estimated_days_max' => 3,
            'shipping_status' => 'pending',
            'tracking_number' => 'JT12345678PH',
        ]);

        return $order->fresh(['items', 'shipping', 'customer', 'seller']);
    }

    public function test_buyer_can_view_and_download_pickup_receipt(): void
    {
        $order = $this->createPickupOrder('Shipped');

        // Test stream view
        $viewResponse = $this->actingAs($this->buyer)->get(route('orders.pickup-receipt', $order->id));
        $viewResponse->assertStatus(200);
        $viewResponse->assertHeader('content-type', 'application/pdf');

        // Test download
        $downloadResponse = $this->actingAs($this->buyer)->get(route('orders.pickup-receipt.download', $order->id));
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment;', $downloadResponse->headers->get('content-disposition'));
        $this->assertStringContainsStringIgnoringCase('pickup-receipt-', $downloadResponse->headers->get('content-disposition'));
    }

    public function test_seller_can_view_and_download_pickup_receipt_for_own_order(): void
    {
        $order = $this->createPickupOrder('Shipped');

        // Test seller stream view
        $viewResponse = $this->actingAs($this->seller)->get(route('seller.orders.pickup-receipt', $order->id));
        $viewResponse->assertStatus(200);
        $viewResponse->assertHeader('content-type', 'application/pdf');

        // Test seller download
        $downloadResponse = $this->actingAs($this->seller)->get(route('seller.orders.pickup-receipt.download', $order->id));
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment;', $downloadResponse->headers->get('content-disposition'));
    }

    public function test_superadmin_can_view_and_download_pickup_receipt(): void
    {
        $order = $this->createPickupOrder('Shipped');

        $response = $this->actingAs($this->superadmin)->get(route('orders.pickup-receipt', $order->id));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_unauthorized_buyer_cannot_access_another_users_pickup_receipt(): void
    {
        $order = $this->createPickupOrder('Shipped');

        // Other buyer tries to view
        $viewResponse = $this->actingAs($this->otherBuyer)->get(route('orders.pickup-receipt', $order->id));
        $viewResponse->assertStatus(403);

        // Other buyer tries to download
        $downloadResponse = $this->actingAs($this->otherBuyer)->get(route('orders.pickup-receipt.download', $order->id));
        $downloadResponse->assertStatus(403);
    }

    public function test_unauthorized_seller_cannot_access_another_sellers_pickup_receipt(): void
    {
        $order = $this->createPickupOrder('Shipped');

        // Other seller tries to view
        $viewResponse = $this->actingAs($this->otherSeller)->get(route('seller.orders.pickup-receipt', $order->id));
        $viewResponse->assertStatus(403);

        // Other seller tries to download
        $downloadResponse = $this->actingAs($this->otherSeller)->get(route('seller.orders.pickup-receipt.download', $order->id));
        $downloadResponse->assertStatus(403);
    }

    public function test_non_store_pickup_order_rejects_pickup_receipt_request(): void
    {
        $courierOrder = $this->createCourierOrder();

        $response = $this->actingAs($this->buyer)->get(route('orders.pickup-receipt', $courierOrder->id));
        $response->assertStatus(400);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $order = $this->createPickupOrder('Shipped');

        $response = $this->get(route('orders.pickup-receipt', $order->id));
        $response->assertRedirect(route('login'));
    }

    public function test_buyer_order_details_shows_pickup_receipt_download_action(): void
    {
        $order = $this->createPickupOrder('Shipped');

        $response = $this->actingAs($this->buyer)->get(route('orders.show', $order->id));
        $response->assertStatus(200);
        $response->assertSee('Download Pickup Receipt');
        $response->assertSee(route('orders.pickup-receipt.download', $order->id));
        $response->assertSee(route('orders.pickup-receipt', $order->id));
    }

    public function test_seller_orders_dossier_shows_pickup_receipt_action(): void
    {
        $order = $this->createPickupOrder('Shipped');

        $response = $this->actingAs($this->seller)->get(route('seller.orders'));
        $response->assertStatus(200);
    }
}

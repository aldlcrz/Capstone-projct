<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipping;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ShippingProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StandardDeliverySellerAndCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected User $customer;
    protected Product $product;
    protected ShippingProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'id'                => (string) Str::uuid(),
            'name'              => 'Artisan Seller',
            'shopName'          => 'Lumban Fine Embroidery',
            'role'              => 'seller',
            'status'            => 'active',
            'isVerified'        => true,
            'email_verified_at' => now(),
        ]);

        $this->customer = User::factory()->create([
            'id'     => (string) Str::uuid(),
            'name'   => 'Customer Buyer',
            'role'   => 'customer',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'id'          => (string) Str::uuid(),
            'sellerId'    => $this->seller->id,
            'name'        => 'Barong Tagalog Classic',
            'description' => 'Classic artisan barong',
            'price'       => 2500.00,
            'stock'       => 10,
            'status'      => 'approved',
        ]);

        $this->provider = ShippingProvider::create([
            'id'              => (string) Str::uuid(),
            'name'            => 'Standard Delivery (J&T Express)',
            'code'            => 'jnt',
            'type'            => 'courier',
            'is_active'       => true,
            'origin_zone'     => 'Laguna',
        ]);
    }

    /** @test */
    public function seller_can_view_incoming_standard_delivery_order_in_orders_dashboard()
    {
        $orderId = (string) Str::uuid();

        // Simulate order placed via CreateOrderService with Standard Delivery direct payment
        $order = Order::create([
            'id'                      => $orderId,
            'customerId'              => $this->customer->id,
            'sellerId'                => $this->seller->id,
            'totalAmount'             => 2680.00,
            'total_verified_payments' => 2680.00,
            'status'                  => 'Pending',
            'fund_holder'             => 'seller',
            'paymentMethod'           => 'GCash',
            'paymentReference'        => '1009988776655',
            'paymentStatus'           => 'Paid (Direct to Seller)',
            'courierName'             => 'Standard Delivery',
            'shippingAddress'         => [
                'recipientName' => 'Customer Buyer',
                'houseNo'       => '123',
                'street'        => 'Ayala Ave',
                'barangay'      => 'San Lorenzo',
                'city'          => 'Makati',
                'province'      => 'Metro Manila',
                'postalCode'    => '1223',
                'phone'         => '09171234567',
            ],
            'createdAt'               => now(),
        ]);

        OrderShipping::create([
            'id'                       => (string) Str::uuid(),
            'order_id'                 => $order->id,
            'provider_id'              => $this->provider->id,
            'provider_name'            => 'Standard Delivery',
            'pricing_provider_id'      => $this->provider->id,
            'pricing_provider_name'    => 'Standard Delivery',
            'fulfillment_provider_id'  => $this->provider->id,
            'fulfillment_provider_name'=> 'Standard Delivery',
            'origin_zone_name'         => 'Lumban Origin',
            'destination_zone_name'    => 'Metro Manila',
            'actual_weight'            => 0.5,
            'volumetric_weight'        => 0.5,
            'chargeable_weight'        => 0.5,
            'rate_base_snapshot'       => 180.00,
            'additional_weight_rate_snapshot' => 0.0,
            'volumetric_divisor_snapshot'     => 3500,
            'estimated_days_min'       => 2,
            'estimated_days_max'       => 4,
            'shipping_fee'             => 180.00,
            'shipping_status'          => 'Pending',
        ]);

        OrderItem::create([
            'id'        => (string) Str::uuid(),
            'orderId'   => $order->id,
            'productId' => $this->product->id,
            'quantity'  => 1,
            'price'     => 2500.00,
        ]);

        // Seller views their orders page
        $sellerRes = $this->actingAs($this->seller)->get(route('seller.orders'));
        $sellerRes->assertStatus(200);
        $sellerRes->assertSee($order->id);

        // Seller views the pending tab
        $sellerPendingRes = $this->actingAs($this->seller)->get(route('seller.orders', ['status' => 'pending']));
        $sellerPendingRes->assertStatus(200);
        $sellerPendingRes->assertSee($order->id);

        // Customer views order detail (/orders/{id}) without 500 error
        $customerDetailRes = $this->actingAs($this->customer)->get(route('orders.show', $order->id));
        $customerDetailRes->assertStatus(200);
        $customerDetailRes->assertSee('Standard Shipping');
        $customerDetailRes->assertSee('Barong Tagalog Classic');
    }
}

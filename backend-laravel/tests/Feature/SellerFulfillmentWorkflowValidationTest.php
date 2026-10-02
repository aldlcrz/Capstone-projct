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

class SellerFulfillmentWorkflowValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
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

        $this->buyer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Maria Clara',
            'email' => 'maria@customer.test',
            'status' => 'active',
            'isVerified' => true,
            'email_verified_at' => now(),
        ]);

        $this->category = Category::create([
            'name' => 'Barong Tagalog',
            'description' => 'Formal Wear',
        ]);

        $this->product = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Embroidered Barong Tagalog',
            'description' => 'Handcrafted embroidered barong',
            'price' => 4500.00,
            'stock' => 15,
            'status' => 'active',
            'images' => ['products/barong1.jpg'],
        ]);
    }

    protected function createOrder(string $fulfillmentType, string $paymentMethod = 'GCASH', string $initialStatus = 'To Ship'): Order
    {
        $courierName = match ($fulfillmentType) {
            'store_pickup' => 'Store Pickup',
            'special_delivery' => 'Special Delivery (Local Artisan Rider)',
            default => 'J&T Express',
        };

        return Order::create([
            'id' => (string) Str::uuid(),
            'orderID' => 'LB-' . strtoupper(Str::random(8)),
            'customerId' => $this->buyer->id,
            'sellerId' => $this->seller->id,
            'shippingAddress' => json_encode([
                'fullName' => 'Maria Clara',
                'phone' => '09171234567',
                'address' => 'Barangay Sto. Nino',
                'city' => 'Lumban',
                'province' => 'Laguna',
            ]),
            'totalAmount' => 4500.00,
            'shippingFee' => $fulfillmentType === 'store_pickup' ? 0.00 : 150.00,
            'status' => $initialStatus,
            'paymentStatus' => $paymentMethod === 'COD' ? 'unpaid' : 'paid',
            'paymentMethod' => $paymentMethod,
            'courierName' => $courierName,
        ]);
    }

    public function test_store_pickup_complete_workflow(): void
    {
        // 1. Order Placed in To Ship (Prepare stage)
        $order = $this->createOrder('store_pickup', 'COD', 'To Ship');
        $this->assertTrue($order->isStorePickup());
        $this->assertFalse($order->isSpecialDelivery());

        // 2. Seller prepares order and marks Ready for Pickup (Shipped)
        $response = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'Shipped',
        ]);
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('Shipped', $order->status);
        $this->assertNull($order->trackingNumber);

        // 3. Pickup Receipt is accessible
        $receiptResponse = $this->actingAs($this->seller)->get(route('seller.orders.pickup-receipt', $order->id));
        $receiptResponse->assertStatus(200);

        // 4. Store pickup rejects courier in-transit transition
        $inTransitAttempt = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'In Transit',
        ]);
        $inTransitAttempt->assertStatus(400);

        // 5. Buyer Claims at Workshop -> Seller marks Delivered (Picked Up / Claimed)
        $claimResponse = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'Delivered',
        ]);
        $claimResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals('Delivered', $order->status);

        // 6. Customer confirms claim -> Completed
        $confirmResponse = $this->actingAs($this->buyer)->patchJson('/api/orders/' . $order->id . '/status', [
            'status' => 'Completed',
        ]);
        $confirmResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals('Completed', $order->status);
    }

    public function test_courier_delivery_complete_workflow(): void
    {
        // 1. Order Placed in To Ship
        $order = $this->createOrder('courier', 'GCASH', 'To Ship');
        $this->assertFalse($order->isStorePickup());
        $this->assertFalse($order->isSpecialDelivery());

        // 2. Seller marks Shipped
        $response = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'Shipped',
            'courierName' => 'J&T Express',
        ]);
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('Shipped', $order->status);

        // 3. Transition to In Transit strictly requires tracking number
        $missingTracking = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'In Transit',
            'courierName' => 'J&T Express',
            'trackingNumber' => '',
        ]);
        $missingTracking->assertStatus(422);

        // 4. Valid tracking number advances to In Transit
        $validInTransit = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'In Transit',
            'courierName' => 'J&T Express',
            'trackingNumber' => 'JT9876543210',
            'trackingLink' => 'https://www.jtexpress.ph/track',
        ]);
        $validInTransit->assertStatus(200);
        $order->refresh();
        $this->assertEquals('In Transit', $order->status);
        $this->assertEquals('JT9876543210', $order->trackingNumber);

        // 5. Courier delivers -> Delivered
        $delivered = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'Delivered',
        ]);
        $delivered->assertStatus(200);
        $order->refresh();
        $this->assertEquals('Delivered', $order->status);

        // 6. Customer confirms receipt -> Completed
        $confirm = $this->actingAs($this->buyer)->patchJson('/api/orders/' . $order->id . '/status', [
            'status' => 'Completed',
        ]);
        $confirm->assertStatus(200);
        $order->refresh();
        $this->assertEquals('Completed', $order->status);
    }

    public function test_special_delivery_complete_workflow(): void
    {
        // 1. Order Placed in To Ship
        $order = $this->createOrder('special_delivery', 'MAYA', 'To Ship');
        $this->assertFalse($order->isStorePickup());
        $this->assertTrue($order->isSpecialDelivery());

        // 2. Seller marks Shipped (Special Delivery Processing)
        $shipped = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'Shipped',
        ]);
        $shipped->assertStatus(200);
        $order->refresh();
        $this->assertEquals('Shipped', $order->status);

        // 3. Dispatched to Artisan Rider -> Out for Special Delivery (In Transit without 3rd party tracking)
        $outForDelivery = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'In Transit',
        ]);
        $outForDelivery->assertStatus(200);
        $order->refresh();
        $this->assertEquals('In Transit', $order->status);
        $this->assertEquals('Special Delivery (Local Artisan Rider)', $order->courierName);

        // 4. Artisan Rider drops off -> Delivered
        $delivered = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'Delivered',
        ]);
        $delivered->assertStatus(200);
        $order->refresh();
        $this->assertEquals('Delivered', $order->status);

        // 5. Customer confirms receipt -> Completed
        $confirm = $this->actingAs($this->buyer)->patchJson('/api/orders/' . $order->id . '/status', [
            'status' => 'Completed',
        ]);
        $confirm->assertStatus(200);
        $order->refresh();
        $this->assertEquals('Completed', $order->status);
    }

    public function test_fulfillment_operates_independently_from_payment_methods(): void
    {
        // Store Pickup with GCASH
        $p1 = $this->createOrder('store_pickup', 'GCASH', 'To Ship');
        $this->assertTrue($p1->isStorePickup());
        $this->assertEquals('GCASH', $p1->paymentMethod);

        // Special Delivery with COD
        $p2 = $this->createOrder('special_delivery', 'COD', 'To Ship');
        $this->assertTrue($p2->isSpecialDelivery());
        $this->assertEquals('COD', $p2->paymentMethod);

        // Courier with MAYA
        $p3 = $this->createOrder('courier', 'MAYA', 'To Ship');
        $this->assertFalse($p3->isStorePickup());
        $this->assertFalse($p3->isSpecialDelivery());
        $this->assertEquals('MAYA', $p3->paymentMethod);
    }
}

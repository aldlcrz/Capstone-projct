<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ShippingLogisticsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerOrderDetailsSpecialDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $seller;
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

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Maria Clara',
            'email' => 'maria@lumban.test',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'name' => 'Artisan Maestro',
            'shopName' => 'Lumban Heritage Bordado',
            'shopCity' => 'Lumban',
            'shopProvince' => 'Laguna',
            'shopPostalCode' => '4014',
            'email' => 'artisan@lumban.test',
            'status' => 'approved',
            'isVerified' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Barong Tagalog',
            'slug' => 'barong-tagalog',
        ]);

        $this->product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Hand-Embroidered Piña Barong',
            'description' => 'Traditional handcrafted barong',
            'price' => 4500.00,
            'stock' => 5,
            'status' => 'approved',
            'image' => ['barong_test.jpg'],
        ]);
    }

    /**
     * 1. Artisan Inspection Proof section is absent for Special Delivery.
     */
    public function test_artisan_inspection_proof_is_absent_for_special_delivery(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 4600.00,
            'shippingFee' => 100.00,
            'status' => 'In Transit',
            'courierName' => 'Special Delivery (Local Artisan Rider)',
            'paymentMethod' => 'Special Delivery',
            'shippingAddress' => [
                'name' => 'Maria Clara',
                'phone' => '09171234567',
                'street' => 'Rizal Street, Poblacion',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postalCode' => '4014',
            ],
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 4500.00,
            'size' => 'Medium',
        ]);

        $response = $this->actingAs($this->customer)->get('/orders/' . $order->id);

        $response->assertStatus(200);
        $this->assertTrue($order->isSpecialDelivery());

        // Artisan inspection proof, photo pending, packing proof instructions MUST be absent
        $response->assertDontSee('Artisan inspection proof');
        $response->assertDontSee('Photo pending');
        $response->assertDontSee('Artisan will upload quality inspection photo before dispatch');
    }

    /**
     * 2. Missing inspection proof does not prevent Special Delivery workflow or customer receipt confirmation.
     */
    public function test_missing_inspection_proof_does_not_prevent_special_delivery_confirmation(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 4600.00,
            'shippingFee' => 100.00,
            'status' => 'Delivered',
            'packingProof' => null, // No inspection proof uploaded
            'courierName' => 'Special Delivery (Local Artisan Rider)',
            'paymentMethod' => 'Special Delivery',
            'shippingAddress' => [
                'name' => 'Maria Clara',
                'phone' => '09171234567',
                'street' => 'Rizal Street, Poblacion',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postalCode' => '4014',
            ],
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 4500.00,
            'size' => 'Medium',
        ]);

        // Customer confirms receipt via PATCH endpoint
        $response = $this->actingAs($this->customer)->patch('/orders/' . $order->id . '/confirm');

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals('Completed', $order->status);
        $this->assertTrue($order->isReceivedByCustomer());
    }

    /**
     * 3. Required inspection-proof section remains present for standard courier shipments.
     */
    public function test_artisan_inspection_proof_remains_for_standard_courier_orders(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 4650.00,
            'shippingFee' => 150.00,
            'status' => 'In Transit',
            'courierName' => 'J&T Express',
            'trackingNumber' => '781234567890',
            'trackingLink' => 'https://www.jtexpress.ph/track',
            'paymentMethod' => 'COD',
            'shippingAddress' => [
                'name' => 'Maria Clara',
                'phone' => '09171234567',
                'street' => 'Ayala Avenue',
                'city' => 'Makati',
                'province' => 'Metro Manila',
                'postalCode' => '1226',
            ],
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 4500.00,
            'size' => 'Medium',
        ]);

        $response = $this->actingAs($this->customer)->get('/orders/' . $order->id);

        $response->assertStatus(200);
        $this->assertFalse($order->isSpecialDelivery());

        // Artisan inspection proof section MUST be present for standard courier
        $response->assertSee('Artisan inspection proof');
        $response->assertSee('Photo pending');
    }

    /**
     * 4. Only ONE primary Chat With Artisan button renders on the page for a given seller context.
     */
    public function test_only_one_chat_with_artisan_button_renders(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 4600.00,
            'shippingFee' => 100.00,
            'status' => 'In Transit',
            'courierName' => 'Special Delivery (Local Artisan Rider)',
            'paymentMethod' => 'Special Delivery',
            'shippingAddress' => [
                'name' => 'Maria Clara',
                'phone' => '09171234567',
                'street' => 'Rizal Street, Poblacion',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postalCode' => '4014',
            ],
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 4500.00,
            'size' => 'Medium',
        ]);

        $response = $this->actingAs($this->customer)->get('/orders/' . $order->id);

        $response->assertStatus(200);

        // Assert exactly one open-chat trigger is rendered on the page
        $content = $response->getContent();
        $chatOccurrences = substr_count($content, "CustomEvent('open-chat'");
        $this->assertEquals(1, $chatOccurrences, "Expected exactly one open-chat dispatch trigger, found: {$chatOccurrences}");

        // Duplicate "Message Artisan" from notification banner must NOT be present
        $response->assertDontSee('Message Artisan');
        // Consolidated "Chat with artisan" in Sold by Artisan card MUST be present
        $response->assertSee('Chat with artisan');
    }

    /**
     * 5. Repeated Special Delivery wording is reduced.
     */
    public function test_reduced_repetitive_special_delivery_wording(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 4600.00,
            'shippingFee' => 100.00,
            'status' => 'In Transit',
            'courierName' => 'Special Delivery (Local Artisan Rider)',
            'paymentMethod' => 'Special Delivery',
            'shippingAddress' => [
                'name' => 'Maria Clara',
                'phone' => '09171234567',
                'street' => 'Rizal Street, Poblacion',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postalCode' => '4014',
            ],
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 4500.00,
            'size' => 'Medium',
        ]);

        $response = $this->actingAs($this->customer)->get('/orders/' . $order->id);

        $response->assertStatus(200);

        // Repetitive banners should be replaced with concise contextual labels
        $response->assertDontSee('Special Delivery Fulfillment Progress &bull; Out for Special Delivery');
        $response->assertSee('Local artisan rider');
        $response->assertSee('Pay to artisan rider');

        // When delivered / completed:
        $order->status = 'Delivered';
        $order->save();

        $deliveredResponse = $this->actingAs($this->customer)->get('/orders/' . $order->id);
        $deliveredResponse->assertStatus(200);
        $deliveredResponse->assertDontSee('Special delivery completed');
        $deliveredResponse->assertSee('Delivery completed');
        $deliveredResponse->assertSee('Please inspect your order and confirm receipt.');
    }

    /**
     * 6. Report Product is hidden until customer confirms receipt (Completed state).
     */
    public function test_report_product_hidden_while_in_transit_or_delivered_unconfirmed(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 4600.00,
            'shippingFee' => 100.00,
            'status' => 'In Transit',
            'courierName' => 'Special Delivery (Local Artisan Rider)',
            'paymentMethod' => 'Special Delivery',
            'shippingAddress' => [
                'name' => 'Maria Clara',
                'phone' => '09171234567',
                'street' => 'Rizal Street, Poblacion',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postalCode' => '4014',
            ],
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 4500.00,
            'size' => 'Medium',
        ]);

        // While in transit:
        $resInTransit = $this->actingAs($this->customer)->get('/orders/' . $order->id);
        $resInTransit->assertStatus(200);
        $resInTransit->assertDontSee('Report Product');

        // While delivered but awaiting customer receipt confirmation:
        $order->status = 'Delivered';
        $order->save();

        $resDelivered = $this->actingAs($this->customer)->get('/orders/' . $order->id);
        $resDelivered->assertStatus(200);
        $resDelivered->assertDontSee('Report Product');

        // Once confirmed received (Completed state):
        $order->status = 'Completed';
        $order->save();

        $resCompleted = $this->actingAs($this->customer)->get('/orders/' . $order->id);
        $resCompleted->assertStatus(200);
        $resCompleted->assertSee('Report Product');
    }
}

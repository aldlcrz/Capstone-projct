<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CreateOrderService;
use Database\Seeders\ShippingLogisticsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomaticSellerPurchaseMessageTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\SystemSetting::firstOrCreate(
            ['key' => 'maintenanceMode'],
            ['value' => 'false']
        );

        $this->seed(ShippingLogisticsSeeder::class);

        $this->category = Category::create([
            'name' => 'Barong Tagalog',
            'description' => 'Authentic Lumban Barongs',
        ]);
    }

    public function test_automatic_seller_message_is_sent_to_buyer_after_successful_purchase(): void
    {
        // 1. Setup Seller and Buyer
        $seller = User::factory()->create([
            'role' => 'seller',
            'name' => 'Lumban Master Weaver',
            'shopName' => 'Lumban Heritage Crafts',
            'shopCity' => 'Lumban',
            'shopProvince' => 'Laguna',
            'shopPostalCode' => '4014',
            'email' => 'artisan@lumban.test',
        ]);

        $buyer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Maria Clara',
            'email' => 'maria@customer.test',
        ]);

        // 2. Setup Product
        $product = Product::create([
            'sellerId' => $seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Barong Tagalog Piña Special',
            'description' => 'Fine piña hand embroidery',
            'price' => 4500.00,
            'costPerPiece' => 2000.00,
            'stock' => 10,
            'status' => 'approved',
            'image' => '/uploads/products/barong-pina.jpg',
        ]);

        // 3. Create Order via Canonical CreateOrderService
        /** @var CreateOrderService $createOrderService */
        $createOrderService = app(CreateOrderService::class);

        $order = $createOrderService->createOrder([
            'customer' => $buyer,
            'sellerId' => $seller->id,
            'items' => [
                [
                    'productId' => $product->id,
                    'quantity' => 2,
                    'price' => 4500.00,
                    'variation' => 'Medium / Natural',
                    'size' => 'M',
                ]
            ],
            'shippingAddress' => [
                'full_name' => 'Maria Clara',
                'phone' => '09123456789',
                'street' => '123 Calle Crisostomo',
                'barangay' => 'Poblacion',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postal_code' => '4014',
            ],
            'paymentMethod' => 'COD',
            'deliveryMethod' => 'Standard Delivery',
            'shippingFee' => 150.00,
            'specialDiscount' => 0.00,
        ]);

        // 4. Assert Order exists
        $this->assertNotNull($order);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'customerId' => $buyer->id, 'sellerId' => $seller->id]);

        // 5. Assert Automatic Seller Message was created
        $message = Message::where('senderId', $seller->id)
            ->where('receiverId', $buyer->id)
            ->first();

        $this->assertNotNull($message, 'Automatic seller purchase message was not found.');

        // 6. Verify trusted details in message content
        // - Product Name
        $this->assertStringContainsString('Barong Tagalog Piña Special', $message->content);
        // - Variant
        $this->assertStringContainsString('Medium / Natural', $message->content);
        // - Quantity
        $this->assertStringContainsString('Qty: 2', $message->content);
        // - Order Number / ID
        $orderShortId = strtoupper(substr($order->id, -8));
        $this->assertStringContainsString($orderShortId, $message->content);
        // - Seller / Shop name
        $this->assertStringContainsString('Lumban Heritage Crafts', $message->content);
        // - Purchase date & Order status
        $this->assertStringContainsString('Purchase Date:', $message->content);
        $this->assertStringContainsString('Order Status:', $message->content);
        // - Product Image markdown
        $this->assertStringContainsString('/uploads/products/barong-pina.jpg', $message->content);
        // - Clickable order link
        $this->assertStringContainsString("/orders/{$order->id}", $message->content);
    }

    public function test_automatic_purchase_message_is_idempotent(): void
    {
        // Setup
        $seller = User::factory()->create([
            'role' => 'seller',
            'shopName' => 'Laguna Silks',
            'shopCity' => 'Lumban',
            'shopProvince' => 'Laguna',
            'shopPostalCode' => '4014',
        ]);
        $buyer = User::factory()->create(['role' => 'customer']);
        $product = Product::create([
            'sellerId' => $seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Formal Jusi Barong',
            'description' => 'Classic Jusi',
            'price' => 2800.00,
            'costPerPiece' => 1200.00,
            'stock' => 15,
            'status' => 'approved',
        ]);

        /** @var CreateOrderService $createOrderService */
        $createOrderService = app(CreateOrderService::class);

        $order = $createOrderService->createOrder([
            'customer' => $buyer,
            'sellerId' => $seller->id,
            'items' => [
                [
                    'productId' => $product->id,
                    'quantity' => 1,
                    'price' => 2800.00,
                ]
            ],
            'shippingAddress' => [
                'full_name' => 'Buyer Name',
                'phone' => '09123456789',
                'street' => 'Street 1',
                'barangay' => 'Barangay 1',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postal_code' => '4014',
            ],
            'paymentMethod' => 'COD',
            'deliveryMethod' => 'Standard Delivery',
            'shippingFee' => 100.00,
        ]);

        $initialCount = Message::where('senderId', $seller->id)->where('receiverId', $buyer->id)->count();
        $this->assertEquals(1, $initialCount);

        // Simulate re-running post-order notifications or duplicate event dispatch
        $createOrderService->createOrder([
            'idempotencyKey' => 'duplicate-attempt-key',
            'customer' => $buyer,
            'sellerId' => $seller->id,
            'items' => [
                [
                    'productId' => $product->id,
                    'quantity' => 1,
                    'price' => 2800.00,
                ]
            ],
            'shippingAddress' => [
                'full_name' => 'Buyer Name',
                'phone' => '09123456789',
                'street' => 'Street 1',
                'barangay' => 'Barangay 1',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postal_code' => '4014',
            ],
            'paymentMethod' => 'COD',
            'deliveryMethod' => 'Standard Delivery',
            'shippingFee' => 100.00,
        ]);

        // Second order creates 1 for its own order, but does not duplicate the first order's message
        $messagesForFirstOrder = Message::where('senderId', $seller->id)
            ->where('receiverId', $buyer->id)
            ->where('content', 'like', "%[order:{$order->id}]%")
            ->count();

        $this->assertEquals(1, $messagesForFirstOrder, 'First order message was duplicated.');
    }

    public function test_buyer_and_seller_chat_endpoints_return_automatic_purchase_message(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'shopName' => 'Bordado Specialists',
            'shopCity' => 'Lumban',
            'shopProvince' => 'Laguna',
            'shopPostalCode' => '4014',
        ]);
        $buyer = User::factory()->create(['role' => 'customer']);
        $product = Product::create([
            'sellerId' => $seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Traditional Sukob Barong',
            'description' => 'Authentic embroidery',
            'price' => 3200.00,
            'costPerPiece' => 1500.00,
            'stock' => 5,
            'status' => 'approved',
        ]);

        /** @var CreateOrderService $createOrderService */
        $createOrderService = app(CreateOrderService::class);

        $order = $createOrderService->createOrder([
            'customer' => $buyer,
            'sellerId' => $seller->id,
            'items' => [
                [
                    'productId' => $product->id,
                    'quantity' => 1,
                    'price' => 3200.00,
                ]
            ],
            'shippingAddress' => [
                'full_name' => 'Buyer Name',
                'phone' => '09123456789',
                'street' => 'Street 1',
                'barangay' => 'Barangay 1',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postal_code' => '4014',
            ],
            'paymentMethod' => 'COD',
            'deliveryMethod' => 'Standard Delivery',
            'shippingFee' => 100.00,
        ]);

        // Buyer loads messages with this seller
        $buyerResponse = $this->actingAs($buyer)->getJson('/chat/messages/' . $seller->id);
        $buyerResponse->assertStatus(200);
        $buyerResponse->assertJsonFragment([
            'senderId' => $seller->id,
            'receiverId' => $buyer->id,
        ]);

        // Buyer loads conversations list (shows shop name)
        $convResponse = $this->actingAs($buyer)->getJson('/chat/conversations');
        $convResponse->assertStatus(200);
        $convResponse->assertJsonFragment([
            'name' => 'Bordado Specialists',
        ]);

        // Seller loads messages with buyer
        $sellerResponse = $this->actingAs($seller)->getJson('/api/chat/conversation/' . $buyer->id);
        $sellerResponse->assertStatus(200);
        $sellerResponse->assertJsonFragment([
            'senderId' => $seller->id,
            'receiverId' => $buyer->id,
        ]);
    }
}

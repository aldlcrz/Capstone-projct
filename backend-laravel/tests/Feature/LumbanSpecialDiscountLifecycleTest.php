<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Address;
use App\Services\CreateOrderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LumbanSpecialDiscountLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected User $customer;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'active',
            'isVerified' => true,
            'shopName' => 'Lumban Heritage Embroidery',
            'shopCity' => 'Lumban',
            'shopProvince' => 'Laguna',
            'shopPostalCode' => '4014',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Barong Tagalog',
            'description' => 'Traditional Philippine Barong Tagalog',
        ]);

        $this->seed(\Database\Seeders\ShippingLogisticsSeeder::class);
    }

    public function test_active_discount_product_is_visible_in_lumban_special()
    {
        $activeProduct = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Active Discount Barong',
            'description' => 'Handmade Barong',
            'price' => 1000.00,
            'costPerPiece' => 500.00,
            'stock' => 10,
            'status' => 'approved',
            'is_on_sale' => true,
            'discount_percentage' => 20,
            'sale_duration' => '1_week',
            'sale_ends_at' => Carbon::now()->addDays(3),
        ]);

        $this->assertTrue($activeProduct->isSaleActive());
        $this->assertTrue($activeProduct->is_on_sale);
        $this->assertEquals(800.00, $activeProduct->sale_price);

        // Query WebController Lumban Special
        $response = $this->get('/?sort=lumban_special');
        $response->assertStatus(200);
        $viewProducts = $response->viewData('products');
        $this->assertTrue($viewProducts->contains('id', $activeProduct->id));

        $responseAlt = $this->get('/?lumban_special=1');
        $responseAlt->assertStatus(200);
        $viewProductsAlt = $responseAlt->viewData('products');
        $this->assertTrue($viewProductsAlt->contains('id', $activeProduct->id));
    }

    public function test_expired_discount_product_is_not_visible_in_lumban_special()
    {
        $expiredProduct = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Expired Discount Barong',
            'description' => 'Handmade Barong',
            'price' => 1000.00,
            'costPerPiece' => 500.00,
            'stock' => 10,
            'status' => 'approved',
            'is_on_sale' => true,
            'discount_percentage' => 20,
            'sale_duration' => '1_week',
            'sale_ends_at' => Carbon::now()->subHour(),
        ]);

        $this->assertFalse($expiredProduct->isSaleActive());
        $this->assertFalse($expiredProduct->is_on_sale);
        $this->assertEquals(1000.00, $expiredProduct->sale_price);

        // Query WebController Lumban Special
        $response = $this->get('/?sort=lumban_special');
        $response->assertStatus(200);
        $viewProducts = $response->viewData('products');
        $this->assertFalse($viewProducts->contains('id', $expiredProduct->id));

        $responseAlt = $this->get('/?lumban_special=1');
        $responseAlt->assertStatus(200);
        $viewProductsAlt = $responseAlt->viewData('products');
        $this->assertFalse($viewProductsAlt->contains('id', $expiredProduct->id));
    }

    public function test_future_discount_product_is_not_visible_in_lumban_special()
    {
        $futureProduct = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Future Discount Barong',
            'description' => 'Handmade Barong',
            'price' => 1200.00,
            'costPerPiece' => 600.00,
            'stock' => 10,
            'status' => 'approved',
            'is_on_sale' => false,
            'discount_percentage' => 20,
            'sale_duration' => '1_week',
            'sale_ends_at' => Carbon::now()->addDays(7),
        ]);

        $this->assertFalse($futureProduct->isSaleActive());
        $this->assertFalse($futureProduct->is_on_sale);
        $this->assertEquals(1200.00, $futureProduct->sale_price);

        $response = $this->get('/?sort=lumban_special');
        $response->assertStatus(200);
        $viewProducts = $response->viewData('products');
        $this->assertFalse($viewProducts->contains('id', $futureProduct->id));
    }

    public function test_boundary_discount_expiring_exactly_at_current_time_is_not_active()
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 2, 12, 0, 0));

        $boundaryProduct = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Boundary Expiring Barong',
            'description' => 'Handmade Barong',
            'price' => 2000.00,
            'costPerPiece' => 1000.00,
            'stock' => 5,
            'status' => 'approved',
            'is_on_sale' => true,
            'discount_percentage' => 15,
            'sale_duration' => '1_day',
            'sale_ends_at' => Carbon::now(), // Exactly now
        ]);

        $this->assertFalse($boundaryProduct->isSaleActive());
        $this->assertFalse($boundaryProduct->is_on_sale);
        $this->assertEquals(2000.00, $boundaryProduct->sale_price);

        $response = $this->get('/?sort=lumban_special');
        $response->assertStatus(200);
        $viewProducts = $response->viewData('products');
        $this->assertFalse($viewProducts->contains('id', $boundaryProduct->id));

        Carbon::setTestNow(null);
    }

    public function test_api_products_endpoint_returns_only_valid_discounts_in_lumban_special()
    {
        $activeProduct = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'API Active Discount Barong',
            'description' => 'Active sale',
            'price' => 1500.00,
            'costPerPiece' => 800.00,
            'stock' => 10,
            'status' => 'approved',
            'is_on_sale' => true,
            'discount_percentage' => 10,
            'sale_duration' => '1_week',
            'sale_ends_at' => Carbon::now()->addDays(5),
        ]);

        $expiredProduct = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'API Expired Discount Barong',
            'description' => 'Expired sale',
            'price' => 1500.00,
            'costPerPiece' => 800.00,
            'stock' => 10,
            'status' => 'approved',
            'is_on_sale' => true,
            'discount_percentage' => 10,
            'sale_duration' => '1_week',
            'sale_ends_at' => Carbon::now()->subMinutes(10),
        ]);

        $response = $this->getJson('/api/v1/products?sort=lumban_special');
        $response->assertStatus(200);
        
        $data = $response->json();
        $productNames = collect($data)->pluck('name')->toArray();

        $this->assertContains('API Active Discount Barong', $productNames);
        $this->assertNotContains('API Expired Discount Barong', $productNames);

        // Individual product serialization check for expired product
        $expiredItemResponse = $this->getJson('/api/v1/products/' . $expiredProduct->id);
        $expiredItemResponse->assertStatus(200);
        $expiredItemData = $expiredItemResponse->json();
        $this->assertFalse($expiredItemData['is_on_sale']);
        $this->assertEquals(0, $expiredItemData['discount_percentage']);
        $this->assertEquals(1500.00, $expiredItemData['sale_price']);
    }

    public function test_checkout_recalculates_price_without_expired_discount()
    {
        $product = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Checkout Barong',
            'description' => 'Artisan Barong',
            'price' => 2000.00,
            'costPerPiece' => 1000.00,
            'stock' => 10,
            'status' => 'approved',
            'is_on_sale' => true,
            'discount_percentage' => 50, // 50% off -> 1000.00
            'sale_duration' => '1_week',
            'sale_ends_at' => Carbon::now()->addHour(),
        ]);

        $this->assertEquals(1000.00, $product->sale_price);

        // Add to cart while active
        $this->actingAs($this->customer);
        $cart = [
            'item_1' => [
                'key' => 'item_1',
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->sale_price,
                'original_price' => (float) $product->price,
                'discount_percentage' => 50,
                'is_on_sale' => true,
                'quantity' => 1,
                'size' => 'L',
                'variation' => null,
                'sellerId' => $this->seller->id,
            ]
        ];
        session(['cart' => $cart]);

        // Fast-forward time past sale expiration
        Carbon::setTestNow(Carbon::now()->addHours(2));

        // Refreshed product in DB
        $product->refresh();
        $this->assertFalse($product->isSaleActive());

        // Address for checkout
        $address = Address::create([
            'userId' => $this->customer->id,
            'recipientName' => 'Maria Clara',
            'phone' => '09123456789',
            'houseNo' => '123',
            'street' => 'Rizal St.',
            'barangay' => 'Poblacion',
            'city' => 'Lumban',
            'province' => 'Laguna',
            'region' => 'Region IV-A (CALABARZON)',
            'postalCode' => '4014',
            'isDefault' => true,
        ]);

        // Place order via CreateOrderService
        /** @var CreateOrderService $createOrderService */
        $createOrderService = app(CreateOrderService::class);
        $order = $createOrderService->createOrder([
            'customer' => $this->customer,
            'address_id' => $address->id,
            'paymentMethod' => 'GCash',
            'paymentReference' => '1234567890123', // 13-digit valid GCash reference
            'items' => [
                [
                    'productId' => $product->id,
                    'quantity' => 1,
                    'size' => 'L',
                ]
            ],
        ]);

        // Authoritative order item price must be 2000.00 (original price), NOT 1000.00 (expired sale price)
        $this->assertEquals(2000.00, (float)$order->items->first()->price);
        $this->assertEquals(2000.00 + (float)$order->shippingFee, (float)$order->totalAmount);

        Carbon::setTestNow(null);
    }
}

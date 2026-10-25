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

class SellerAnalyticsTest extends TestCase
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
            'name' => 'Piña Barong Special',
            'description' => 'Authentic hand-embroidered barong',
            'price' => 2500,
            'stock' => 10,
            'status' => 'active',
        ]);
    }

    public function test_seller_analytics_page_renders_successfully_for_authenticated_seller(): void
    {
        $response = $this->actingAs($this->seller)->get(route('seller.analytics'));
        $response->assertStatus(200);
    }

    public function test_seller_analytics_with_orders_and_size_breakdown(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'orderNumber' => 'ORD-TEST-001',
            'sellerId' => $this->seller->id,
            'customerId' => $this->buyer->id,
            'shippingAddress' => '123 Lumban St, Laguna',
            'totalAmount' => 5000,
            'status' => 'completed',
            'paymentStatus' => 'Paid',
            'paymentMethod' => 'COD',
            'shippingFee' => 150,
            'discount_amount' => 0,
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'quantity' => 2,
            'price' => 2500,
            'size' => 'L',
        ]);

        $response = $this->actingAs($this->seller)->get(route('seller.analytics'));
        $response->assertStatus(200);
        $response->assertSee('Garment Size Distribution');
        $response->assertSee('L');
    }

    public function test_seller_analytics_for_brand_new_seller_with_no_products_and_no_orders(): void
    {
        $newSeller = User::factory()->create([
            'role' => 'seller',
            'status' => 'approved',
            'isVerified' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($newSeller)->get(route('seller.analytics'));
        $response->assertStatus(200);
        $response->assertSee('No size-specific sales data recorded yet.');
        $response->assertSee('No products recorded yet.');
    }

    public function test_seller_analytics_json_format_returns_valid_structure(): void
    {
        $response = $this->actingAs($this->seller)->get(route('seller.analytics', ['format' => 'json']));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'filters',
            'salesAnalytics' => ['totalSales', 'grossSales', 'netSales'],
            'orderAnalytics' => ['stats', 'completionRate'],
            'productAnalytics' => ['all', 'bestSelling', 'overallSizeBreakdown'],
            'customerAnalytics' => ['totalCustomers', 'funnel'],
            'categorySales',
            'financialAnalytics',
            'marketingAnalytics',
            'salesTrendChart',
        ]);
    }

    public function test_seller_analytics_date_presets_do_not_crash(): void
    {
        foreach (['today', '1_week', '1_month', '1_year', 'all_time'] as $preset) {
            $response = $this->actingAs($this->seller)->get(route('seller.analytics', ['date_preset' => $preset]));
            $response->assertStatus(200);
        }
    }

    public function test_add_to_cart_increments_customer_funnel_and_product_analytics(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'approved',
            'isVerified' => true,
        ]);

        // Initial analytics call
        $initialRes = $this->actingAs($this->seller)->get(route('seller.analytics', ['format' => 'json']));
        $initialRes->assertStatus(200);
        $initialCarts = $initialRes->json('customerAnalytics.funnel.addToCart');

        // Customer adds item to cart
        $cartAddRes = $this->actingAs($customer)->post(route('cart.add'), [
            'productId' => $this->product->id,
            'quantity' => 1,
            'size' => 'M',
        ]);
        $cartAddRes->assertSessionHasNoErrors();

        // Check analytics again
        $updatedRes = $this->actingAs($this->seller)->get(route('seller.analytics', ['format' => 'json']));
        $updatedRes->assertStatus(200);
        $updatedCarts = $updatedRes->json('customerAnalytics.funnel.addToCart');

        $this->assertGreaterThan($initialCarts, $updatedCarts);

        // Also check product table metrics
        $products = collect($updatedRes->json('productAnalytics.all'));
        $targetProduct = $products->firstWhere('id', $this->product->id);
        $this->assertNotNull($targetProduct);
        $this->assertGreaterThanOrEqual(1, $targetProduct['addToCart']);
    }
}

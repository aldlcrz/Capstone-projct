<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CreateOrderService;
use App\Support\CartHelper;
use Database\Seeders\ShippingLogisticsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryModeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected User $buyer;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        SystemSetting::firstOrCreate(
            ['key' => 'maintenanceMode'],
            ['value' => 'false']
        );

        $this->seed(ShippingLogisticsSeeder::class);
        Storage::fake('public');

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'approved',
            'isVerified' => true,
            'name' => 'Lumban Master Tailor',
            'shopName' => 'Lumban Atelier',
            'shopCity' => 'Lumban',
            'shopProvince' => 'Laguna',
            'shopPostalCode' => '4014',
            'gcashNumber' => '09171234567',
            'gcashQrCode' => 'uploads/qrcodes/seller_default_qr.png',
        ]);

        $this->buyer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@customer.test',
        ]);

        $this->category = Category::create([
            'name' => 'Barong Tagalog',
            'target_group' => ['Men'],
            'description' => 'Fine Lumban Barongs',
        ]);
    }

    public function test_seller_creates_preorder_product_forces_zero_stock_and_stores_handling_days(): void
    {
        $coverFile = UploadedFile::fake()->image('preorder_barong.jpg', 800, 800);

        $response = $this->actingAs($this->seller)->post(route('seller.products.store'), [
            'action' => 'publish',
            'name' => 'Custom Hand-Embroidered Preorder Barong',
            'description' => 'Bespoke piece meticulously handcrafted on order.',
            'price' => 6500,
            'package_weight_per_unit' => 0.5,
            'shippingFee' => 200,
            'shippingDays' => 7,
            'handling_days' => 14,
            'inventory_mode' => 'preorder',
            'CategoryId' => $this->category->id,
            'category_ids' => [$this->category->id],
            'target_group' => 'Men',
            'sizes' => ['S', 'M', 'L', 'XL'],
            'product_is_gcash_available' => '1',
            'gcashNumber' => '09171234567',
            'variant_indexes' => [0],
            'variant_names' => [0 => 'Pina-Seda Natural'],
            'variant_image_0' => $coverFile,
        ]);

        $response->assertRedirect(route('seller.products.index'));
        $response->assertSessionHas('success');

        $product = Product::where('name', 'Custom Hand-Embroidered Preorder Barong')->first();
        $this->assertNotNull($product);
        $this->assertEquals('preorder', $product->inventory_mode);
        $this->assertEquals(0, $product->stock, 'Preorder physical stock must strictly be 0.');
        $this->assertEmpty($product->size_stocks, 'Preorder size stocks must strictly be empty.');
        $this->assertEquals(14, $product->handling_days);
        $this->assertTrue($product->isPreorder());
        $this->assertFalse($product->isAvailableStock());

        // Approval enables purchaser access
        $product->status = 'approved';
        $product->save();
        $this->assertTrue($product->isPurchasable());
    }

    public function test_seller_creates_available_stock_product_computes_total_stock_from_size_stocks(): void
    {
        $coverFile = UploadedFile::fake()->image('ready_barong.jpg', 800, 800);

        $response = $this->actingAs($this->seller)->post(route('seller.products.store'), [
            'action' => 'publish',
            'name' => 'Ready-to-Wear Jusilyn Barong',
            'description' => 'Ready stock in physical inventory.',
            'price' => 2800,
            'package_weight_per_unit' => 0.5,
            'shippingFee' => 120,
            'shippingDays' => 3,
            'handling_days' => 2,
            'inventory_mode' => 'available_stock',
            'CategoryId' => $this->category->id,
            'category_ids' => [$this->category->id],
            'target_group' => 'Men',
            'sizes' => ['M', 'L'],
            'size_stocks' => ['M' => 4, 'L' => 6],
            'product_is_gcash_available' => '1',
            'gcashNumber' => '09171234567',
            'variant_indexes' => [0],
            'variant_names' => [0 => 'Ivory'],
            'variant_image_0' => $coverFile,
        ]);

        $response->assertRedirect(route('seller.products.index'));

        $product = Product::where('name', 'Ready-to-Wear Jusilyn Barong')->first();
        $this->assertNotNull($product);
        $this->assertEquals('available_stock', $product->inventory_mode);
        $this->assertEquals(10, $product->stock);
        $this->assertEquals(['M' => 4, 'L' => 6], $product->size_stocks);
        $this->assertFalse($product->isPreorder());
        $this->assertTrue($product->isAvailableStock());

        // Approval enables purchaser access
        $product->status = 'approved';
        $product->save();
        $this->assertTrue($product->isPurchasable());
    }

    public function test_customer_can_add_zero_stock_preorder_to_cart_and_update_quantity(): void
    {
        $product = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Bespoke Calado Preorder',
            'description' => 'Made to order piece',
            'price' => 5000,
            'stock' => 0,
            'sizes' => ['M', 'L'],
            'size_stocks' => [],
            'inventory_mode' => 'preorder',
            'handling_days' => 10,
            'status' => 'approved',
            'image' => ['/uploads/products/default.jpg'],
        ]);

        $this->actingAs($this->buyer);

        // Add to cart
        $response = $this->postJson('/cart/add', [
            'productId' => $product->id,
            'quantity' => 2,
            'size' => 'M',
            'variation' => 'Original',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $cart = session()->get('cart', []);
        $cartKey = CartHelper::getCanonicalKey($product->id, 'M', 'Original');
        $this->assertArrayHasKey($cartKey, $cart);
        $this->assertEquals('preorder', $cart[$cartKey]['inventory_mode']);
        $this->assertEquals(2, $cart[$cartKey]['quantity']);

        // Update quantity to 5
        $updateResp = $this->postJson('/cart/update', [
            'key' => $cartKey,
            'quantity' => 5,
        ]);

        $updateResp->assertOk();
        $cartAfter = session()->get('cart', []);
        $this->assertEquals(5, $cartAfter[$cartKey]['quantity']);
    }

    public function test_cart_helper_consolidate_preserves_preorder_items_and_cleans_out_of_stock_available_items(): void
    {
        $preorderProduct = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Preorder Piece',
            'description' => 'Made on demand',
            'price' => 4000,
            'stock' => 0,
            'sizes' => ['L'],
            'size_stocks' => [],
            'inventory_mode' => 'preorder',
            'handling_days' => 12,
            'status' => 'approved',
            'image' => ['/uploads/products/default.jpg'],
        ]);

        $soldOutAvailableProduct = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Sold Out Stock Piece',
            'description' => 'Sold out',
            'price' => 2000,
            'stock' => 0,
            'sizes' => ['M'],
            'size_stocks' => ['M' => 0],
            'inventory_mode' => 'available_stock',
            'status' => 'approved',
            'image' => ['/uploads/products/default.jpg'],
        ]);

        $rawCart = [
            'preorder_item' => [
                'id' => $preorderProduct->id,
                'name' => $preorderProduct->name,
                'price' => 4000,
                'quantity' => 3,
                'size' => 'L',
                'variation' => 'Original',
                'inventory_mode' => 'preorder',
            ],
            'sold_out_item' => [
                'id' => $soldOutAvailableProduct->id,
                'name' => $soldOutAvailableProduct->name,
                'price' => 2000,
                'quantity' => 2,
                'size' => 'M',
                'variation' => 'Original',
                'inventory_mode' => 'available_stock',
            ],
        ];

        $consolidated = CartHelper::consolidateCart($rawCart);

        // Preorder item must be retained with its full requested quantity despite stock = 0
        $preorderKey = CartHelper::getCanonicalKey($preorderProduct->id, 'L', 'Original');
        $this->assertArrayHasKey($preorderKey, $consolidated);
        $this->assertEquals(3, $consolidated[$preorderKey]['quantity']);
        $this->assertEquals('preorder', $consolidated[$preorderKey]['inventory_mode']);

        // Sold out available-stock item must be filtered out
        $availableKey = CartHelper::getCanonicalKey($soldOutAvailableProduct->id, 'M', 'Original');
        $this->assertArrayNotHasKey($availableKey, $consolidated);
    }

    public function test_order_creation_snapshots_preorder_mode_and_skips_stock_decrement(): void
    {
        $preorderProduct = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Tailored Pina Organza Preorder',
            'description' => 'Hand tailored',
            'price' => 7000,
            'stock' => 0,
            'sizes' => ['XL'],
            'size_stocks' => [],
            'inventory_mode' => 'preorder',
            'handling_days' => 15,
            'status' => 'approved',
            'image' => ['/uploads/products/default.jpg'],
        ]);

        $availableProduct = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Ready Pina Shawl',
            'description' => 'Ready in store',
            'price' => 1500,
            'stock' => 5,
            'sizes' => ['Standard'],
            'size_stocks' => ['Standard' => 5],
            'inventory_mode' => 'available_stock',
            'status' => 'approved',
            'image' => ['/uploads/products/default.jpg'],
        ]);

        /** @var CreateOrderService $service */
        $service = app(CreateOrderService::class);

        $order = $service->createOrder([
            'customer' => $this->buyer,
            'sellerId' => $this->seller->id,
            'items' => [
                [
                    'productId' => $preorderProduct->id,
                    'quantity' => 2,
                    'price' => 7000,
                    'size' => 'XL',
                    'variation' => 'Natural',
                ],
                [
                    'productId' => $availableProduct->id,
                    'quantity' => 1,
                    'price' => 1500,
                    'size' => 'Standard',
                    'variation' => 'Original',
                ],
            ],
            'shippingAddress' => [
                'full_name' => 'Juan Dela Cruz',
                'phone' => '09170001122',
                'street' => 'Crisostomo Ibarra St',
                'barangay' => 'Poblacion',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postal_code' => '4014',
            ],
            'paymentMethod' => 'COD',
            'deliveryMethod' => 'Standard Delivery',
            'shippingFee' => 150,
            'specialDiscount' => 0,
        ]);

        $this->assertNotNull($order);

        // Verify Order Items snapshotted modes
        $preorderItem = OrderItem::where('orderId', $order->id)
            ->where('productId', $preorderProduct->id)
            ->first();
        $this->assertNotNull($preorderItem);
        $this->assertEquals('preorder', $preorderItem->inventory_mode);
        $this->assertTrue($preorderItem->isPreorder());

        $availableItem = OrderItem::where('orderId', $order->id)
            ->where('productId', $availableProduct->id)
            ->first();
        $this->assertNotNull($availableItem);
        $this->assertEquals('available_stock', $availableItem->inventory_mode);
        $this->assertFalse($availableItem->isPreorder());

        // Verify product stocks after order
        $preorderProduct->refresh();
        $this->assertEquals(0, $preorderProduct->stock, 'Preorder stock must remain exactly 0.');

        $availableProduct->refresh();
        $this->assertEquals(4, $availableProduct->stock, 'Available stock must decrement by 1.');
        $this->assertEquals(4, $availableProduct->size_stocks['Standard']);
    }

    public function test_cancelling_order_restores_available_stock_but_never_preorder_stock(): void
    {
        $preorderProduct = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Preorder Wedding Barong',
            'description' => 'Made to order',
            'price' => 8000,
            'stock' => 0,
            'sizes' => ['M'],
            'size_stocks' => [],
            'inventory_mode' => 'preorder',
            'handling_days' => 20,
            'status' => 'approved',
            'image' => ['/uploads/products/default.jpg'],
        ]);

        $availableProduct = Product::create([
            'sellerId' => $this->seller->id,
            'CategoryId' => $this->category->id,
            'name' => 'Stock Wedding Barong',
            'description' => 'Physical stock',
            'price' => 8000,
            'stock' => 3,
            'sizes' => ['M'],
            'size_stocks' => ['M' => 3],
            'inventory_mode' => 'available_stock',
            'status' => 'approved',
            'image' => ['/uploads/products/default.jpg'],
        ]);

        /** @var CreateOrderService $service */
        $service = app(CreateOrderService::class);

        $order = $service->createOrder([
            'customer' => $this->buyer,
            'sellerId' => $this->seller->id,
            'items' => [
                [
                    'productId' => $preorderProduct->id,
                    'quantity' => 1,
                    'price' => 8000,
                    'size' => 'M',
                ],
                [
                    'productId' => $availableProduct->id,
                    'quantity' => 2,
                    'price' => 8000,
                    'size' => 'M',
                ],
            ],
            'shippingAddress' => [
                'full_name' => 'Juan Dela Cruz',
                'phone' => '09170001122',
                'street' => 'Crisostomo St',
                'barangay' => 'Poblacion',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postal_code' => '4014',
            ],
            'paymentMethod' => 'GCash',
            'deliveryMethod' => 'Standard Delivery',
            'shippingFee' => 150,
            'specialDiscount' => 0,
        ]);

        // Prior to cancellation:
        // Preorder stock = 0
        // Available stock was 3, decremented to 1
        $this->assertEquals(0, $preorderProduct->fresh()->stock);
        $this->assertEquals(1, $availableProduct->fresh()->stock);

        // Transition order to Cancellation Pending
        $order->status = 'Cancellation Pending';
        $order->save();

        // Cancel order via OrderController cancellation approval endpoint
        $this->actingAs($this->seller);
        $response = $this->postJson(route('orders.approve-cancellation', $order->id), [
            'reason' => 'Buyer changed mind before crafting started',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['order']);

        // After cancellation:
        // Preorder stock must STRICTLY remain 0! Never incremented to 1!
        $this->assertEquals(0, $preorderProduct->fresh()->stock, 'Preorder stock must NEVER be incremented upon cancellation.');
        $this->assertEmpty($preorderProduct->fresh()->size_stocks);

        // Available product stock must be restored from 1 back to 3
        $this->assertEquals(3, $availableProduct->fresh()->stock, 'Available stock must be restored upon cancellation.');
        $this->assertEquals(3, $availableProduct->fresh()->size_stocks['M']);
    }
}

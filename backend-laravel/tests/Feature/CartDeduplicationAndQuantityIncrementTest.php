<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\CartHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartDeduplicationAndQuantityIncrementTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $seller;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::create([
            'id'                => (string) Str::uuid(),
            'name'              => 'Azzy Artisan',
            'email'             => 'azzy@artisan.test',
            'password'          => bcrypt('password'),
            'role'              => 'seller',
            'status'            => 'active',
            'isVerified'        => true,
            'shopName'          => 'Azzy Artisan Creations',
            'isGcashAvailable'  => true,
            'gcashNumber'       => '09171234567',
        ]);

        $this->customer = User::create([
            'id'         => (string) Str::uuid(),
            'name'       => 'Maria Clara',
            'email'      => 'maria@customer.test',
            'password'   => bcrypt('password'),
            'role'       => 'customer',
            'status'     => 'active',
            'isVerified' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Barong Tagalog',
        ]);
    }

    protected function createTestProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'id'           => (string) Str::uuid(),
            'sellerId'     => $this->seller->id,
            'CategoryId'   => $this->category->id,
            'name'         => 'Embroidered Sleeve Satin Maxi',
            'price'        => 1250.00,
            'costPerPiece' => 900.00,
            'stock'        => 10,
            'sizes'        => ['S', 'M', 'L'],
            'size_stocks'  => ['S' => 5, 'M' => 5, 'L' => 5],
            'variations'   => [
                ['name' => 'Classic Black', 'image' => 'products/cover/black.jpg'],
                ['name' => 'Golden Piña', 'image' => 'products/cover/gold.jpg'],
            ],
            'status'       => 'approved',
        ], $overrides));
    }

    public function test_same_product_same_size_same_variation_increments_quantity(): void
    {
        $product = $this->createTestProduct();

        $this->actingAs($this->customer);

        // 1st Add: Size S, Classic Black, Qty 1
        $res1 = $this->postJson('/cart/add', [
            'productId' => $product->id,
            'size'      => 'S',
            'variation' => 'Classic Black',
            'quantity'  => 1,
        ]);

        $res1->assertOk()
            ->assertJson(['success' => true, 'cart_count' => 1]);

        $cart = session('cart');
        $this->assertCount(1, $cart);
        $firstItem = reset($cart);
        $this->assertEquals(1, $firstItem['quantity']);
        $this->assertEquals('S', $firstItem['size']);
        $this->assertEquals('Classic Black', $firstItem['variation']);

        // 2nd Add: Exact same configuration Qty 1
        $res2 = $this->postJson('/cart/add', [
            'productId' => $product->id,
            'size'      => 'S',
            'variation' => 'Classic Black',
            'quantity'  => 1,
        ]);

        $res2->assertOk()
            ->assertJson(['success' => true, 'cart_count' => 1]);

        $cartAfter = session('cart');
        $this->assertCount(1, $cartAfter, 'Cart should remain exactly 1 item instead of duplicating');
        $updatedItem = reset($cartAfter);
        $this->assertEquals(2, $updatedItem['quantity'], 'Quantity should have incremented from 1 to 2');
    }

    public function test_same_product_different_size_creates_separate_items(): void
    {
        $product = $this->createTestProduct([
            'name'        => 'Black Filipiniana Dress',
            'price'       => 1500.00,
            'stock'       => 20,
            'sizes'       => ['S', 'M'],
            'size_stocks' => ['S' => 10, 'M' => 10],
        ]);

        $this->actingAs($this->customer);

        // Add Size S
        $this->postJson('/cart/add', [
            'productId' => $product->id,
            'size'      => 'S',
            'quantity'  => 1,
        ])->assertOk();

        // Add Size M
        $this->postJson('/cart/add', [
            'productId' => $product->id,
            'size'      => 'M',
            'quantity'  => 1,
        ])->assertOk();

        $cart = session('cart');
        $this->assertCount(2, $cart, 'Different sizes of the same product must create separate cart rows');

        $sizesInCart = array_column($cart, 'size');
        $this->assertContains('S', $sizesInCart);
        $this->assertContains('M', $sizesInCart);
    }

    public function test_same_product_different_variation_creates_separate_items(): void
    {
        $product = $this->createTestProduct([
            'name'        => 'Traditional Barong',
            'price'       => 2500.00,
            'stock'       => 20,
            'sizes'       => ['L'],
            'size_stocks' => ['L' => 20],
            'variations'  => [
                ['name' => 'Black Piña', 'image' => 'products/cover/black.jpg'],
                ['name' => 'White Cocoon', 'image' => 'products/cover/white.jpg'],
            ],
        ]);

        $this->actingAs($this->customer);

        // Add Variant 1 (Black Piña)
        $this->postJson('/cart/add', [
            'productId' => $product->id,
            'size'      => 'L',
            'variation' => 'Black Piña',
            'quantity'  => 1,
        ])->assertOk();

        // Add Variant 2 (White Cocoon)
        $this->postJson('/cart/add', [
            'productId' => $product->id,
            'size'      => 'L',
            'variation' => 'White Cocoon',
            'quantity'  => 1,
        ])->assertOk();

        $cart = session('cart');
        $this->assertCount(2, $cart, 'Different variations of the same product must remain separate cart rows');

        $variationsInCart = array_column($cart, 'variation');
        $this->assertContains('Black Piña', $variationsInCart);
        $this->assertContains('White Cocoon', $variationsInCart);
    }

    public function test_product_without_variation_merges_across_different_input_representations(): void
    {
        $product = $this->createTestProduct([
            'name'        => 'Heritage Scarf',
            'price'       => 500.00,
            'stock'       => 15,
            'sizes'       => null,
            'size_stocks' => null,
            'variations'  => null,
        ]);

        $this->actingAs($this->customer);

        // Quick add (variation = null)
        $this->postJson('/cart/add', [
            'productId' => $product->id,
            'size'      => null,
            'variation' => null,
            'quantity'  => 1,
        ])->assertOk();

        // Product page add (variation = 'Original')
        $this->postJson('/cart/add', [
            'productId' => $product->id,
            'size'      => '',
            'variation' => 'Original',
            'quantity'  => 2,
        ])->assertOk();

        $cart = session('cart');
        $this->assertCount(1, $cart, 'Unvaried product added with null or Original should merge onto same canonical item');
        $item = reset($cart);
        $this->assertEquals(3, $item['quantity']);
    }

    public function test_stock_limits_are_strictly_enforced_when_incrementing_quantity(): void
    {
        $product = $this->createTestProduct([
            'name'        => 'Limited Silk Shawl',
            'price'       => 1200.00,
            'stock'       => 3,
            'sizes'       => ['Standard'],
            'size_stocks' => ['Standard' => 3],
            'variations'  => null,
        ]);

        $this->actingAs($this->customer);

        // Add 2
        $this->postJson('/cart/add', [
            'productId' => $product->id,
            'size'      => 'Standard',
            'quantity'  => 2,
        ])->assertOk();

        // Add another 2 (Total requested = 4, but stock is only 3)
        $this->postJson('/cart/add', [
            'productId' => $product->id,
            'size'      => 'Standard',
            'quantity'  => 2,
        ])->assertOk();

        $cart = session('cart');
        $this->assertCount(1, $cart);
        $item = reset($cart);
        $this->assertEquals(3, $item['quantity'], 'Quantity must be capped at maximum available stock (3)');
    }

    public function test_login_merges_guest_cart_and_saved_cart_authoritatively(): void
    {
        $product = $this->createTestProduct([
            'name'        => 'Custom Linen Barong',
            'price'       => 1800.00,
            'stock'       => 10,
            'sizes'       => ['M'],
            'size_stocks' => ['M' => 10],
            'variations'  => null,
        ]);

        $canonicalKey = CartHelper::getCanonicalKey($product->id, 'M', null, $product);

        // User already has 1 in saved DB cart
        $this->customer->update([
            'cart' => json_encode([
                $canonicalKey => [
                    'key'      => $canonicalKey,
                    'id'       => $product->id,
                    'name'     => $product->name,
                    'price'    => 1800.00,
                    'quantity' => 1,
                    'size'     => 'M',
                    'variation'=> null,
                    'sellerId' => $this->seller->id,
                ]
            ])
        ]);

        // Guest session adds 2 of the same item
        session()->put('cart', [
            $canonicalKey => [
                'key'      => $canonicalKey,
                'id'       => $product->id,
                'name'     => $product->name,
                'price'    => 1800.00,
                'quantity' => 2,
                'size'     => 'M',
                'variation'=> null,
                'sellerId' => $this->seller->id,
            ]
        ]);

        // Login as customer
        $this->post('/login', [
            'email'    => $this->customer->email,
            'password' => 'password',
        ])->assertRedirect();

        $mergedCart = session('cart');
        $this->assertCount(1, $mergedCart, 'Login should consolidate into a single cart item');
        $item = reset($mergedCart);
        $this->assertEquals(3, $item['quantity'], 'Saved quantity (1) + Guest quantity (2) should equal 3');
    }

    public function test_cart_index_consolidates_legacy_duplicate_records_on_the_fly(): void
    {
        $product = $this->createTestProduct([
            'name'        => 'Organza Bolero',
            'price'       => 950.00,
            'stock'       => 10,
            'sizes'       => ['S'],
            'size_stocks' => ['S' => 10],
            'variations'  => null,
        ]);

        // Simulate legacy unnormalized keys existing in session
        $legacyCart = [
            $product->id . '_S_' => [
                'id'       => $product->id,
                'name'     => $product->name,
                'price'    => 950.00,
                'quantity' => 1,
                'size'     => 'S',
                'variation'=> null,
                'sellerId' => $this->seller->id,
            ],
            $product->id . '_s_Original' => [
                'id'       => $product->id,
                'name'     => $product->name,
                'price'    => 950.00,
                'quantity' => 2,
                'size'     => 's',
                'variation'=> 'Original',
                'sellerId' => $this->seller->id,
            ],
        ];

        session()->put('cart', $legacyCart);
        $this->actingAs($this->customer);

        $response = $this->get('/cart');
        $response->assertOk();

        $cleanedCart = session('cart');
        $this->assertCount(1, $cleanedCart, 'Legacy duplicate entries should be automatically consolidated on index');
        $item = reset($cleanedCart);
        $this->assertEquals(3, $item['quantity'], 'Legacy quantities (1 + 2) must be summed to 3');
    }

    public function test_checkout_from_selected_cart_calculates_shipping_quote_without_cart_empty_error(): void
    {
        $this->seed(\Database\Seeders\ShippingLogisticsSeeder::class);

        $this->seller->update([
            'shopProvince'   => 'Laguna',
            'shopCity'       => 'Lumban',
            'shopBarangay'   => 'Poblacion',
            'shopPostalCode' => '4014',
        ]);

        $product = $this->createTestProduct();
        $this->actingAs($this->customer);

        $address = \App\Models\Address::create([
            'id'            => (string) Str::uuid(),
            'userId'        => $this->customer->id,
            'recipientName' => 'Maria Clara',
            'phone'         => '09123456789',
            'houseNo'       => '101',
            'street'        => 'Angon St',
            'barangay'      => 'Barangay 1',
            'city'          => 'Manila',
            'province'      => 'Metro Manila',
            'region'        => 'NCR',
            'postalCode'    => '1000',
            'is_default'    => true,
        ]);

        // Add item to cart
        $this->postJson('/cart/add', [
            'productId' => $product->id,
            'size'      => 'S',
            'variation' => 'Classic Black',
            'quantity'  => 1,
        ])->assertOk();

        $cart = session('cart');
        $cartKey = array_key_first($cart);

        // Checkout from selected
        $fromSelectedRes = $this->post(route('checkout.selected'), [
            'selected_keys' => [$cartKey],
        ]);
        $fromSelectedRes->assertRedirect(route('checkout.index', ['mode' => 'selected']));

        // Call shipping quote with mode = selected
        $quoteRes = $this->postJson(route('checkout.shipping-quote'), [
            'address_id' => $address->id,
            'mode'       => 'selected',
        ]);

        $quoteRes->assertOk();
        $quoteRes->assertJson(['success' => true]);
        $this->assertArrayHasKey('shipping_quote_token', $quoteRes->json());
    }
}


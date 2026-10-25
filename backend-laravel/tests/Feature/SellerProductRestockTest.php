<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SellerProductRestockTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'approved',
            'isVerified' => true,
        ]);

        $category = Category::create([
            'name' => 'Formal Barong',
            'target_group' => ['Men'],
            'description' => 'Traditional formal Lumban barongs',
        ]);

        $this->product = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Testing Barong',
            'description' => 'Experience authentic Filipino artistry.',
            'price' => 419.50,
            'stock' => 0,
            'status' => 'approved',
            'CategoryId' => $category->id,
            'sizes' => ['S', 'M', 'L'],
            'size_stocks' => ['S' => 0, 'M' => 0, 'L' => 0],
            'shippingFee' => 150,
            'shippingDays' => 3,
        ]);
    }

    /**
     * Test that the restock button renders on the seller products page
     * with direct product ID call and clean attributes.
     */
    public function test_seller_products_index_renders_restock_button_correctly(): void
    {
        $response = $this->actingAs($this->seller)->get(route('seller.products.index'));

        $response->assertStatus(200);
        $response->assertSee('Testing Barong');
        // Assert openRestockModal is called with the product ID
        $response->assertSee("openRestockModal('{$this->product->id}'", false);
        // Assert data-product-id is present
        $response->assertSee("data-product-id=\"{$this->product->id}\"", false);
        // Assert that the double-encoded htmlspecialchars entity &amp;quot; is NOT present
        $response->assertDontSee('&amp;quot;', false);
    }

    /**
     * Test successful restock via API endpoint with size quantities.
     */
    public function test_seller_can_restock_product_by_sizes(): void
    {
        $response = $this->actingAs($this->seller)->postJson("/seller/products/{$this->product->id}/restock", [
            'size_quantities' => [
                'S' => 2,
                'M' => 5,
                'L' => 3,
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'new_stock' => 10,
            'size_stocks' => [
                'S' => 2,
                'M' => 5,
                'L' => 3,
            ],
        ]);

        $this->product->refresh();
        $this->assertEquals(10, $this->product->stock);
        $this->assertEquals(['S' => 2, 'M' => 5, 'L' => 3], $this->product->size_stocks);
    }

    /**
     * Test successful restock via API endpoint with single quantity.
     */
    public function test_seller_can_restock_product_by_total_quantity(): void
    {
        $singleProduct = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Custom Accessory',
            'description' => 'Artisan accessory without sizes.',
            'price' => 150.00,
            'stock' => 2,
            'status' => 'approved',
            'sizes' => [],
            'shippingFee' => 50,
            'shippingDays' => 2,
        ]);

        $response = $this->actingAs($this->seller)->postJson("/seller/products/{$singleProduct->id}/restock", [
            'quantity' => 15,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'new_stock' => 15,
        ]);

        $singleProduct->refresh();
        $this->assertEquals(15, $singleProduct->stock);
    }

    /**
     * Test that another seller cannot restock someone else's product (returns 404).
     */
    public function test_other_seller_cannot_restock_foreign_product(): void
    {
        /** @var User $otherSeller */
        $otherSeller = User::factory()->create([
            'role' => 'seller',
            'status' => 'approved',
            'isVerified' => true,
        ]);

        $response = $this->actingAs($otherSeller)->postJson("/seller/products/{$this->product->id}/restock", [
            'size_quantities' => ['S' => 5],
        ]);

        $response->assertStatus(404);
    }
}

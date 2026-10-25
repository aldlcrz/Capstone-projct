<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CatalogueClearSearchSyncTest extends TestCase
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
            'description' => 'Formal Lumban barong',
        ]);

        $this->product = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Black Modern Barong',
            'description' => 'Handcrafted black barong.',
            'price' => 1599.00,
            'stock' => 10,
            'status' => 'approved',
            'CategoryId' => $category->id,
            'sizes' => ['S', 'M', 'L'],
            'shippingFee' => 100,
            'shippingDays' => 3,
        ]);
    }

    /**
     * Test that the search results banner renders the clear search button
     * with the synchronizing onclick and event dispatchers.
     */
    public function test_catalogue_search_results_renders_clear_search_with_sync_handlers(): void
    {
        $response = $this->get('/?search=black');

        $response->assertStatus(200);
        $response->assertSee('Results for');
        $response->assertSee('black');
        $response->assertSee('Clear Search');
        // Assert event dispatcher and DOM input clearing on click
        $response->assertSee("window.dispatchEvent(new CustomEvent('catalogue-search-cleared'))", false);
        // Assert navbar search form listens for catalogue-search-cleared
        $response->assertSee('@catalogue-search-cleared.window', false);
    }

    /**
     * Test that clicking/navigating to the clear search URL removes search filtering.
     */
    public function test_navigating_to_clear_search_target_returns_full_catalogue(): void
    {
        $response = $this->get('/#catalogue-section');

        $response->assertStatus(200);
        $response->assertDontSee('Results for <strong class="text-black">"black"</strong>', false);
        $response->assertDontSee('Clear Search');
    }
}

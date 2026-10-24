<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShopSpecificSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function search_api_with_seller_parameter_strictly_isolates_products_to_that_shop()
    {
        // Setup Seller A
        $sellerA = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Lumban Heritage Barong',
        ]);

        // Setup Seller B
        $sellerB = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Manila Embroidery Hub',
        ]);

        // Create Barong for Seller A
        $productA1 = Product::create([
            'id'             => (string) Str::uuid(),
            'name'           => 'Lumban Hand-Embroidered Piña Barong Tagalog',
            'sellerId'       => $sellerA->id,
            'price'          => 4500.00,
            'stock'          => 10,
            'status'         => 'approved',
            'approvalStatus' => 'Approved',
            'description'    => 'Authentic hand-loomed piña fabric with calado embroidery',
            'colors'         => json_encode(['Natural', 'Cream']),
            'target_group'   => 'Men',
        ]);

        // Create second product for Seller A (different garment)
        $productA2 = Product::create([
            'id'             => (string) Str::uuid(),
            'name'           => 'Lumban Silk Bolero for Women',
            'sellerId'       => $sellerA->id,
            'price'          => 3200.00,
            'stock'          => 5,
            'status'         => 'approved',
            'approvalStatus' => 'Approved',
            'description'    => 'Fine silk bolero filipiniana',
            'colors'         => json_encode(['Rose', 'Pink']),
            'target_group'   => 'Women',
        ]);

        // Create Barong for Seller B with overlapping keywords
        $productB1 = Product::create([
            'id'             => (string) Str::uuid(),
            'name'           => 'Manila Classic Barong Tagalog Piña Organza',
            'sellerId'       => $sellerB->id,
            'price'          => 2800.00,
            'stock'          => 8,
            'status'         => 'approved',
            'approvalStatus' => 'Approved',
            'description'    => 'Affordable barong tagalog from Manila',
            'colors'         => json_encode(['Natural']),
            'target_group'   => 'Men',
        ]);

        // Query Seller A with search='barong'
        $responseA = $this->getJson("/api/v1/products?seller={$sellerA->id}&search=barong");
        $responseA->assertStatus(200);

        $resultsA = $responseA->json();
        $this->assertNotEmpty($resultsA);

        // Every single returned item MUST belong to Seller A
        foreach ($resultsA as $item) {
            $this->assertEquals($sellerA->id, $item['sellerId'], 'Strict shop isolation: returned product must belong to Seller A');
            $this->assertNotEquals($sellerB->id, $item['sellerId'], 'Strict shop isolation violation: Seller B product leaked into Seller A search');
        }

        // Verify Product A1 was returned, but Product B1 was excluded
        $idsA = array_column($resultsA, 'id');
        $this->assertContains($productA1->id, $idsA);
        $this->assertNotContains($productB1->id, $idsA);

        // Query Seller B with search='barong'
        $responseB = $this->getJson("/api/v1/products?seller={$sellerB->id}&search=barong");
        $responseB->assertStatus(200);

        $resultsB = $responseB->json();
        $this->assertNotEmpty($resultsB);

        // Every single returned item MUST belong to Seller B
        foreach ($resultsB as $item) {
            $this->assertEquals($sellerB->id, $item['sellerId'], 'Strict shop isolation: returned product must belong to Seller B');
            $this->assertNotEquals($sellerA->id, $item['sellerId'], 'Strict shop isolation violation: Seller A product leaked into Seller B search');
        }

        $idsB = array_column($resultsB, 'id');
        $this->assertContains($productB1->id, $idsB);
        $this->assertNotContains($productA1->id, $idsB);
    }

    /** @test */
    public function visit_shop_page_renders_shop_specific_search_bar_and_custom_placeholder()
    {
        $seller = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Lumban Master Tailors',
        ]);

        $response = $this->get("/shop/{$seller->id}");
        $response->assertStatus(200);

        // Header search bar on shop page should have custom shop placeholder
        $response->assertSee('Search in Lumban Master Tailors...', false);

        // Header search bar action should point to the current shop URL
        $response->assertSee("/shop/{$seller->id}", false);

        // In-page shop client component should have the modern search bar
        $response->assertSee('shopSearchInput', false);
        $response->assertSee('x-ref="shopSearchInput"', false);
        $response->assertSee('onSearchInput', false);
        $response->assertSee('Clear Search', false);
    }

    /** @test */
    public function zero_search_results_in_shop_returns_empty_array_and_no_other_shops_products()
    {
        $seller = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Lumban Special Shop',
        ]);

        // Create product for another seller
        $otherSeller = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Other Shop',
        ]);

        Product::create([
            'id'             => (string) Str::uuid(),
            'name'           => 'Unicorn Rare Silk Tuxedo',
            'sellerId'       => $otherSeller->id,
            'price'          => 9999.00,
            'stock'          => 1,
            'status'         => 'approved',
            'approvalStatus' => 'Approved',
            'description'    => 'Ultra rare item in other shop',
        ]);

        // Search for 'Unicorn' in $seller's shop (where it does NOT exist)
        $response = $this->getJson("/api/v1/products?seller={$seller->id}&search=Unicorn");
        $response->assertStatus(200);

        $results = $response->json();
        $this->assertEmpty($results, 'Seller shop search must be empty and NEVER leak items from other shops');
    }
}

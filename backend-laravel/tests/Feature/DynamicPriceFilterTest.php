<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicPriceFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected Category $menCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'active',
        ]);

        $this->menCategory = Category::create([
            'name' => 'Men',
            'slug' => 'men',
        ]);
    }

    public function test_it_dynamically_discovers_catalog_price_bounds_and_builds_recommended_tiers(): void
    {
        // Create products matching the current catalog boundaries
        Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Budget Craft Swatch',
            'price' => 50.00,
            'status' => 'approved',
            'CategoryId' => $this->menCategory->id,
        ]);

        Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Embroidered Mask',
            'price' => 739.50,
            'status' => 'approved',
            'CategoryId' => $this->menCategory->id,
        ]);

        Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Classic Jusi Barong',
            'price' => 1200.00,
            'status' => 'approved',
            'CategoryId' => $this->menCategory->id,
        ]);

        Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Organza Formal Barong',
            'price' => 2200.00,
            'status' => 'approved',
            'CategoryId' => $this->menCategory->id,
        ]);

        Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Hand-Embroidered Cocoon Barong',
            'price' => 3199.00,
            'status' => 'approved',
            'CategoryId' => $this->menCategory->id,
        ]);

        Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Master Artisan Piña Barong Tagalog',
            'price' => 3999.00,
            'status' => 'approved',
            'CategoryId' => $this->menCategory->id,
        ]);

        ProductFilterService::clearPriceBoundsCache();
        $bounds = ProductFilterService::getEligibleCatalogPriceBounds();

        $this->assertEquals(50.0, $bounds['min']);
        $this->assertEquals(3999.0, $bounds['max']);

        $tiers = ProductFilterService::getPriceTiers();

        // 1 All Prices + 5 sensible tiers = 6 items
        $this->assertCount(6, $tiers);

        $this->assertEquals('All Prices', $tiers[0]['label']);
        $this->assertEquals('', $tiers[0]['val']);

        // First tier starts strictly at the actual catalog minimum (50)
        $this->assertEquals('₱50 – ₱999', $tiers[1]['label']);
        $this->assertEquals('50_999', $tiers[1]['val']);

        // Middle tiers
        $this->assertEquals('₱1,000 – ₱1,999', $tiers[2]['label']);
        $this->assertEquals('1000_1999', $tiers[2]['val']);

        $this->assertEquals('₱2,000 – ₱2,999', $tiers[3]['label']);
        $this->assertEquals('2000_2999', $tiers[3]['val']);

        $this->assertEquals('₱3,000 – ₱3,499', $tiers[4]['label']);
        $this->assertEquals('3000_3499', $tiers[4]['val']);

        // Last tier ends strictly at the actual catalog maximum (3999)
        $this->assertEquals('₱3,500 – ₱3,999', $tiers[5]['label']);
        $this->assertEquals('3500_3999', $tiers[5]['val']);
    }

    public function test_it_filters_correctly_by_dynamic_tier_including_decimal_prices(): void
    {
        $craft = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Budget Craft Swatch',
            'price' => 50.00,
            'status' => 'approved',
        ]);

        $decimalItem = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Embroidered Mask Item',
            'price' => 739.50,
            'status' => 'approved',
        ]);

        $upperBudget = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Upper Edge Centavo Barong',
            'price' => 999.99,
            'status' => 'approved',
        ]);

        $midItem = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Classic Jusi Barong',
            'price' => 1200.00,
            'status' => 'approved',
        ]);

        $luxuryItem = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Master Artisan Piña Barong',
            'price' => 3999.00,
            'status' => 'approved',
        ]);

        // 1. Filter 50_999: includes 50.00, 739.50, and 999.99, but excludes 1200.00
        $resTier1 = $this->get('/?price_range=50_999');
        $resTier1->assertStatus(200);
        $products1 = $resTier1->viewData('products');
        $this->assertTrue($products1->contains('id', $craft->id));
        $this->assertTrue($products1->contains('id', $decimalItem->id));
        $this->assertTrue($products1->contains('id', $upperBudget->id));
        $this->assertFalse($products1->contains('id', $midItem->id));
        $this->assertFalse($products1->contains('id', $luxuryItem->id));

        // 2. Filter 1000_1999: includes 1200.00, excludes 999.99 and 3999.00
        $resTier2 = $this->get('/?price_range=1000_1999');
        $resTier2->assertStatus(200);
        $products2 = $resTier2->viewData('products');
        $this->assertTrue($products2->contains('id', $midItem->id));
        $this->assertFalse($products2->contains('id', $craft->id));
        $this->assertFalse($products2->contains('id', $upperBudget->id));
        $this->assertFalse($products2->contains('id', $luxuryItem->id));

        // 3. Filter 3500_3999: includes 3999.00
        $resTier5 = $this->get('/?price_range=3500_3999');
        $resTier5->assertStatus(200);
        $products5 = $resTier5->viewData('products');
        $this->assertTrue($products5->contains('id', $luxuryItem->id));
        $this->assertFalse($products5->contains('id', $midItem->id));
        $this->assertFalse($products5->contains('id', $craft->id));
    }

    public function test_it_auto_updates_tiers_when_product_prices_change(): void
    {
        Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Initial Lowest Item',
            'price' => 100.00,
            'status' => 'approved',
        ]);

        Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Initial Highest Item',
            'price' => 3500.00,
            'status' => 'approved',
        ]);

        ProductFilterService::clearPriceBoundsCache();
        $tiersBefore = ProductFilterService::getPriceTiers();
        $this->assertEquals('₱100 – ₱999', $tiersBefore[1]['label']);
        $this->assertEquals('₱3,000 – ₱3,500', $tiersBefore[count($tiersBefore) - 1]['label']);

        // Now add a high-end luxury piece priced at 5000.00
        Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Imperial Gold Embroidered Barong',
            'price' => 5000.00,
            'status' => 'approved',
        ]);

        // Model saved hook automatically clears the cache
        $tiersAfter = ProductFilterService::getPriceTiers();
        $lastTier = $tiersAfter[count($tiersAfter) - 1];
        $this->assertEquals('₱3,500 – ₱5,000', $lastTier['label']);
        $this->assertEquals('3500_5000', $lastTier['val']);
    }

    public function test_it_maintains_compatibility_with_search_category_and_sort(): void
    {
        $weddingBarong = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Wedding Barong Tagalog',
            'description' => 'Fine piña barong for groom wedding',
            'price' => 1500.00,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $budgetMen = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Everyday Barong Tagalog',
            'description' => 'Casual short sleeve wedding barong',
            'price' => 600.00,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $res = $this->get('/?category=Men&search=Wedding&sort=newest&price_range=1000_1999');
        $res->assertStatus(200);

        $products = $res->viewData('products');
        $this->assertTrue($products->contains('id', $weddingBarong->id));
        $this->assertFalse($products->contains('id', $budgetMen->id)); // excluded by price_range 1000_1999
    }

    public function test_legacy_presets_remain_supported(): void
    {
        $budget = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Budget Barong Accessory',
            'price' => 350.00,
            'status' => 'approved',
        ]);

        $res = $this->get('/?price_range=under_500');
        $res->assertStatus(200);
        $products = $res->viewData('products');
        $this->assertTrue($products->contains('id', $budget->id));
    }

    public function test_min_max_and_apply_fields_remain_intact(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);

        // Min input field with exact name and step
        $res->assertSee('name="min_price"', false);
        $res->assertSee('placeholder="Min"', false);

        // Max input field with exact name and step
        $res->assertSee('name="max_price"', false);
        $res->assertSee('placeholder="Max"', false);

        // Apply submit button
        $res->assertSee('Apply', false);
    }
}

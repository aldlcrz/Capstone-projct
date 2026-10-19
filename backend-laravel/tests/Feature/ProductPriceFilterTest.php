<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Services\ProductFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductPriceFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected Category $menCategory;
    protected Category $womenCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'shopName' => 'Lumban Fine Embroidery',
            'status' => 'approved',
        ]);

        $this->menCategory = Category::create([
            'name' => 'Barong Tagalog',
            'target_group' => ['Men'],
        ]);

        $this->womenCategory = Category::create([
            'name' => 'Filipiniana',
            'target_group' => ['Women'],
        ]);
    }

    /**
     * Helper to create an approved product with a given price.
     */
    protected function createProduct(string $name, float|string $price, string|int|null $categoryId = null, string $status = 'approved'): Product
    {
        return Product::create([
            'sellerId' => $this->seller->id,
            'name' => $name,
            'description' => "Test description for {$name}",
            'price' => $price,
            'stock' => 10,
            'status' => $status,
            'target_group' => 'Men',
            'CategoryId' => $categoryId ?? $this->menCategory->id,
        ]);
    }

    public function test_service_parse_price_range_whole_peso_maximum(): void
    {
        $parsed = ProductFilterService::parsePriceRange(null, 419);
        $this->assertTrue($parsed['isValid']);
        $this->assertNull($parsed['min']);
        // 419 should expand to 419.99
        $this->assertEquals('419.99', $parsed['max']);

        // Explicit 419.00 is also a whole peso
        $parsed2 = ProductFilterService::parsePriceRange(null, '419.00');
        $this->assertTrue($parsed2['isValid']);
        $this->assertEquals('419.99', $parsed2['max']);
    }

    public function test_service_parse_price_range_decimal_maximum(): void
    {
        $parsed = ProductFilterService::parsePriceRange(null, '419.50');
        $this->assertTrue($parsed['isValid']);
        $this->assertEquals('419.50', $parsed['max']);

        $parsed2 = ProductFilterService::parsePriceRange(null, '419.5');
        $this->assertTrue($parsed2['isValid']);
        $this->assertEquals('419.50', $parsed2['max']);

        $parsed3 = ProductFilterService::parsePriceRange(null, '419.99');
        $this->assertTrue($parsed3['isValid']);
        $this->assertEquals('419.99', $parsed3['max']);
    }

    public function test_service_parse_price_range_validation_rejections(): void
    {
        // Negative minimum
        $negMin = ProductFilterService::parsePriceRange(-10, 500);
        $this->assertFalse($negMin['isValid']);
        $this->assertEquals('Minimum price cannot be negative.', $negMin['error']);

        // Negative maximum
        $negMax = ProductFilterService::parsePriceRange(10, -50);
        $this->assertFalse($negMax['isValid']);
        $this->assertEquals('Maximum price cannot be negative.', $negMax['error']);

        // Non-numeric minimum
        $nanMin = ProductFilterService::parsePriceRange('abc', 500);
        $this->assertFalse($nanMin['isValid']);
        $this->assertEquals('Minimum price must be a valid number.', $nanMin['error']);

        // Non-numeric maximum
        $nanMax = ProductFilterService::parsePriceRange(100, 'xyz');
        $this->assertFalse($nanMax['isValid']);
        $this->assertEquals('Maximum price must be a valid number.', $nanMax['error']);

        // Minimum > Maximum
        $minGtMax = ProductFilterService::parsePriceRange(500, 400);
        $this->assertFalse($minGtMax['isValid']);
        $this->assertEquals('Minimum price cannot be greater than maximum price.', $minGtMax['error']);
    }

    public function test_whole_peso_maximum_includes_centavos_up_to_point_99(): void
    {
        $p418 = $this->createProduct('Barong 418', 418.00);
        $p419_00 = $this->createProduct('Barong 419 Exact', 419.00);
        $p419_50 = $this->createProduct('Barong 419 Half', 419.50);
        $p419_99 = $this->createProduct('Barong 419 Boundary', 419.99);
        $p420_00 = $this->createProduct('Barong 420 Next', 420.00);

        // When customer searches with whole peso max_price = 419
        $response = $this->get('/?max_price=419');
        $response->assertStatus(200);

        $products = $response->viewData('products');
        $this->assertTrue($products->contains('id', $p418->id));
        $this->assertTrue($products->contains('id', $p419_00->id));
        $this->assertTrue($products->contains('id', $p419_50->id));
        $this->assertTrue($products->contains('id', $p419_99->id));
        $this->assertFalse($products->contains('id', $p420_00->id));
    }

    public function test_decimal_maximum_uses_exact_inclusive_amount(): void
    {
        $p419_40 = $this->createProduct('Barong 419.40', 419.40);
        $p419_50 = $this->createProduct('Barong 419.50', 419.50);
        $p419_51 = $this->createProduct('Barong 419.51', 419.51);

        // Filter with exact decimal max = 419.50
        $response = $this->get('/?max_price=419.50');
        $response->assertStatus(200);

        $products = $response->viewData('products');
        $this->assertTrue($products->contains('id', $p419_40->id));
        $this->assertTrue($products->contains('id', $p419_50->id));
        $this->assertFalse($products->contains('id', $p419_51->id));
    }

    public function test_minimum_only_filter(): void
    {
        $p419_49 = $this->createProduct('Barong 419.49', 419.49);
        $p419_50 = $this->createProduct('Barong 419.50', 419.50);
        $p420_00 = $this->createProduct('Barong 420.00', 420.00);

        $response = $this->get('/?min_price=419.50');
        $response->assertStatus(200);

        $products = $response->viewData('products');
        $this->assertFalse($products->contains('id', $p419_49->id));
        $this->assertTrue($products->contains('id', $p419_50->id));
        $this->assertTrue($products->contains('id', $p420_00->id));
    }

    public function test_combined_whole_peso_range_same_number(): void
    {
        // When customer sets min=419 and max=419, it should match all 419.xx products
        $p418_99 = $this->createProduct('Barong 418.99', 418.99);
        $p419_00 = $this->createProduct('Barong 419.00', 419.00);
        $p419_50 = $this->createProduct('Barong 419.50', 419.50);
        $p419_99 = $this->createProduct('Barong 419.99', 419.99);
        $p420_00 = $this->createProduct('Barong 420.00', 420.00);

        $response = $this->get('/?min_price=419&max_price=419');
        $response->assertStatus(200);

        $products = $response->viewData('products');
        $this->assertFalse($products->contains('id', $p418_99->id));
        $this->assertTrue($products->contains('id', $p419_00->id));
        $this->assertTrue($products->contains('id', $p419_50->id));
        $this->assertTrue($products->contains('id', $p419_99->id));
        $this->assertFalse($products->contains('id', $p420_00->id));
    }

    public function test_invalid_range_min_greater_than_max(): void
    {
        $p419 = $this->createProduct('Barong 419', 419.00);

        $response = $this->get('/?min_price=500&max_price=400');
        $response->assertStatus(200);

        $products = $response->viewData('products');
        // Product should not be shown
        $this->assertFalse($products->contains('id', $p419->id));
        $this->assertCount(0, $products);

        // Warning message should be present in the viewData and view HTML
        $this->assertEquals('Minimum price cannot be greater than maximum price.', $response->viewData('priceFilterError'));
        $response->assertSee('Minimum price cannot be greater than maximum price.');
    }

    public function test_negative_price_input_rejected(): void
    {
        $p419 = $this->createProduct('Barong 419', 419.00);

        $response = $this->get('/?min_price=-50&max_price=500');
        $response->assertStatus(200);

        $this->assertEquals('Minimum price cannot be negative.', $response->viewData('priceFilterError'));
        $response->assertSee('Minimum price cannot be negative.');
    }

    public function test_price_filter_with_category_and_search_and_availability(): void
    {
        // Approved product in Men category with matching search and price
        $match = $this->createProduct('Silk Organza Barong', 419.50, $this->menCategory->id, 'approved');

        // Approved product with matching search and price, but wrong category
        $wrongCategory = $this->createProduct('Silk Organza Gown', 419.50, $this->womenCategory->id, 'approved');

        // Unapproved product with matching price and category
        $unapproved = $this->createProduct('Silk Organza Pending', 419.50, $this->menCategory->id, 'pending');

        // Product matching category and search, but outside price range
        $expensive = $this->createProduct('Silk Organza Expensive', 550.00, $this->menCategory->id, 'approved');

        $response = $this->get('/?search=Organza&category=' . $this->menCategory->id . '&min_price=400&max_price=419');
        $response->assertStatus(200);

        $products = $response->viewData('products');
        $this->assertTrue($products->contains('id', $match->id));
        $this->assertFalse($products->contains('id', $wrongCategory->id));
        $this->assertFalse($products->contains('id', $unapproved->id));
        $this->assertFalse($products->contains('id', $expensive->id));
    }

    public function test_api_v1_products_price_filter(): void
    {
        $p418 = $this->createProduct('Barong API 418', 418.00);
        $p419_50 = $this->createProduct('Barong API 419.50', 419.50);
        $p420 = $this->createProduct('Barong API 420', 420.00);

        // Whole peso max 419
        $response = $this->getJson('/api/v1/products?max_price=419');
        $response->assertStatus(200);
        $ids = collect($response->json())->pluck('id')->all();

        $this->assertContains($p418->id, $ids);
        $this->assertContains($p419_50->id, $ids);
        $this->assertNotContains($p420->id, $ids);

        // Decimal max 419.20 should exclude 419.50
        $responseDec = $this->getJson('/api/v1/products?max_price=419.20');
        $responseDec->assertStatus(200);
        $idsDec = collect($responseDec->json())->pluck('id')->all();

        $this->assertContains($p418->id, $idsDec);
        $this->assertNotContains($p419_50->id, $idsDec);
        $this->assertNotContains($p420->id, $idsDec);

        // Min only API filter
        $responseMin = $this->getJson('/api/v1/products?min_price=419.50');
        $responseMin->assertStatus(200);
        $idsMin = collect($responseMin->json())->pluck('id')->all();

        $this->assertNotContains($p418->id, $idsMin);
        $this->assertContains($p419_50->id, $idsMin);
        $this->assertContains($p420->id, $idsMin);
    }
}

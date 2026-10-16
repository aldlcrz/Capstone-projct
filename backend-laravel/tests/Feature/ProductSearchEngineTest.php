<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Services\ProductSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductSearchEngineTest extends TestCase
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
            'shopName' => "Lumban Artisan Guild",
        ]);

        $this->menCategory = Category::create([
            'name' => 'Barong Tagalog',
            'target_group' => ['Men'],
        ]);

        $this->womenCategory = Category::create([
            'name' => 'Filipiniana & Gowns',
            'target_group' => ['Women'],
        ]);
    }

    public function test_normalization_handles_possessives_punctuation_and_plurals(): void
    {
        $this->assertEquals("men barong", ProductSearchService::normalize("men's barong"));
        $this->assertEquals("women barong", ProductSearchService::normalize("women's barong"));
        $this->assertEquals("kid barong", ProductSearchService::normalize("kid's barong"));
        $this->assertEquals("polo barong", ProductSearchService::normalize("polo - barong!"));
    }

    public function test_target_audience_detection(): void
    {
        $this->assertEquals('Men', ProductSearchService::detectTargetAudience('barong for men'));
        $this->assertEquals('Men', ProductSearchService::detectTargetAudience('men barong'));
        $this->assertEquals('Men', ProductSearchService::detectTargetAudience('male barong'));
        $this->assertEquals('Women', ProductSearchService::detectTargetAudience('barong for women'));
        $this->assertEquals('Women', ProductSearchService::detectTargetAudience('filipiniana dress'));
        $this->assertEquals('Kids', ProductSearchService::detectTargetAudience('barong for kids'));
        $this->assertNull(ProductSearchService::detectTargetAudience('calado embroidery'));
    }

    public function test_token_extraction_stems_plurals_and_removes_stopwords(): void
    {
        $tokens = ProductSearchService::extractTokens(ProductSearchService::normalize('barongs for men'));
        $words = array_column($tokens, 'original');
        
        $this->assertContains('barongs', $words);
        $this->assertContains('men', $words);
        $this->assertNotContains('for', $words);

        // Check variant generation
        $barongToken = collect($tokens)->firstWhere('original', 'barongs');
        $this->assertContains('barong', $barongToken['variants']);
    }

    public function test_barong_for_men_finds_polo_barong_with_men_tag_and_avoids_womens_products(): void
    {
        $poloMen = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Polo Barong',
            'description' => 'Classic everyday business attire for gentlemen',
            'price' => 1200,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'fabric_type' => 'Jusi',
            'CategoryId' => $this->menCategory->id,
        ]);

        $dressWomen = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Mestiza Balloon Dress',
            'description' => 'Modern Filipiniana barong style dress for women',
            'price' => 2500,
            'stock' => 5,
            'status' => 'approved',
            'target_group' => 'Women',
            'fabric_type' => 'Organza',
            'CategoryId' => $this->womenCategory->id,
        ]);

        // Search for 'barong for men'
        $results = Product::query()->where('status', 'approved')->search('barong for men')->get();

        $this->assertTrue($results->contains('id', $poloMen->id), 'Polo Barong should appear in search for barong for men');
        
        // Polo Men should rank higher than any woman's product
        $firstResult = $results->first();
        $this->assertNotNull($firstResult);
        $this->assertEquals($poloMen->id, $firstResult->id, 'Men product must rank first when searching barong for men');
    }

    public function test_mens_barong_finds_men_barong(): void
    {
        $poloMen = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Polo Barong Tagalog',
            'description' => 'Handcrafted embroidered piece',
            'price' => 1500,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $results = Product::query()->where('status', 'approved')->search("men's barong")->get();
        $this->assertTrue($results->contains('id', $poloMen->id));
    }

    public function test_barongs_plural_finds_singular_barong(): void
    {
        $barong = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Barong Tagalog Calado',
            'description' => 'Fine piña craftsmanship',
            'price' => 3500,
            'stock' => 8,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        // Plural search
        $results = Product::query()->where('status', 'approved')->search('barongs')->get();
        $this->assertTrue($results->contains('id', $barong->id));

        // Plural with audience
        $resultsWithAudience = Product::query()->where('status', 'approved')->search('barongs for men')->get();
        $this->assertTrue($resultsWithAudience->contains('id', $barong->id));
    }

    public function test_barong_for_women_prioritizes_womens_collection(): void
    {
        $poloMen = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Classic Barong Tagalog',
            'description' => 'Formal barong piece',
            'price' => 2000,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $filipinianaWomen = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Filipiniana Barong Bolero',
            'description' => 'Formal barong bolero for women with floral embroidery',
            'price' => 2200,
            'stock' => 5,
            'status' => 'approved',
            'target_group' => 'Women',
            'CategoryId' => $this->womenCategory->id,
        ]);

        $results = Product::query()->where('status', 'approved')->search('barong for women')->get();

        $this->assertTrue($results->contains('id', $filipinianaWomen->id));
        $this->assertEquals($filipinianaWomen->id, $results->first()->id, "Women's item must be ranked first when searching barong for women");
    }

    public function test_unrelated_keyword_returns_zero_results(): void
    {
        Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Polo Barong',
            'description' => 'Authentic embroidery',
            'price' => 1200,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $results = Product::query()->where('status', 'approved')->search('smartphone android gadget')->get();
        $this->assertCount(0, $results);
    }

    public function test_homepage_http_search_route_with_various_queries(): void
    {
        $poloMen = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Authentic Polo Barong',
            'description' => 'Finest Lumban craftsmanship',
            'price' => 1800,
            'stock' => 15,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $boleroWomen = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Mestiza Filipiniana Bolero',
            'description' => 'Elegant dress piece',
            'price' => 2400,
            'stock' => 8,
            'status' => 'approved',
            'target_group' => 'Women',
            'CategoryId' => $this->womenCategory->id,
        ]);

        // 1. Search 'barong for men'
        $response = $this->get('/?search=barong+for+men');
        $response->assertStatus(200);
        $response->assertSee('Authentic Polo Barong');

        // 2. Search "men's barong"
        $response = $this->get("/?search=men's+barong");
        $response->assertStatus(200);
        $response->assertSee('Authentic Polo Barong');

        // 3. Search plural "barongs"
        $response = $this->get('/?search=barongs');
        $response->assertStatus(200);
        $response->assertSee('Authentic Polo Barong');

        // 4. Search 'polo barong'
        $response = $this->get('/?search=polo+barong');
        $response->assertStatus(200);
        $response->assertSee('Authentic Polo Barong');

        // 5. Search 'barong for women'
        $response = $this->get('/?search=barong+for+women');
        $response->assertStatus(200);
        $response->assertSee('Mestiza Filipiniana Bolero');

        // 6. Search unrelated keyword
        $response = $this->get('/?search=nonexistentphone123');
        $response->assertStatus(200);
        $response->assertSee('No Products Found');
    }

    public function test_target_audience_detection_for_boys_girls_kids(): void
    {
        $this->assertEquals('Boys', ProductSearchService::detectTargetAudience('barong for boy'));
        $this->assertEquals('Boys', ProductSearchService::detectTargetAudience('boys barong'));
        $this->assertEquals('Girls', ProductSearchService::detectTargetAudience('barong for girl'));
        $this->assertEquals('Girls', ProductSearchService::detectTargetAudience('girls filipiniana'));
        $this->assertEquals('Kids', ProductSearchService::detectTargetAudience('barong for kid'));
        $this->assertEquals('Kids', ProductSearchService::detectTargetAudience('kids collection'));
    }

    public function test_search_boy_and_barong_for_boy_prioritizes_kids_and_excludes_womens_dresses(): void
    {
        $kidsCategory = Category::create([
            'name' => "Boys' Barong",
            'target_group' => ['Kids'],
        ]);

        $boyBarong = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Boys Traditional Mini Barong',
            'description' => 'Tailored for young boys ceremonies',
            'price' => 900,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Kids',
            'CategoryId' => $kidsCategory->id,
        ]);

        $menBarong = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Adult Classic Barong Tagalog',
            'description' => 'Finest Lumban embroidery for men',
            'price' => 2000,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $womenDress = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Duchess Filipiniana Gown',
            'description' => 'Women formal evening dress',
            'price' => 3000,
            'stock' => 5,
            'status' => 'approved',
            'target_group' => 'Women',
            'CategoryId' => $this->womenCategory->id,
        ]);

        // Search 'boy'
        $results = Product::query()->where('status', 'approved')->search('boy')->get();
        $this->assertTrue($results->contains('id', $boyBarong->id), 'Boys Barong should match');
        $this->assertEquals($boyBarong->id, $results->first()->id, 'Kids boy product should rank at top');
        $this->assertFalse($results->contains('id', $womenDress->id), 'Women gown should not match boy');

        // Search 'barong for boy'
        $results2 = Product::query()->where('status', 'approved')->search('barong for boy')->get();
        $this->assertTrue($results2->contains('id', $boyBarong->id));
        $this->assertEquals($boyBarong->id, $results2->first()->id, 'Kids boy product should rank #1 for barong for boy');
        $this->assertFalse($results2->contains('id', $womenDress->id), 'Women gown should not match barong for boy');
    }

    public function test_search_girl_and_barong_for_girl_surfaces_girls_and_filipiniana_attire(): void
    {
        $girlsCategory = Category::create([
            'name' => "Girls' Filipiniana",
            'target_group' => ['Kids'],
        ]);

        $girlDress = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Girls Mini Filipiniana Dress',
            'description' => 'Traditional dress for young girls and school events',
            'price' => 1100,
            'stock' => 8,
            'status' => 'approved',
            'target_group' => 'Kids',
            'CategoryId' => $girlsCategory->id,
        ]);

        $ladyBarong = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Modern Lady Barong Blouse',
            'description' => 'Embroidered barong blouse for women and ladies',
            'price' => 1800,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Women',
            'CategoryId' => $this->womenCategory->id,
        ]);

        $menBarong = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Formal Men Barong Tagalog',
            'description' => 'Classic gentleman attire',
            'price' => 2200,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        // Search 'girl'
        $results = Product::query()->where('status', 'approved')->search('girl')->get();
        $this->assertTrue($results->contains('id', $girlDress->id));
        $this->assertEquals($girlDress->id, $results->first()->id, 'Kids girl dress should rank #1');
        $this->assertFalse($results->contains('id', $menBarong->id), 'Men barong should not match girl');

        // Search 'barong for girl'
        $results2 = Product::query()->where('status', 'approved')->search('barong for girl')->get();
        $this->assertTrue($results2->contains('id', $girlDress->id));
        $this->assertTrue($results2->contains('id', $ladyBarong->id), 'Lady barong should match barong for girl');
        $this->assertFalse($results2->contains('id', $menBarong->id), 'Pure men barong should not match barong for girl');
    }
}


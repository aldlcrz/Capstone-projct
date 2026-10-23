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
        $this->assertTrue($response->viewData('products')->contains('id', $poloMen->id));
        $this->assertFalse($response->viewData('products')->contains('id', $boleroWomen->id));

        // 1b. Search 'men'
        $resMen = $this->get('/?search=men');
        $resMen->assertStatus(200);
        $this->assertTrue($resMen->viewData('products')->contains('id', $poloMen->id));
        $this->assertFalse($resMen->viewData('products')->contains('id', $boleroWomen->id));

        // 2. Search "men's barong"
        $response = $this->get("/?search=men's+barong");
        $response->assertStatus(200);
        $this->assertTrue($response->viewData('products')->contains('id', $poloMen->id));
        $this->assertFalse($response->viewData('products')->contains('id', $boleroWomen->id));

        // 3. Search plural "barongs"
        $response = $this->get('/?search=barongs');
        $response->assertStatus(200);
        $this->assertTrue($response->viewData('products')->contains('id', $poloMen->id));

        // 4. Search 'polo barong'
        $response = $this->get('/?search=polo+barong');
        $response->assertStatus(200);
        $this->assertTrue($response->viewData('products')->contains('id', $poloMen->id));

        // 5. Search 'barong for women'
        $response = $this->get('/?search=barong+for+women');
        $response->assertStatus(200);
        $this->assertTrue($response->viewData('products')->contains('id', $boleroWomen->id));
        $this->assertFalse($response->viewData('products')->contains('id', $poloMen->id));

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

    public function test_controlled_typo_tolerance_recovers_common_misspellings(): void
    {
        $poloBarong = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Polo Barong Premium',
            'description' => 'Classic everyday business attire',
            'price' => 1500,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'fabric_type' => 'Jusi',
            'CategoryId' => $this->menCategory->id,
        ]);

        $jusilynBarong = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Monochrome Jusilyn Barong',
            'description' => 'Fine embroidered jusilyn formal piece',
            'price' => 2200,
            'stock' => 5,
            'status' => 'approved',
            'target_group' => 'Men',
            'fabric_type' => 'Jusilyn',
            'CategoryId' => $this->menCategory->id,
        ]);

        $organzaBolero = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Organza Filipiniana Bolero',
            'description' => 'Delicate organza craftsmanship for ladies',
            'price' => 1900,
            'stock' => 8,
            'status' => 'approved',
            'target_group' => 'Women',
            'fabric_type' => 'Organza',
            'CategoryId' => $this->womenCategory->id,
        ]);

        // 1. 'barng' -> recovers to 'barong'
        $resultsBarng = Product::query()->where('status', 'approved')->search('barng')->get();
        $this->assertTrue($resultsBarng->contains('id', $poloBarong->id), "Typo 'barng' must find Polo Barong");
        $this->assertTrue($resultsBarng->contains('id', $jusilynBarong->id), "Typo 'barng' must find Jusilyn Barong");

        // 2. 'poloo' -> recovers to 'polo'
        $resultsPoloo = Product::query()->where('status', 'approved')->search('poloo')->get();
        $this->assertTrue($resultsPoloo->contains('id', $poloBarong->id), "Typo 'poloo' must find Polo Barong");

        // 3. 'jusli' -> recovers to 'jusi' and matches Jusi / Jusilyn products
        $resultsJusli = Product::query()->where('status', 'approved')->search('jusli')->get();
        $this->assertTrue($resultsJusli->contains('id', $poloBarong->id), "Typo 'jusli' must match Jusi fabric product");
        $this->assertTrue($resultsJusli->contains('id', $jusilynBarong->id), "Typo 'jusli' must match Jusilyn fabric product");

        // 4. 'organz' -> recovers to 'organza'
        $resultsOrganz = Product::query()->where('status', 'approved')->search('organz')->get();
        $this->assertTrue($resultsOrganz->contains('id', $organzaBolero->id), "Typo 'organz' must find Organza bolero");
    }

    public function test_generic_apparel_synonyms_with_audience_awareness(): void
    {
        $menFormalBarong = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Classic Barong Tagalog',
            'description' => 'Finest formal wear and traditional craftsmanship for gentlemen',
            'price' => 3000,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $womenFormalGown = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Duchess Filipiniana Gown',
            'description' => 'Elegant evening dress and formal attire for women',
            'price' => 4500,
            'stock' => 5,
            'status' => 'approved',
            'target_group' => 'Women',
            'CategoryId' => $this->womenCategory->id,
        ]);

        // 1. 'formal clothing for men' -> matches men's barong, excludes women's gown
        $resultsMenClothing = Product::query()->where('status', 'approved')->search('formal clothing for men')->get();
        $this->assertTrue($resultsMenClothing->contains('id', $menFormalBarong->id), "Must match Men's barong for formal clothing for men");
        $this->assertFalse($resultsMenClothing->contains('id', $womenFormalGown->id), "Must NOT include women's gown for men's search");

        // 2. "men's formal wear" -> matches men's barong
        $resultsMenWear = Product::query()->where('status', 'approved')->search("men's formal wear")->get();
        $this->assertTrue($resultsMenWear->contains('id', $menFormalBarong->id), "Must match Men's formal wear");
        $this->assertFalse($resultsMenWear->contains('id', $womenFormalGown->id));

        // 3. "women's formal attire" -> matches women's gown, excludes men's barong
        $resultsWomenAttire = Product::query()->where('status', 'approved')->search("women's formal attire")->get();
        $this->assertTrue($resultsWomenAttire->contains('id', $womenFormalGown->id), "Must match Women's formal attire");
        $this->assertFalse($resultsWomenAttire->contains('id', $menFormalBarong->id));

        // 4. 'traditional Filipino attire' -> neutral generic culture query surfaces both
        $resultsTraditional = Product::query()->where('status', 'approved')->search('traditional Filipino attire')->get();
        $this->assertTrue($resultsTraditional->contains('id', $menFormalBarong->id));
        $this->assertTrue($resultsTraditional->contains('id', $womenFormalGown->id));
    }

    public function test_exact_product_name_priority_over_partial_matches(): void
    {
        $partialMatch = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Long Sleeve Barong Tagalog with Polo Collar',
            'description' => 'Classic embroidery style',
            'price' => 2000,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $exactMatch = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Polo Barong',
            'description' => 'Authentic everyday barong',
            'price' => 1500,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $results = Product::query()->where('status', 'approved')->search('Polo Barong')->get();

        $this->assertGreaterThanOrEqual(2, $results->count());
        $this->assertEquals($exactMatch->id, $results->first()->id, 'Exact product name must rank first with highest score');
    }

    public function test_cross_attribute_matching_across_fabric_collar_and_region(): void
    {
        $specialPiece = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Heritage Formal Piece',
            'description' => 'Handcrafted wedding garment',
            'price' => 3800,
            'stock' => 5,
            'status' => 'approved',
            'target_group' => 'Men',
            'fabric_type' => 'Jusi',
            'collar_type' => 'Mandarin',
            'artisan_region' => 'Lumban',
            'CategoryId' => $this->menCategory->id,
        ]);

        // Search matching across different attributes: fabric + collar + region
        $results = Product::query()->where('status', 'approved')->search('Jusi Mandarin Lumban')->get();
        $this->assertTrue($results->contains('id', $specialPiece->id), 'Must match cross-attribute keywords Jusi Mandarin Lumban');
    }

    public function test_unrelated_queries_prevent_false_positives(): void
    {
        Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Polo Barong Tagalog',
            'description' => 'Fine embroidery',
            'price' => 1600,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $results = Product::query()->where('status', 'approved')->search('automotive replacement battery')->get();
        $this->assertCount(0, $results, 'Unrelated automotive query must return 0 products');

        $results2 = Product::query()->where('status', 'approved')->search('laptop android mechanical keyboard')->get();
        $this->assertCount(0, $results2, 'Unrelated electronic query must return 0 products');
    }

    public function test_artisan_shop_api_search_unification_and_seller_isolation(): void
    {
        $otherSeller = User::factory()->create([
            'role' => 'seller',
            'shopName' => "Rival Artisan Shop",
        ]);

        $sellerAProduct = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Guild Special Polo Barong',
            'description' => 'Authentic guild artisan garment',
            'price' => 1800,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $sellerBProduct = Product::create([
            'sellerId' => $otherSeller->id,
            'name' => 'Rival Exclusive Polo Barong',
            'description' => 'Crafted by competitor',
            'price' => 2000,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        // 1. Search inside Seller A's shop using typo 'barng'
        $responseA = $this->getJson("/api/v1/products?seller={$this->seller->id}&search=barng");
        $responseA->assertStatus(200);
        $dataA = $responseA->json();

        $idsA = collect($dataA)->pluck('id')->all();
        $this->assertContains($sellerAProduct->id, $idsA, "Seller A product must appear when searching inside Seller A shop");
        $this->assertNotContains($sellerBProduct->id, $idsA, "Seller B product MUST NEVER appear when searching inside Seller A shop");

        // 2. Search inside Seller B's shop using typo 'poloo'
        $responseB = $this->getJson("/api/v1/products?seller={$otherSeller->id}&search=poloo");
        $responseB->assertStatus(200);
        $dataB = $responseB->json();

        $idsB = collect($dataB)->pluck('id')->all();
        $this->assertContains($sellerBProduct->id, $idsB, "Seller B product must appear inside Seller B shop");
        $this->assertNotContains($sellerAProduct->id, $idsB, "Seller A product MUST NEVER appear inside Seller B shop");
    }

    public function test_explicit_sort_overrides_relevance_ranking(): void
    {
        $cheapProduct = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Affordable Polo Barong',
            'description' => 'Budget friendly polo barong for men',
            'price' => 999,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $exactHighMatch = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Polo Barong', // Exact name match (+400 relevance points)
            'description' => 'Premium high-end piece',
            'price' => 5999,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        // When NO sort is requested, exact name match ranks first by relevance
        $defaultResults = Product::query()->where('status', 'approved')->search('Polo Barong')->get();
        $this->assertEquals($exactHighMatch->id, $defaultResults->first()->id);

        // When explicit sort 'price_asc' is requested via API, Cheapest product MUST be first
        $responsePriceAsc = $this->getJson("/api/v1/products?search=Polo+Barong&sort=price_asc");
        $responsePriceAsc->assertStatus(200);
        $dataPriceAsc = $responsePriceAsc->json();
        $this->assertEquals($cheapProduct->id, $dataPriceAsc[0]['id'], "Explicit price_asc sort must override relevance score");

        // When explicit sort 'price_desc' is requested, Most expensive MUST be first
        $responsePriceDesc = $this->getJson("/api/v1/products?search=Polo+Barong&sort=price_desc");
        $responsePriceDesc->assertStatus(200);
        $dataPriceDesc = $responsePriceDesc->json();
        $this->assertEquals($exactHighMatch->id, $dataPriceDesc[0]['id'], "Explicit price_desc sort must put highest price first");
    }

    public function test_preorder_and_approved_visibility_rules(): void
    {
        $preorderProduct = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Custom Hand-Embroidered Preorder Barong',
            'description' => 'Made to order traditional barong',
            'price' => 4200,
            'stock' => 0,
            'inventory_mode' => 'preorder',
            'sizes' => ['S', 'M', 'L'],
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $pendingProduct = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Unapproved Pending Barong',
            'description' => 'Should not be visible to customers',
            'price' => 1500,
            'stock' => 10,
            'status' => 'pending',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        // Storefront search
        $results = Product::query()->where('status', 'approved')->search('Barong')->get();
        $this->assertTrue($results->contains('id', $preorderProduct->id), 'Preorder approved products must be searchable and visible');
        $this->assertFalse($results->contains('id', $pendingProduct->id), 'Pending unapproved products must be excluded');

        // HTTP API endpoint
        $response = $this->getJson('/api/v1/products?search=Barong');
        $response->assertStatus(200);
        $ids = collect($response->json())->pluck('id')->all();
        $this->assertContains($preorderProduct->id, $ids);
        $this->assertNotContains($pendingProduct->id, $ids);
    }

    public function test_target_group_boy_and_girl_searches(): void
    {
        $boyProduct = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Junior Barong Tagalog',
            'description' => 'Children celebration barong',
            'price' => 1200,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Boys',
            'CategoryId' => $this->menCategory->id,
        ]);

        $girlProduct = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Little Mestiza Dress',
            'description' => 'Filipiniana attire for young girls',
            'price' => 1400,
            'stock' => 5,
            'status' => 'approved',
            'target_group' => 'Girls',
            'CategoryId' => $this->womenCategory->id,
        ]);

        // 1. Search 'barong for boy'
        $boyResults = Product::query()->where('status', 'approved')->search('barong for boy')->get();
        $this->assertTrue($boyResults->contains('id', $boyProduct->id), 'Must find boy barong');
        $this->assertFalse($boyResults->contains('id', $girlProduct->id), 'Must NOT contain girl product');

        // 2. Search 'barong for girl'
        $girlResults = Product::query()->where('status', 'approved')->search('barong for girl')->get();
        $this->assertTrue($girlResults->contains('id', $girlProduct->id), 'Must find girl attire');
        $this->assertFalse($girlResults->contains('id', $boyProduct->id), 'Must NOT contain boy product');
    }

    public function test_pina_barong_and_split_syllable_organ_z(): void
    {
        $pinaBarong = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Handwoven Piña Barong Tagalog',
            'description' => 'Pure pina fiber traditional barong',
            'price' => 8500,
            'stock' => 3,
            'status' => 'approved',
            'target_group' => 'Men',
            'fabric_type' => 'Piña',
            'CategoryId' => $this->menCategory->id,
        ]);

        $organzaGown = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Organza Evening Bolero',
            'description' => 'Sheer organza with embroidery',
            'price' => 2800,
            'stock' => 4,
            'status' => 'approved',
            'target_group' => 'Women',
            'fabric_type' => 'Organza',
            'CategoryId' => $this->womenCategory->id,
        ]);

        // 1. Search 'pina barong'
        $resultsPina = Product::query()->where('status', 'approved')->search('pina barong')->get();
        $this->assertTrue($resultsPina->contains('id', $pinaBarong->id), "Must match pina barong");

        // 2. Search 'organ z' (accidental space / split syllable)
        $resultsOrganZ = Product::query()->where('status', 'approved')->search('organ z')->get();
        $this->assertTrue($resultsOrganZ->contains('id', $organzaGown->id), "Must recover 'organ z' to organza and find product");
    }

    public function test_price_tier_filters_and_custom_range(): void
    {
        $budget = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Budget Cotton Mask',
            'description' => 'Affordable barong embroidered accessory',
            'price' => 350,
            'stock' => 20,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $midPiece = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Standard Polo Barong',
            'description' => 'Mid tier daily wear',
            'price' => 1200,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $premiumPiece = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'High-end Jusi Barong',
            'description' => 'Luxury handcrafted barong',
            'price' => 7500,
            'stock' => 5,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        // 1. Under 500 via Web route
        $resUnder500 = $this->get('/?price_range=under_500');
        $resUnder500->assertStatus(200);
        $productsUnder500 = $resUnder500->viewData('products');
        $this->assertTrue($productsUnder500->contains('id', $budget->id));
        $this->assertFalse($productsUnder500->contains('id', $midPiece->id));
        $this->assertFalse($productsUnder500->contains('id', $premiumPiece->id));

        // 2. 500 to 1499 via Web route
        $resMid = $this->get('/?price_range=500_1499');
        $resMid->assertStatus(200);
        $productsMid = $resMid->viewData('products');
        $this->assertTrue($productsMid->contains('id', $midPiece->id));
        $this->assertFalse($productsMid->contains('id', $budget->id));
        $this->assertFalse($productsMid->contains('id', $premiumPiece->id));

        // 3. Above 6000 via Web route
        $resLuxury = $this->get('/?price_range=above_6000');
        $resLuxury->assertStatus(200);
        $productsLuxury = $resLuxury->viewData('products');
        $this->assertTrue($productsLuxury->contains('id', $premiumPiece->id));
        $this->assertFalse($productsLuxury->contains('id', $budget->id));
        $this->assertFalse($productsLuxury->contains('id', $midPiece->id));

        // 4. Custom range: min 1000, max 2000 via Web route
        $resCustom = $this->get('/?min_price=1000&max_price=2000');
        $resCustom->assertStatus(200);
        $productsCustom = $resCustom->viewData('products');
        $this->assertTrue($productsCustom->contains('id', $midPiece->id));
        $this->assertFalse($productsCustom->contains('id', $budget->id));
        $this->assertFalse($productsCustom->contains('id', $premiumPiece->id));

        // 5. Test API endpoint with price range
        $resApiUnder500 = $this->getJson('/api/v1/products?price_range=under_500');
        $resApiUnder500->assertStatus(200);
        $apiIds = collect($resApiUnder500->json())->pluck('id')->all();
        $this->assertContains($budget->id, $apiIds);
        $this->assertNotContains($midPiece->id, $apiIds);
        $this->assertNotContains($premiumPiece->id, $apiIds);
    }

    public function test_search_men_strictly_isolates_men_products_and_excludes_women_filipiniana_and_kids(): void
    {
        $weddingBarongMen = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Wedding Barong',
            'description' => 'Classic traditional barong for men wedding ceremony',
            'price' => 600,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $womenBolero = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Women Bolero',
            'description' => 'Elegant embroidered garment for women special events',
            'price' => 600,
            'stock' => 5,
            'status' => 'approved',
            'target_group' => 'Women',
            'CategoryId' => $this->womenCategory->id,
        ]);

        $jusilynFilipiniana = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Jusilyn Filipiniana',
            'description' => 'Recommended garment for formal occasions',
            'price' => 3199,
            'stock' => 5,
            'status' => 'approved',
            'target_group' => 'Women',
            'CategoryId' => $this->womenCategory->id,
        ]);

        $kidsTesting = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'For Testing',
            'description' => 'Fine children garment for ceremonial occasions',
            'price' => 1419.50,
            'stock' => 3,
            'status' => 'approved',
            'target_group' => 'Kids',
        ]);

        // Search 'men'
        $results = Product::query()->where('status', 'approved')->search('men')->get();

        $this->assertTrue($results->contains('id', $weddingBarongMen->id), 'Men wedding barong must be returned');
        $this->assertFalse($results->contains('id', $womenBolero->id), 'Women Bolero must NOT be returned when searching men');
        $this->assertFalse($results->contains('id', $jusilynFilipiniana->id), 'Jusilyn Filipiniana must NOT be returned when searching men');
        $this->assertFalse($results->contains('id', $kidsTesting->id), 'Kids product must NOT be returned when searching men');
    }

    public function test_search_women_strictly_isolates_women_and_excludes_men(): void
    {
        $weddingBarongMen = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Wedding Barong Tagalog',
            'description' => 'Formal piece for gentlemen',
            'price' => 600,
            'stock' => 10,
            'status' => 'approved',
            'target_group' => 'Men',
            'CategoryId' => $this->menCategory->id,
        ]);

        $womenBolero = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Organza Filipiniana Bolero',
            'description' => 'Exquisite women formal attire',
            'price' => 1999,
            'stock' => 5,
            'status' => 'approved',
            'target_group' => 'Women',
            'CategoryId' => $this->womenCategory->id,
        ]);

        $results = Product::query()->where('status', 'approved')->search('women')->get();

        $this->assertTrue($results->contains('id', $womenBolero->id), 'Women bolero must be returned');
        $this->assertFalse($results->contains('id', $weddingBarongMen->id), 'Men barong must NOT be returned when searching women');
    }

    public function test_homepage_collection_tab_highlights_men_when_searching_men(): void
    {
        $response = $this->get('/?search=men');
        $response->assertStatus(200);

        // Men tab should be styled active with #DFC97A and Men text
        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/background-color:\s*#1c1917\s*!important;\s*color:\s*#DFC97A\s*!important;[^>]*>\s*Men\s*<\/a>/i', $content);
    }
}


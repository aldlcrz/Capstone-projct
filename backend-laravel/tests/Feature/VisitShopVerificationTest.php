<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VisitShopVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function visit_shops_index_redirects_gracefully_without_404()
    {
        $response = $this->get('/shops');
        $response->assertRedirect('/?open_shops=1#catalogue-section');

        $response2 = $this->get('/shop');
        $response2->assertRedirect('/?open_shops=1#catalogue-section');
    }

    /** @test */
    public function visit_verified_artisan_shop_by_id_returns_200()
    {
        $seller = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Lumban Heritage Workshop',
        ]);

        $this->get('/shops/' . $seller->id)->assertStatus(200);
        $this->get('/shop/' . $seller->id)->assertStatus(200);
    }

    /** @test */
    public function visit_verified_artisan_shop_by_shop_name_or_slug_returns_200()
    {
        $seller = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Lumban Heritage Embroidery',
        ]);

        // Exact name
        $this->get('/shops/' . urlencode('Lumban Heritage Embroidery'))->assertStatus(200);
        // Slugified name
        $this->get('/shops/Lumban-Heritage-Embroidery')->assertStatus(200);
    }

    /** @test */
    public function visit_shop_via_product_id_fallback_redirects_or_returns_shop()
    {
        $seller = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Artisan Pina Atelier',
        ]);

        $product = Product::create([
            'id'             => (string) Str::uuid(),
            'name'           => 'Artisan Special Barong',
            'sellerId'       => $seller->id,
            'price'          => 2500.00,
            'stock'          => 5,
            'status'         => 'approved',
            'approvalStatus' => 'Approved',
            'category'       => 'Traditional',
            'description'    => 'Fine embroidered barong tagalog',
            'images'         => ['barong.jpg'],
        ]);

        // Passing product ID to /shops/{id} should resolve to the seller's shop
        $this->get('/shops/' . $product->id)->assertStatus(200);
    }

    /** @test */
    public function banner_resolved_button_url_2_never_returns_broken_link()
    {
        $seller = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Laguna Heritage Weavers',
        ]);

        // 1. Banner with specific userId
        $bannerWithUser = new Banner([
            'title'         => 'Heritage Barong',
            'subtitle'      => 'Laguna Heritage Weavers',
            'userId'        => $seller->id,
            'button_url_2'  => '',
            'is_active'     => true,
        ]);
        $this->assertEquals(route('shops.show', ['id' => $seller->id]), $bannerWithUser->getResolvedButtonUrl2());

        // 2. Banner with matching subtitle
        $bannerWithSubtitle = new Banner([
            'title'         => 'Heritage Barong',
            'subtitle'      => 'Laguna Heritage Weavers',
            'userId'        => null,
            'button_url_2'  => '',
            'is_active'     => true,
        ]);
        $this->assertEquals(route('shops.show', ['id' => $seller->id]), $bannerWithSubtitle->getResolvedButtonUrl2());

        // 3. Banner with generic /shops
        $bannerGeneric = new Banner([
            'title'         => 'Heritage Barong',
            'subtitle'      => 'General Collection',
            'userId'        => null,
            'button_url_2'  => '/shops',
            'is_active'     => true,
        ]);
        $this->assertEquals('/?open_shops=1#catalogue-section', $bannerGeneric->getResolvedButtonUrl2());
    }

    /** @test */
    public function get_seller_info_api_endpoint_resolves_all_valid_identifiers()
    {
        $seller = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Laguna Master Embroiderers',
        ]);

        // By ID
        $res1 = $this->getJson('/api/v1/user/seller/' . $seller->id);
        $res1->assertStatus(200);
        $res1->assertJson(['id' => $seller->id, 'shopName' => 'Laguna Master Embroiderers']);

        // By shopName
        $res2 = $this->getJson('/api/v1/user/seller/' . urlencode('Laguna Master Embroiderers'));
        $res2->assertStatus(200);
        $res2->assertJson(['id' => $seller->id]);

        // By slug
        $res3 = $this->getJson('/api/v1/user/seller/Laguna-Master-Embroiderers');
        $res3->assertStatus(200);
        $res3->assertJson(['id' => $seller->id]);
    }
}

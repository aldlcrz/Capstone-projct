<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroBannerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $superadmin;
    protected User $sellerA;
    protected User $sellerB;
    protected Product $productA1;
    protected Product $productA2;
    protected Product $productB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $this->superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin@test.com',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $this->sellerA = User::factory()->create([
            'role' => 'seller',
            'shopName' => 'Artisan Shop Alpha',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $this->sellerB = User::factory()->create([
            'role' => 'seller',
            'shopName' => 'Artisan Shop Beta',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $category = Category::create(['name' => 'Barong']);

        $this->productA1 = Product::create([
            'name' => 'Alpha Barong 1',
            'sellerId' => $this->sellerA->id,
            'CategoryId' => $category->id,
            'price' => 1500,
            'stock' => 10,
            'image' => 'uploads/products/alpha1.jpg',
            'status' => 'approved',
        ]);

        $this->productA2 = Product::create([
            'name' => 'Alpha Barong 2',
            'sellerId' => $this->sellerA->id,
            'CategoryId' => $category->id,
            'price' => 2500,
            'stock' => 5,
            'image' => 'uploads/products/alpha2.jpg',
            'status' => 'approved',
        ]);

        $this->productB1 = Product::create([
            'name' => 'Beta Dress 1',
            'sellerId' => $this->sellerB->id,
            'CategoryId' => $category->id,
            'price' => 3000,
            'stock' => 8,
            'image' => 'uploads/products/beta1.jpg',
            'status' => 'approved',
        ]);
    }

    public function test_sellers_cannot_manage_hero_banners()
    {
        $response = $this->actingAs($this->sellerA)->get(route('admin.banners.index'));
        $response->assertRedirect('/');

        $response = $this->actingAs($this->sellerA)->post(route('admin.banners.store'), [
            'title' => 'Seller Attempted Banner',
            'button_url_1' => '/products/' . $this->productA1->id,
            'preset_image_url' => 'uploads/banners/default.jpg',
        ]);
        $response->assertRedirect('/');
    }


    public function test_admin_can_create_hero_banner_for_a_shop()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.banners.store'), [
            'title' => 'Featured Alpha Product',
            'subtitle' => 'Artisan Shop Alpha',
            'button_text_1' => 'Shop Now',
            'button_url_1' => '/products/' . $this->productA1->id,
            'preset_image_url' => 'uploads/banners/default.jpg',
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('banners', [
            'title' => 'Featured Alpha Product',
            'button_url_1' => '/products/' . $this->productA1->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_create_second_active_banner_for_same_shop()
    {
        // 1st active banner for Seller A
        Banner::create([
            'title' => 'First Banner Alpha',
            'button_url_1' => '/products/' . $this->productA1->id,
            'image_path' => 'uploads/banners/banner1.jpg',
            'is_active' => true,
            'order_index' => 1,
        ]);

        // Attempting 2nd active banner for Seller A with a different product from same shop
        $response = $this->actingAs($this->admin)->post(route('admin.banners.store'), [
            'title' => 'Second Banner Alpha',
            'button_text_1' => 'Shop Now',
            'button_url_1' => '/products/' . $this->productA2->id,
            'preset_image_url' => 'uploads/banners/default.jpg',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors(['button_url_1']);
        $this->assertDatabaseMissing('banners', [
            'title' => 'Second Banner Alpha',
        ]);
    }

    public function test_admin_can_create_active_banner_for_a_different_shop()
    {
        // 1st active banner for Seller A
        Banner::create([
            'title' => 'First Banner Alpha',
            'button_url_1' => '/products/' . $this->productA1->id,
            'image_path' => 'uploads/banners/banner1.jpg',
            'is_active' => true,
            'order_index' => 1,
        ]);

        // Creating active banner for Seller B should succeed (different shop)
        $response = $this->actingAs($this->admin)->post(route('admin.banners.store'), [
            'title' => 'Banner Beta',
            'subtitle' => 'Artisan Shop Beta',
            'button_text_1' => 'Shop Now',
            'button_url_1' => '/products/' . $this->productB1->id,
            'preset_image_url' => 'uploads/banners/default.jpg',
            'is_active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('banners', [
            'title' => 'Banner Beta',
            'is_active' => true,
        ]);
    }

    public function test_cannot_toggle_inactive_banner_to_active_if_shop_already_has_active_banner()
    {
        // Live banner for shop A
        Banner::create([
            'title' => 'Active Banner Alpha',
            'button_url_1' => '/products/' . $this->productA1->id,
            'image_path' => 'uploads/banners/banner1.jpg',
            'is_active' => true,
            'order_index' => 1,
        ]);

        // Inactive banner for shop A
        $inactiveBanner = Banner::create([
            'title' => 'Inactive Banner Alpha',
            'button_url_1' => '/products/' . $this->productA2->id,
            'image_path' => 'uploads/banners/banner2.jpg',
            'is_active' => false,
            'order_index' => 2,
        ]);

        // Try toggling to active
        $response = $this->actingAs($this->admin)->patch(route('admin.banners.toggle', $inactiveBanner->id));
        $response->assertSessionHas('error');

        $inactiveBanner->refresh();
        $this->assertFalse($inactiveBanner->is_active);
    }

    public function test_homepage_deduplicates_and_displays_only_one_product_per_shop()
    {
        // Two active banners pointing to products from shop A
        Banner::create([
            'title' => 'Alpha Featured 1',
            'button_url_1' => '/products/' . $this->productA1->id,
            'image_path' => 'uploads/banners/banner1.jpg',
            'is_active' => true,
            'order_index' => 1,
        ]);

        Banner::create([
            'title' => 'Alpha Featured 2',
            'button_url_1' => '/products/' . $this->productA2->id,
            'image_path' => 'uploads/banners/banner2.jpg',
            'is_active' => true,
            'order_index' => 2,
        ]);

        // One active banner for shop B
        Banner::create([
            'title' => 'Beta Featured 1',
            'button_url_1' => '/products/' . $this->productB1->id,
            'image_path' => 'uploads/banners/banner3.jpg',
            'is_active' => true,
            'order_index' => 3,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);

        /** @var \Illuminate\Support\Collection $displayedBanners */
        $displayedBanners = $response->viewData('banners');

        // Should display 2 banners (1 for shop A, 1 for shop B), not 3
        $this->assertCount(2, $displayedBanners);
        $this->assertEquals('Alpha Featured 1', $displayedBanners[0]->title);
        $this->assertEquals('Beta Featured 1', $displayedBanners[1]->title);
    }
}

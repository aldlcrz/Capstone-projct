<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShopCategoriesCarouselAdminMatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_categories_carousel_matches_admin_categories_exactly(): void
    {
        // Create categories as admin would
        $cat1 = Category::create([
            'id'           => (string) Str::uuid(),
            'name'         => 'Camisa de Chino',
            'description'  => 'Traditional undershirt',
            'target_group' => ['Men'],
            'image'        => '/uploads/categories/camisa_undershirt.png',
        ]);

        $cat2 = Category::create([
            'id'           => (string) Str::uuid(),
            'name'         => 'Lady Barong',
            'description'  => 'Women formal attire',
            'target_group' => ['Women'],
            'image'        => '/uploads/categories/women_lady_barong.png',
        ]);

        $cat3 = Category::create([
            'id'           => (string) Str::uuid(),
            'name'         => 'Custom Artisan Shawl',
            'description'  => 'Handcrafted accessories',
            'target_group' => ['Women'],
            'image'        => '/uploads/categories/accessories.png',
        ]);

        // Visit marketplace home / shop page
        $response = $this->get('/');
        $response->assertOk();

        // 1. Verify "All Barongs" is present as first pill
        $response->assertSee('All Barongs');

        // 2. Verify all categories created in Admin appear with exact names
        $response->assertSee('Camisa de Chino');
        $response->assertSee('Lady Barong');
        $response->assertSee('Custom Artisan Shawl');

        // 3. Verify former hardcoded suffixes are NOT used
        $response->assertDontSee('Camisa de Chino Undershirt');
        $response->assertDontSee('Lady Barong Blouse');

        // 4. Verify images match Admin category getImageUrl()
        $response->assertSee($cat1->getImageUrl());
        $response->assertSee($cat2->getImageUrl());
        $response->assertSee($cat3->getImageUrl());
    }

    public function test_newly_created_admin_category_appears_in_shop_carousel_even_with_zero_products(): void
    {
        $newCat = Category::create([
            'id'           => (string) Str::uuid(),
            'name'         => 'Heritage Bolero',
            'description'  => 'New bolero collection',
            'target_group' => ['Women'],
            'image'        => '/uploads/categories/women_terno.png',
        ]);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Heritage Bolero');
    }

    public function test_deleted_admin_category_disappears_from_shop_carousel(): void
    {
        $cat = Category::create([
            'id'           => (string) Str::uuid(),
            'name'         => 'Temporary Seasonal Collection',
            'target_group' => ['Men'],
            'image'        => '/uploads/categories/polo_casual.png',
        ]);

        $response = $this->get('/');
        $response->assertSee('Temporary Seasonal Collection');

        // Admin deletes category
        $cat->delete();

        $responseAfterDelete = $this->get('/');
        $responseAfterDelete->assertDontSee('Temporary Seasonal Collection');
    }
}

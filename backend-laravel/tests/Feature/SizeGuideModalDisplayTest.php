<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SizeGuideModalDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_size_guide_image_shows_when_seller_has_not_uploaded_size_guide()
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'active',
            'isVerified' => true,
            'shopName' => 'Lumban Masters',
            'size_guides' => null,
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Classic Barong Tagalog',
            'sellerId' => $seller->id,
            'price' => 1500.00,
            'stock' => 10,
            'status' => 'approved',
            'size_guide_image' => null,
            'target_group' => 'Men',
            'description' => 'Quality artisan barong',
        ]);

        $response = $this->get(route('products.show', $product->id));

        $response->assertStatus(200);
        // Default size guide image must be present
        $response->assertSee('uploads/size-guides/size_guide_men.png', false);
        $response->assertSee('Barong & Filipiniana Size Guide Reference', false);
        // Custom artisan guide badge must not be present
        $response->assertDontSee('Custom Artisan Guide', false);
    }

    public function test_uploaded_size_guide_shows_and_default_is_hidden_when_seller_uploads_guide()
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'active',
            'isVerified' => true,
            'shopName' => 'Lumban Masters',
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Classic Barong Tagalog',
            'sellerId' => $seller->id,
            'price' => 1500.00,
            'stock' => 10,
            'status' => 'approved',
            'size_guide_image' => 'products/sizeguides/artisan_custom_chart.png',
            'target_group' => 'Men',
            'description' => 'Quality artisan barong',
        ]);

        $response = $this->get(route('products.show', $product->id));

        $response->assertStatus(200);
        // Custom size guide image must be present
        $response->assertSee('/storage/products/sizeguides/artisan_custom_chart.png', false);
        $response->assertSee("Artisan's Custom Size Chart", false);
        $response->assertSee('Custom Artisan Guide', false);

        // Default size guide reference tabs must be HIDDEN
        $response->assertDontSee('Barong & Filipiniana Size Guide Reference', false);
        $response->assertDontSee('uploads/size-guides/size_guide_men.png', false);
    }

    public function test_category_uploaded_guide_shows_and_unprovided_category_shows_default()
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'active',
            'isVerified' => true,
            'shopName' => 'Lumban Masters',
            'size_guides' => [
                'Men' => 'uploads/sizeguides/custom_men_artisan.png',
            ],
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Classic Barong Tagalog',
            'sellerId' => $seller->id,
            'price' => 1500.00,
            'stock' => 10,
            'status' => 'approved',
            'size_guide_image' => null,
            'target_group' => 'Men',
            'description' => 'Quality artisan barong',
        ]);

        $response = $this->get(route('products.show', $product->id));

        $response->assertStatus(200);
        // Men shows custom uploaded guide
        $response->assertSee('uploads/sizeguides/custom_men_artisan.png', false);
        $response->assertDontSee('uploads/size-guides/size_guide_men.png', false);

        // Women was not uploaded, so Women shows default guide
        $response->assertSee('uploads/size-guides/size_guide_women.png', false);
    }
}

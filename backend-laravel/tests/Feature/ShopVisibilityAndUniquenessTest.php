<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ShopVisibilityAndUniquenessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function pending_or_unverified_seller_shops_do_not_appear_in_top_rated_shops()
    {
        // 1. Create a pending/unverified seller
        $pendingSeller = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'pending',
            'isVerified' => false,
            'shopName'   => 'Hidden Pending Atelier',
        ]);

        // 2. Create an active, verified seller
        $verifiedSeller = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Visible Master Artisan',
        ]);

        // 3. Visit the home / welcome page
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Visible Master Artisan');
        $response->assertDontSee('Hidden Pending Atelier');
    }

    /** @test */
    public function public_users_cannot_access_unverified_pending_artisan_shop_page()
    {
        $pendingSeller = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'pending',
            'isVerified' => false,
            'shopName'   => 'Unverified Shop 123',
        ]);

        $verifiedSeller = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Verified Shop 456',
        ]);

        // Unverified shop returns 404 for guests
        $this->get('/shops/' . $pendingSeller->id)->assertStatus(404);
        $this->get('/shop/' . $pendingSeller->id)->assertStatus(404);

        // Verified shop returns 200
        $this->get('/shops/' . $verifiedSeller->id)->assertStatus(200);
        $this->get('/shop/' . $verifiedSeller->id)->assertStatus(200);
    }

    /** @test */
    public function check_shop_name_endpoint_detects_duplicates()
    {
        User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Lumban Heritage Barong',
        ]);

        // Exact match
        $res = $this->getJson('/auth/check-shop-name?name=Lumban+Heritage+Barong');
        $res->assertStatus(200);
        $res->assertJson([
            'available' => false,
        ]);

        // Case-insensitive match
        $res2 = $this->getJson('/auth/check-shop-name?name=lumban+heritage+barong');
        $res2->assertStatus(200);
        $res2->assertJson([
            'available' => false,
        ]);

        // Available name
        $res3 = $this->getJson('/auth/check-shop-name?name=Brand+New+Unique+Shop');
        $res3->assertStatus(200);
        $res3->assertJson([
            'available' => true,
        ]);
    }

    /** @test */
    public function seller_registration_rejects_duplicate_shop_name()
    {
        User::factory()->create([
            'role'     => 'seller',
            'shopName' => 'Existing Workshop',
        ]);

        $res = $this->post('/seller/register', [
            'name'                 => 'Artisan Bob',
            'email'                => 'bob@gmail.com',
            'password'             => 'password123',
            'password_confirmation'=> 'password123',
            'shopName'             => 'Existing Workshop',
            'terms_consent'        => '1',
            'residencyCertificate' => UploadedFile::fake()->create('residency.pdf', 100, 'application/pdf'),
            'birDocument'          => UploadedFile::fake()->create('bir.pdf', 100, 'application/pdf'),
            'businessPermit'       => UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf'),
        ]);

        $res->assertSessionHasErrors('shopName');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerRegistrationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function unverified_seller_in_awaiting_email_verification_does_not_appear_in_superadmin_pending_queue()
    {
        /** @var User $superadmin */
        $superadmin = User::factory()->create([
            'role'       => 'superadmin',
            'status'     => 'active',
            'isVerified' => true,
        ]);

        $awaitingSeller = User::factory()->create([
            'name'                    => 'Ghost Seller',
            'email'                   => 'ghost@example.com',
            'shopName'                => 'Ghost Atelier',
            'role'                    => 'seller',
            'status'                  => 'awaiting_email_verification',
            'isVerified'              => false,
            'email_verified_at'       => null,
            'registration_expires_at' => now()->addHours(2),
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.sellers', ['filter' => 'pending']));

        $response->assertStatus(200);
        $response->assertDontSee('Ghost Atelier');
        $response->assertDontSee('Ghost Seller');
    }

    /** @test */
    public function verified_seller_with_status_pending_appears_in_superadmin_pending_queue()
    {
        /** @var User $superadmin */
        $superadmin = User::factory()->create([
            'role'       => 'superadmin',
            'status'     => 'active',
            'isVerified' => true,
        ]);

        $pendingSeller = User::factory()->create([
            'name'                    => 'Legitimate Applicant',
            'email'                   => 'legit@example.com',
            'shopName'                => 'Legitimate Atelier',
            'role'                    => 'seller',
            'status'                  => 'pending',
            'isVerified'              => false,
            'email_verified_at'       => now(),
            'registration_expires_at' => null,
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.sellers', ['filter' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('Legitimate Atelier');
        $response->assertSee('Legitimate Applicant');
    }

    /** @test */
    public function active_seller_blocks_shop_name()
    {
        User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Lumban Master Craft',
        ]);

        $response = $this->getJson('/auth/check-shop-name?name=' . urlencode('Lumban Master Craft'));

        $response->assertStatus(200);
        $response->assertJson([
            'available' => false,
        ]);
    }

    /** @test */
    public function pending_verified_seller_blocks_shop_name()
    {
        User::factory()->create([
            'role'              => 'seller',
            'status'            => 'pending',
            'isVerified'        => false,
            'email_verified_at' => now(),
            'shopName'          => 'Pending Heritage Shop',
        ]);

        $response = $this->getJson('/auth/check-shop-name?name=' . urlencode('pending heritage shop'));

        $response->assertStatus(200);
        $response->assertJson([
            'available' => false,
        ]);
    }

    /** @test */
    public function fresh_awaiting_email_verification_temporarily_blocks_shop_name()
    {
        User::factory()->create([
            'role'                    => 'seller',
            'status'                  => 'awaiting_email_verification',
            'isVerified'              => false,
            'email_verified_at'       => null,
            'shopName'                => 'Temporary Hold Shop',
            'registration_expires_at' => now()->addHours(2),
        ]);

        $response = $this->getJson('/auth/check-shop-name?name=' . urlencode('Temporary Hold Shop'));

        $response->assertStatus(200);
        $response->assertJson([
            'available' => false,
        ]);
    }

    /** @test */
    public function expired_awaiting_email_verification_releases_shop_name()
    {
        User::factory()->create([
            'role'                    => 'seller',
            'status'                  => 'awaiting_email_verification',
            'isVerified'              => false,
            'email_verified_at'       => null,
            'shopName'                => 'Abandoned Shop Name',
            'registration_expires_at' => now()->subMinutes(5),
        ]);

        $response = $this->getJson('/auth/check-shop-name?name=' . urlencode('Abandoned Shop Name'));

        $response->assertStatus(200);
        $response->assertJson([
            'available' => true,
        ]);
    }

    /** @test */
    public function rejected_seller_releases_shop_name()
    {
        User::factory()->create([
            'role'       => 'seller',
            'status'     => 'rejected',
            'isVerified' => false,
            'shopName'   => 'Rejected Artisan Shop',
        ]);

        $response = $this->getJson('/auth/check-shop-name?name=' . urlencode('Rejected Artisan Shop'));

        $response->assertStatus(200);
        $response->assertJson([
            'available' => true,
        ]);
    }

    /** @test */
    public function expire_command_transitions_stale_awaiting_registrations_to_expired()
    {
        $staleSeller = User::factory()->create([
            'role'                    => 'seller',
            'status'                  => 'awaiting_email_verification',
            'isVerified'              => false,
            'email_verified_at'       => null,
            'registration_expires_at' => now()->subHours(3),
        ]);

        $activeRegistration = User::factory()->create([
            'role'                    => 'seller',
            'status'                  => 'awaiting_email_verification',
            'isVerified'              => false,
            'email_verified_at'       => null,
            'registration_expires_at' => now()->addHour(),
        ]);

        $this->artisan('sellers:expire-registrations')
            ->assertExitCode(0);

        $this->assertEquals('expired', $staleSeller->fresh()->status);
        $this->assertEquals('awaiting_email_verification', $activeRegistration->fresh()->status);
    }

    /** @test */
    public function expire_command_can_target_specific_email()
    {
        $targetSeller = User::factory()->create([
            'email'                   => 'john112004@gmail.com',
            'role'                    => 'seller',
            'status'                  => 'pending',
            'isVerified'              => false,
            'shopName'                => 'Goww embroidery',
        ]);

        $this->artisan('sellers:expire-registrations', ['--email' => 'john112004@gmail.com'])
            ->assertExitCode(0);

        $this->assertEquals('expired', $targetSeller->fresh()->status);

        // Shop name should now be available
        $response = $this->getJson('/auth/check-shop-name?name=' . urlencode('Goww embroidery'));
        $response->assertStatus(200);
        $response->assertJson(['available' => true]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FirstLoginGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_has_not_seen_guide_by_default()
    {
        $user = User::create([
            'name'       => 'Juan Dela Cruz',
            'email'      => 'juan@example.com',
            'password'   => Hash::make('Password123!'),
            'role'       => 'customer',
            'isVerified' => true,
            'status'     => 'active',
            'has_seen_guide' => false,
        ]);

        $this->assertFalse($user->hasSeenGuide());
    }

    public function test_first_login_flashes_show_first_login_guide()
    {
        $user = User::create([
            'name'       => 'Juan Dela Cruz',
            'email'      => 'juan@example.com',
            'password'   => Hash::make('Password123!'),
            'role'       => 'customer',
            'isVerified' => true,
            'status'     => 'active',
            'has_seen_guide' => false,
        ]);

        $response = $this->post('/login', [
            'email'    => 'juan@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHas('show_first_login_guide', true);
    }

    public function test_subsequent_login_does_not_flash_show_first_login_guide()
    {
        $user = User::create([
            'name'       => 'Maria Clara',
            'email'      => 'maria@example.com',
            'password'   => Hash::make('Password123!'),
            'role'       => 'customer',
            'isVerified' => true,
            'status'     => 'active',
            'has_seen_guide' => true,
        ]);

        $response = $this->post('/login', [
            'email'    => 'maria@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertSessionMissing('show_first_login_guide');
    }

    public function test_guide_dismiss_endpoint_marks_guide_as_seen()
    {
        $user = User::create([
            'name'       => 'Crisostomo Ibarra',
            'email'      => 'ibarra@example.com',
            'password'   => Hash::make('Password123!'),
            'role'       => 'customer',
            'isVerified' => true,
            'status'     => 'active',
            'has_seen_guide' => false,
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/guide/dismiss', [
            'tourId' => 'customer',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $user->refresh();
        $this->assertTrue((bool)$user->has_seen_guide);
        $this->assertTrue($user->hasSeenGuide());
    }

    public function test_seller_first_login_flashes_guide_flag()
    {
        $seller = User::create([
            'name'       => 'Artisan Seller',
            'email'      => 'artisan@example.com',
            'password'   => Hash::make('Password123!'),
            'role'       => 'seller',
            'isVerified' => true,
            'status'     => 'active',
            'is_onboarded' => true,
            'has_seen_guide' => false,
        ]);

        $response = $this->post('/login', [
            'email'    => 'artisan@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHas('show_first_login_guide', true);
        $response->assertRedirect(route('seller.dashboard'));
    }

    public function test_verification_code_expires_in_5_minutes()
    {
        $verification = \App\Services\EmailNotificationService::createVerificationCode('timer.test@gmail.com', 'registration');
        
        $this->assertEquals(5, round(now()->diffInMinutes($verification->expires_at)));
        $this->assertFalse($verification->isExpired());

        $this->travel(6)->minutes();
        $this->assertTrue($verification->isExpired());
    }

    public function test_resend_is_blocked_until_code_expires()
    {
        $user = User::create([
            'name'       => 'Timer User',
            'email'      => 'timer.block@gmail.com',
            'password'   => Hash::make('Password123!'),
            'role'       => 'customer',
            'isVerified' => false,
            'status'     => 'active',
        ]);

        $verification = \App\Services\EmailNotificationService::createVerificationCode($user->email, 'registration');

        // Immediately requesting resend while active should fail
        $response = $this->post('/resend-verification', [
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('code');

        // Fast forward past the 5-minute expiration
        $this->travel(6)->minutes();

        $response2 = $this->post('/resend-verification', [
            'email' => $user->email,
        ]);

        $response2->assertSessionHas('success');
    }
}

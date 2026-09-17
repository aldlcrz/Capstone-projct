<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailVerification;
use App\Services\EmailNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegistrationVerificationAnalysisTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Customer Happy Path:
     * 1. Fill registration form
     * 2. Receive 6-digit OTP in session/database
     * 3. Submit OTP
     * 4. User is created in database, logged in, and marked active + verified
     */
    public function test_customer_registration_happy_path(): void
    {
        $email = 'customer.test@gmail.com';

        // 1. Submit Registration
        $response = $this->post('/register', [
            'name'                  => 'Maria Clara',
            'email'                 => $email,
            'password'              => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms_consent'         => '1',
        ]);

        $response->assertRedirect(route('verify.email'));
        
        // User record is created in DB with isVerified = false, status = 'pending'
        $user = User::where('email', $email)->first();
        $this->assertNotNull($user, 'User record must exist in DB upon registration');
        $this->assertEquals('customer', $user->role);
        $this->assertEquals('pending', $user->status);
        $this->assertFalse((bool)$user->isVerified);

        $verification = EmailVerification::where('email', $email)->where('type', 'registration')->first();
        $this->assertNotNull($verification, 'Verification code must be created');
        $this->assertEquals(6, strlen($verification->code));

        // 2. Submit Verification Code
        $verifyResponse = $this->post('/verify-email', [
            'email' => $email,
            'code'  => $verification->code,
        ]);

        $verifyResponse->assertRedirect('/');
        $this->assertAuthenticated();

        $user->refresh();
        $this->assertEquals('customer', $user->role);
        $this->assertEquals('active', $user->status);
        $this->assertTrue((bool)$user->isVerified);
    }

    /**
     * Customer Validation Errors:
     * - Name with special symbols rejected
     * - Password without numbers rejected
     * - Password under 6 characters rejected
     * - Terms not accepted rejected
     */
    public function test_customer_registration_validation_rules(): void
    {
        // Name with disallowed characters
        $res = $this->post('/register', [
            'name'                  => 'John <script>',
            'email'                 => 'valid@gmail.com',
            'password'              => 'Pass123',
            'password_confirmation' => 'Pass123',
            'terms_consent'         => '1',
        ]);
        $res->assertSessionHasErrors('name');

        // Password without numbers
        $res2 = $this->post('/register', [
            'name'                  => 'Valid Name',
            'email'                 => 'valid2@gmail.com',
            'password'              => 'PasswordOnly',
            'password_confirmation' => 'PasswordOnly',
            'terms_consent'         => '1',
        ]);
        $res2->assertSessionHasErrors('password');

        // Password too short (< 6 chars)
        $res3 = $this->post('/register', [
            'name'                  => 'Valid Name',
            'email'                 => 'valid3@gmail.com',
            'password'              => 'P1',
            'password_confirmation' => 'P1',
            'terms_consent'         => '1',
        ]);
        $res3->assertSessionHasErrors('password');

        // Missing terms consent
        $res4 = $this->post('/register', [
            'name'                  => 'Valid Name',
            'email'                 => 'valid4@gmail.com',
            'password'              => 'Pass123',
            'password_confirmation' => 'Pass123',
        ]);
        $res4->assertSessionHasErrors('terms_consent');
    }

    /**
     * Seller Happy Path:
     * 1. Submit seller registration with documents
     * 2. User created in DB with status=pending, isVerified=false
     * 3. Submit email OTP
     * 4. Redirected to login awaiting admin approval
     */
    public function test_seller_registration_happy_path_and_email_verification(): void
    {
        $email = 'artisan.seller@gmail.com';

        $response = $this->post('/seller/register', [
            'name'                 => 'Artisan Juan',
            'email'                => $email,
            'password'             => 'Artisan123',
            'password_confirmation'=> 'Artisan123',
            'shopName'             => 'Juan Embroidery Workshop',
            'residencyCertificate'=> UploadedFile::fake()->create('residency.pdf', 500, 'application/pdf'),
            'businessPermit'       => UploadedFile::fake()->image('permit.jpg'),
            'birDocument'          => UploadedFile::fake()->create('bir.pdf', 500, 'application/pdf'),
            'terms_consent'        => '1',
        ]);

        $response->assertRedirect(route('verify.email'));

        $seller = User::where('email', $email)->first();
        $this->assertNotNull($seller);
        $this->assertEquals('seller', $seller->role);
        $this->assertFalse((bool)$seller->isVerified);

        $verification = EmailVerification::where('email', $email)->where('type', 'registration')->first();
        $this->assertNotNull($verification);

        // Submit email verification
        $verifyResponse = $this->post('/verify-email', [
            'email' => $email,
            'code'  => $verification->code,
        ]);

        $verifyResponse->assertRedirect(route('login'));
        $this->assertGuest(); // Seller is logged out while awaiting admin approval
    }

    /**
     * Seller Duplicate Shop Name Rejection:
     */
    public function test_seller_duplicate_shop_name_is_rejected(): void
    {
        User::factory()->create([
            'role'     => 'seller',
            'shopName' => 'Lumban Heritage Haven',
        ]);

        $response = $this->post('/seller/register', [
            'name'                 => 'Another Artisan',
            'email'                => 'another@gmail.com',
            'password'             => 'Password123',
            'password_confirmation'=> 'Password123',
            'shopName'             => 'lumban heritage haven', // case-insensitive duplicate
            'residencyCertificate'=> UploadedFile::fake()->create('residency.pdf', 500),
            'businessPermit'       => UploadedFile::fake()->image('permit.jpg'),
            'birDocument'          => UploadedFile::fake()->create('bir.pdf', 500),
            'terms_consent'        => '1',
        ]);

        $response->assertSessionHasErrors('shopName');
    }

    /**
     * OTP brute-force lockout after 5 attempts
     */
    public function test_verification_code_lockout_after_5_failed_attempts(): void
    {
        $email = 'lockout.test@gmail.com';
        $verification = EmailNotificationService::createVerificationCode($email, 'registration');

        for ($i = 0; $i < 5; $i++) {
            $valid = EmailNotificationService::verifyCode($email, '000000', 'registration');
            $this->assertFalse($valid);
        }

        // After 5 failed attempts, even the correct code should fail because record is purged
        $validCorrect = EmailNotificationService::verifyCode($email, $verification->code, 'registration');
        $this->assertFalse($validCorrect);
        $this->assertDatabaseMissing('email_verifications', ['email' => $email]);
    }

    /**
     * Test API registration blocks privilege escalation (admin role disallowed)
     */
    public function test_api_registration_blocks_admin_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Hacker Attempt',
            'email'    => 'hacker@lumbarong.ph',
            'password' => 'Hacker123!',
            'role'     => 'admin',
        ]);

        $response->assertStatus(400);
        $this->assertDatabaseMissing('users', ['email' => 'hacker@lumbarong.ph']);
    }

    /**
     * Test unverified seller attempting login is redirected to verify-email
     */
    public function test_unverified_seller_login_redirected_to_verify_email(): void
    {
        $email = 'unverified.seller@gmail.com';

        // Register seller
        $this->post('/seller/register', [
            'name'                 => 'Artisan Unverified',
            'email'                => $email,
            'password'             => 'Secret123',
            'password_confirmation'=> 'Secret123',
            'shopName'             => 'Unverified Atelier',
            'residencyCertificate'=> UploadedFile::fake()->create('residency.pdf', 500),
            'businessPermit'       => UploadedFile::fake()->image('permit.jpg'),
            'birDocument'          => UploadedFile::fake()->create('bir.pdf', 500),
            'terms_consent'        => '1',
        ]);

        // Attempt login without verifying OTP
        $response = $this->post('/login', [
            'email'    => $email,
            'password' => 'Secret123',
        ]);

        $response->assertRedirect(route('verify.email'));
        $this->assertGuest();
    }

    /**
     * Test resend is blocked while current 5-minute code is still active
     */
    public function test_resend_blocked_while_code_active(): void
    {
        $email = 'cooldown.test@gmail.com';

        // 1. Initial registration
        $this->post('/register', [
            'name'                  => 'Cooldown User',
            'email'                 => $email,
            'password'              => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms_consent'         => '1',
        ]);

        // 2. Immediately attempt resend while active
        $resend = $this->post(route('verify.email.resend'), ['email' => $email]);
        $resend->assertSessionHasErrors('code');
    }

    /**
     * Test resend allowed after 5-minute code expires and overwrites the verification record in place
     */
    public function test_resend_allowed_after_code_expires_and_overwrites_code(): void
    {
        $email = 'overwrite.test@gmail.com';

        // 1. Initial registration
        $this->post('/register', [
            'name'                  => 'Overwrite User',
            'email'                 => $email,
            'password'              => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms_consent'         => '1',
        ]);

        $initialVerification = EmailVerification::where('email', $email)->where('type', 'registration')->first();
        $this->assertEquals(0, $initialVerification->resend_count);

        // Fast-forward expires_at so the code is expired
        $initialVerification->update(['expires_at' => now()->subMinute()]);

        // 2. Request resend
        $resend = $this->post(route('verify.email.resend'), ['email' => $email]);
        $resend->assertSessionHas('success');

        // Verify only 1 record exists (not duplicated or deleted)
        $this->assertEquals(1, EmailVerification::where('email', $email)->where('type', 'registration')->count());

        $updatedVerification = EmailVerification::where('email', $email)->where('type', 'registration')->first();
        $this->assertEquals(1, $updatedVerification->resend_count);
        $this->assertTrue($updatedVerification->expires_at->gt(now()->addMinutes(4)));
    }

    /**
     * Test maximum 5 resends per rolling 1-hour window
     */
    public function test_resend_hourly_rate_limit_blocks_after_5_resends(): void
    {
        $email = 'ratelimit.test@gmail.com';

        // 1. Initial registration
        $this->post('/register', [
            'name'                  => 'Rate Limit User',
            'email'                 => $email,
            'password'              => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms_consent'         => '1',
        ]);

        $verification = EmailVerification::where('email', $email)->where('type', 'registration')->first();

        // Simulate 5 resends within the same hour and code expired
        $verification->update([
            'resend_count'             => 5,
            'resend_window_started_at' => now()->subMinutes(10),
            'expires_at'               => now()->subMinute(),
        ]);

        // 6th resend request should be blocked
        $resend = $this->post(route('verify.email.resend'), ['email' => $email]);
        $resend->assertSessionHasErrors('code');
    }

    /**
     * Test hourly window resets after 1 hour passes
     */
    public function test_resend_hourly_window_resets_after_1_hour(): void
    {
        $email = 'resetwindow.test@gmail.com';

        $this->post('/register', [
            'name'                  => 'Reset User',
            'email'                 => $email,
            'password'              => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms_consent'         => '1',
        ]);

        $verification = EmailVerification::where('email', $email)->where('type', 'registration')->first();

        // Simulate 5 resends, but window started 65 minutes ago (expired window), and code expired
        $verification->update([
            'resend_count'             => 5,
            'resend_window_started_at' => now()->subMinutes(65),
            'expires_at'               => now()->subMinute(),
        ]);

        // Resend request should be allowed and reset resend_count to 1
        $resend = $this->post(route('verify.email.resend'), ['email' => $email]);
        $resend->assertSessionHas('success');

        $refreshed = EmailVerification::where('email', $email)->where('type', 'registration')->first();
        $this->assertEquals(1, $refreshed->resend_count);
    }

    /**
     * Test unverified customer attempting login is redirected to verify-email
     */
    public function test_unverified_customer_login_redirected_to_verify_email(): void
    {
        $email = 'unverified.customer@gmail.com';

        $this->post('/register', [
            'name'                  => 'Pending Customer',
            'email'                 => $email,
            'password'              => 'Secret123',
            'password_confirmation' => 'Secret123',
            'terms_consent'         => '1',
        ]);

        // Attempt login without verifying OTP
        $response = $this->post('/login', [
            'email'    => $email,
            'password' => 'Secret123',
        ]);

        $response->assertRedirect(route('verify.email'));
        $this->assertGuest();
    }
}


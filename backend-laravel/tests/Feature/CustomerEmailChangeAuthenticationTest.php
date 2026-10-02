<?php

namespace Tests\Feature;

use App\Models\EmailVerification;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerEmailChangeAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function createCustomer(array $attrs = []): User
    {
        return User::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => 'Maria Clara',
            'username' => 'mariaclara_' . Str::random(5),
            'email' => 'old_customer@gmail.com',
            'password' => Hash::make('SecretPass123!'),
            'role' => 'customer',
            'status' => 'active',
            'isVerified' => true,
            'email_verified_at' => now(),
            'sessionVersion' => 1,
            'remember_token' => Str::random(60),
            'googleId' => 'google-sub-123456',
        ], $attrs));
    }

    public function test_successful_email_change_allows_login_with_new_email_and_blocks_old_email()
    {
        $user = $this->createCustomer([
            'email' => 'old@gmail.com',
        ]);

        $this->actingAs($user);

        // Step 1: Initiate email change to new email
        $initResponse = $this->postJson(route('profile.email.initiate'), [
            'new_email' => 'new@gmail.com',
        ]);
        $initResponse->assertOk();
        $initResponse->assertJson(['status' => 'success', 'step' => 2]);

        // Fetch verification code generated for OLD email
        $oldVerification = EmailVerification::where('email', 'old@gmail.com')
            ->where('type', 'email_change_old')
            ->first();
        $this->assertNotNull($oldVerification);

        // Step 2: Verify old email OTP
        $verifyOldResponse = $this->postJson(route('profile.email.verify-old'), [
            'code' => $oldVerification->code,
        ]);
        $verifyOldResponse->assertOk();
        $verifyOldResponse->assertJson(['status' => 'success', 'step' => 3]);

        // Fetch verification code generated for NEW email
        $newVerification = EmailVerification::where('email', 'new@gmail.com')
            ->where('type', 'email_change_new')
            ->first();
        $this->assertNotNull($newVerification);

        // Step 3: Verify new email OTP
        $verifyNewResponse = $this->postJson(route('profile.email.verify-new'), [
            'code' => $newVerification->code,
        ]);
        $verifyNewResponse->assertOk();
        $verifyNewResponse->assertJson(['status' => 'success', 'new_email' => 'new@gmail.com']);

        // Verify database state
        $user->refresh();
        $this->assertEquals('new@gmail.com', $user->email);
        $this->assertNull($user->googleId); // Unlinked old Google ID
        $this->assertEquals(2, $user->sessionVersion);

        // Log out
        Auth::logout();
        $this->assertGuest();

        // 1. Attempt login with OLD email -> MUST FAIL
        $oldLoginResponse = $this->from(route('login'))->post(route('login.post'), [
            'email' => 'old@gmail.com',
            'password' => 'SecretPass123!',
        ]);
        $oldLoginResponse->assertSessionHasErrors(['email']);
        $this->assertGuest();

        // 2. Attempt login with NEW email -> MUST SUCCEED
        $newLoginResponse = $this->post(route('login.post'), [
            'email' => 'new@gmail.com',
            'password' => 'SecretPass123!',
        ]);
        $newLoginResponse->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_duplicate_email_is_rejected_at_initiation_and_verification()
    {
        $existingOtherUser = $this->createCustomer([
            'email' => 'existing_taken@gmail.com',
        ]);

        $user = $this->createCustomer([
            'email' => 'current_user@gmail.com',
        ]);

        $this->actingAs($user);

        // Try to initiate change to already registered email
        $initResponse = $this->postJson(route('profile.email.initiate'), [
            'new_email' => 'existing_taken@gmail.com',
        ]);
        $initResponse->assertStatus(422);
        $initResponse->assertJson(['status' => 'error']);

        // Database email remains unchanged
        $user->refresh();
        $this->assertEquals('current_user@gmail.com', $user->email);
    }

    public function test_unverified_email_change_does_not_replace_canonical_email()
    {
        $user = $this->createCustomer([
            'email' => 'canonical@gmail.com',
        ]);

        $this->actingAs($user);

        // Step 1: Initiate change
        $this->postJson(route('profile.email.initiate'), [
            'new_email' => 'unverified_new@gmail.com',
        ]);

        // User leaves flow without verifying code (unverified email change)
        $user->refresh();
        $this->assertEquals('canonical@gmail.com', $user->email);

        Auth::logout();

        // Login with unverified new email must fail
        $loginUnverified = $this->from(route('login'))->post(route('login.post'), [
            'email' => 'unverified_new@gmail.com',
            'password' => 'SecretPass123!',
        ]);
        $loginUnverified->assertSessionHasErrors(['email']);
        $this->assertGuest();

        // Login with canonical email must still succeed
        $loginCanonical = $this->post(route('login.post'), [
            'email' => 'canonical@gmail.com',
            'password' => 'SecretPass123!',
        ]);
        $loginCanonical->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_failed_or_expired_verification_code_does_not_change_email()
    {
        $user = $this->createCustomer([
            'email' => 'verified_owner@gmail.com',
        ]);

        $this->actingAs($user);

        // Initiate change
        $this->postJson(route('profile.email.initiate'), [
            'new_email' => 'target_new@gmail.com',
        ]);

        $oldVerification = EmailVerification::where('email', 'verified_owner@gmail.com')
            ->where('type', 'email_change_old')
            ->first();

        // Try invalid code on step 2
        $invalidResponse = $this->postJson(route('profile.email.verify-old'), [
            'code' => '000000',
        ]);
        $invalidResponse->assertStatus(422);

        // Expire the code
        $oldVerification->update(['expires_at' => now()->subMinutes(10)]);

        $expiredResponse = $this->postJson(route('profile.email.verify-old'), [
            'code' => $oldVerification->code,
        ]);
        $expiredResponse->assertStatus(422);

        // User email remains unmodified
        $user->refresh();
        $this->assertEquals('verified_owner@gmail.com', $user->email);
    }

    public function test_email_normalization_and_whitespace_trimming()
    {
        $user = $this->createCustomer([
            'email' => 'normalize_test@gmail.com',
        ]);

        $this->actingAs($user);

        // Initiate with mixed case and leading/trailing whitespace
        $initResponse = $this->postJson(route('profile.email.initiate'), [
            'new_email' => '  Normalize_New@GMAIL.COM  ',
        ]);
        $initResponse->assertOk();

        $oldVerification = EmailVerification::where('email', 'normalize_test@gmail.com')->first();
        $this->postJson(route('profile.email.verify-old'), ['code' => $oldVerification->code]);

        $newVerification = EmailVerification::where('email', 'normalize_new@gmail.com')->first();
        $this->postJson(route('profile.email.verify-new'), ['code' => $newVerification->code]);

        $user->refresh();
        $this->assertEquals('normalize_new@gmail.com', $user->email);

        Auth::logout();

        // Login with uppercase/whitespace is normalized and succeeds
        $loginResponse = $this->post(route('login.post'), [
            'email' => '  Normalize_New@GMAIL.COM  ',
            'password' => 'SecretPass123!',
        ]);
        $loginResponse->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_cannot_initiate_email_change_with_same_email()
    {
        $user = $this->createCustomer([
            'email' => 'current_same@gmail.com',
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('profile.email.initiate'), [
            'new_email' => 'CURRENT_SAME@GMAIL.COM',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'The new email address cannot be the same as your current registered email.',
        ]);
    }

    public function test_api_login_with_old_email_fails_and_new_email_succeeds()
    {
        $user = $this->createCustomer([
            'email' => 'api_old@gmail.com',
        ]);

        $this->actingAs($user);

        // Perform full email change
        $this->postJson(route('profile.email.initiate'), ['new_email' => 'api_new@gmail.com']);
        $oldVerification = EmailVerification::where('email', 'api_old@gmail.com')->first();
        $this->postJson(route('profile.email.verify-old'), ['code' => $oldVerification->code]);
        $newVerification = EmailVerification::where('email', 'api_new@gmail.com')->first();
        $this->postJson(route('profile.email.verify-new'), ['code' => $newVerification->code]);

        Auth::logout();

        // 1. API Login with old email fails
        $apiOldResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'api_old@gmail.com',
            'password' => 'SecretPass123!',
        ]);
        $apiOldResponse->assertStatus(404);

        // 2. API Login with new email succeeds
        $apiNewResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'api_new@gmail.com',
            'password' => 'SecretPass123!',
        ]);
        $apiNewResponse->assertOk();
        $apiNewResponse->assertJsonStructure(['token', 'user' => ['id', 'email']]);
        $this->assertEquals('api_new@gmail.com', $apiNewResponse->json('user.email'));
    }

    public function test_session_invalidation_terminates_stale_sessions_after_email_change()
    {
        $user = $this->createCustomer([
            'email' => 'session_old@gmail.com',
            'sessionVersion' => 1,
        ]);

        $this->actingAs($user);
        session(['login_session_version' => 1]);

        // Complete email change
        $this->postJson(route('profile.email.initiate'), ['new_email' => 'session_new@gmail.com']);
        $oldVerification = EmailVerification::where('email', 'session_old@gmail.com')->first();
        $this->postJson(route('profile.email.verify-old'), ['code' => $oldVerification->code]);
        $newVerification = EmailVerification::where('email', 'session_new@gmail.com')->first();
        $this->postJson(route('profile.email.verify-new'), ['code' => $newVerification->code]);

        $user->refresh();
        $this->assertEquals(2, $user->sessionVersion);

        // Accessing protected route with a stale session version (1) gets rejected by SingleDeviceSession
        $response = $this->withSession(['login_session_version' => 1])
            ->actingAs($user)
            ->get(route('profile'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}

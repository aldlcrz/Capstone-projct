<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SellerStatusAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PlatformUpdatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function user_can_soft_delete_account_and_register_again_with_same_email()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juandelacruz@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
        ]);

        $this->actingAs($user);

        // Soft delete account
        $response = $this->post('/profile/delete-account', [
            'password' => 'Password123!',
            'confirm_deletion' => '1',
        ]);

        $response->assertRedirect('/');
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertGuest();

        // Register new account with the exact same Gmail
        $regResponse = $this->post('/register', [
            'name' => 'Juan New Account',
            'email' => 'juandelacruz@gmail.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
            'terms_consent' => '1',
        ]);

        $regResponse->assertRedirect(route('verify.email'));
        
        // Retrieve generated verification code and verify
        $verification = \App\Models\EmailVerification::where('email', 'juandelacruz@gmail.com')->first();
        $this->assertNotNull($verification);

        $verifyResponse = $this->post('/verify-email', [
            'code' => $verification->code,
        ]);

        $verifyResponse->assertRedirect();
        
        // Assert new active user exists in DB alongside the soft-deleted one
        $this->assertDatabaseHas('users', [
            'name' => 'Juan New Account',
            'email' => 'juandelacruz@gmail.com',
            'deleted_at' => null,
        ]);

        $this->assertEquals(2, User::withTrashed()->where('email', 'juandelacruz@gmail.com')->count());
    }

    /** @test */
    public function admin_can_approve_pending_seller_and_creates_audit_record()
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@lumbarong.ph',
        ]);

        /** @var User $seller */
        $seller = User::factory()->create([
            'role' => 'seller',
            'isVerified' => false,
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        $response = $this->patch("/admin/sellers/{$seller->id}/verify");
        $response->assertRedirect('/admin/sellers');
        $response->assertSessionHas('success');

        $seller->refresh();
        $this->assertTrue((bool)$seller->isVerified);
        $this->assertEquals('active', $seller->status);

        $this->assertDatabaseHas('seller_status_audits', [
            'seller_id' => $seller->id,
            'admin_id' => $admin->id,
            'previous_status' => 'pending',
            'new_status' => 'active',
        ]);
    }

    /** @test */
    public function admin_can_reject_pending_seller_with_reason_and_audit_record()
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin2@lumbarong.ph',
        ]);

        /** @var User $seller */
        $seller = User::factory()->create([
            'role' => 'seller',
            'isVerified' => false,
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        $response = $this->patch("/admin/sellers/{$seller->id}/reject", [
            'reason' => 'Submitted Barangay Certificate is invalid or illegible',
        ]);

        $response->assertRedirect('/admin/sellers');
        $response->assertSessionHas('success');

        $seller->refresh();
        $this->assertFalse((bool)$seller->isVerified);
        $this->assertEquals('rejected', $seller->status);
        $this->assertEquals('Submitted Barangay Certificate is invalid or illegible', $seller->rejection_reason);

        $this->assertDatabaseHas('seller_status_audits', [
            'seller_id' => $seller->id,
            'admin_id' => $admin->id,
            'previous_status' => 'pending',
            'new_status' => 'rejected',
            'reason' => 'Submitted Barangay Certificate is invalid or illegible',
        ]);
    }

    /** @test */
    public function admin_can_reopen_rejected_seller_application()
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin3@lumbarong.ph',
        ]);

        /** @var User $seller */
        $seller = User::factory()->create([
            'role' => 'seller',
            'isVerified' => false,
            'status' => 'rejected',
            'rejectionReason' => 'Incomplete docs',
        ]);

        $this->actingAs($admin);

        $response = $this->patch("/admin/sellers/{$seller->id}/reopen");
        $response->assertRedirect('/admin/sellers');
        $response->assertSessionHas('success');

        $seller->refresh();
        $this->assertFalse((bool)$seller->isVerified);
        $this->assertEquals('pending', $seller->status);
        $this->assertNull($seller->rejection_reason);

        $this->assertDatabaseHas('seller_status_audits', [
            'seller_id' => $seller->id,
            'admin_id' => $admin->id,
            'previous_status' => 'rejected',
            'new_status' => 'pending',
        ]);
    }

    /** @test */
    public function admin_can_suspend_active_seller_and_unsuspend_afterwards()
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin4@lumbarong.ph',
        ]);

        /** @var User $seller */
        $seller = User::factory()->create([
            'role' => 'seller',
            'isVerified' => true,
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        // Suspend
        $response = $this->patch("/admin/sellers/{$seller->id}/suspend", [
            'reason' => 'Multiple counterfeit product listings detected',
        ]);

        $response->assertRedirect('/admin/sellers');
        $response->assertSessionHas('success');

        $seller->refresh();
        $this->assertEquals('suspended', $seller->status);
        $this->assertEquals('Multiple counterfeit product listings detected', $seller->suspension_reason);

        $this->assertDatabaseHas('seller_status_audits', [
            'seller_id' => $seller->id,
            'admin_id' => $admin->id,
            'previous_status' => 'active',
            'new_status' => 'suspended',
            'reason' => 'Multiple counterfeit product listings detected',
        ]);

        // Unsuspend
        $unsuspendResponse = $this->patch("/admin/sellers/{$seller->id}/unsuspend");
        $unsuspendResponse->assertRedirect('/admin/sellers');
        $unsuspendResponse->assertSessionHas('success');

        $seller->refresh();
        $this->assertEquals('active', $seller->status);
        $this->assertNull($seller->suspension_reason);

        $this->assertDatabaseHas('seller_status_audits', [
            'seller_id' => $seller->id,
            'admin_id' => $admin->id,
            'previous_status' => 'suspended',
            'new_status' => 'active',
        ]);
    }

    /** @test */
    public function cannot_perform_invalid_state_transitions()
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin5@lumbarong.ph',
        ]);

        /** @var User $rejectedSeller */
        $rejectedSeller = User::factory()->create([
            'role' => 'seller',
            'isVerified' => false,
            'status' => 'rejected',
        ]);

        $this->actingAs($admin);

        // Cannot suspend a rejected seller
        $response = $this->patch("/admin/sellers/{$rejectedSeller->id}/suspend", [
            'reason' => 'Test reason',
        ]);
        $response->assertSessionHas('error');

        $rejectedSeller->refresh();
        $this->assertEquals('rejected', $rejectedSeller->status);

        /** @var User $activeSeller */
        $activeSeller = User::factory()->create([
            'role' => 'seller',
            'isVerified' => true,
            'status' => 'active',
        ]);

        // Cannot reject an active seller (must suspend instead)
        $rejectResponse = $this->patch("/admin/sellers/{$activeSeller->id}/reject", [
            'reason' => 'Test reason',
        ]);
        $rejectResponse->assertSessionHas('error');

        $activeSeller->refresh();
        $this->assertEquals('active', $activeSeller->status);
    }

    /** @test */
    public function suspended_seller_cannot_modify_payment_methods()
    {
        /** @var User $suspendedSeller */
        $suspendedSeller = User::factory()->create([
            'name' => 'Artisan Test',
            'role' => 'seller',
            'isVerified' => true,
            'status' => 'suspended',
            'gcashNumber' => '09123456789',
        ]);

        $this->actingAs($suspendedSeller);

        $response = $this->post('/seller/profile', [
            'name' => 'Artisan Test',
            'gcashNumber' => '09999999999',
            'gcashName' => 'New Suspended Account Name',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $suspendedSeller->refresh();
        $this->assertEquals('09123456789', $suspendedSeller->gcashNumber);
    }

    /** @test */
    public function seller_can_be_frozen_due_to_overdue_commission_without_setting_violation_reason()
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin@lumbarong.ph',
        ]);

        /** @var User $seller */
        $seller = User::factory()->create([
            'role' => 'seller',
            'isVerified' => true,
            'status' => 'active',
            'violationReason' => null,
        ]);

        $this->actingAs($superAdmin);

        $response = $this->patch("/superadmin/shops/{$seller->id}/freeze", [
            'period' => '2026-08',
            'reason' => 'Unpaid commission for 2026-08',
        ]);

        $response->assertRedirect();
        $seller->refresh();

        $this->assertEquals('frozen', $seller->status);
        $this->assertTrue((bool)$seller->isVerified);
        // Ensure violationReason was NOT populated or contaminated
        $this->assertNull($seller->violationReason);
    }

    /** @test */
    public function frozen_seller_automatically_unfreezes_when_all_overdue_commissions_are_paid()
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin2@lumbarong.ph',
        ]);

        /** @var User $seller */
        $seller = User::factory()->create([
            'role' => 'seller',
            'isVerified' => true,
            'status' => 'frozen',
        ]);

        // Create 1 unpaid commission record
        $record = \App\Models\CommissionRecord::create([
            'sellerId' => $seller->id,
            'period' => '2026-07',
            'totalSales' => 10000.00,
            'commissionRate' => 5.00,
            'commissionAmount' => 500.00,
            'status' => 'unpaid',
            'dueDate' => now()->subDays(5),
        ]);

        $this->actingAs($superAdmin);

        // Record payment for the only overdue record
        $response = $this->patch("/superadmin/commissions/{$seller->id}/mark-paid", [
            'period' => '2026-07',
            'notes' => 'Settled in full via GCash',
        ]);

        $response->assertRedirect();
        $seller->refresh();

        // Seller must be automatically unfrozen to active
        $this->assertEquals('active', $seller->status);
    }

    /** @test */
    public function frozen_seller_remains_frozen_if_another_overdue_commission_period_exists()
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin3@lumbarong.ph',
        ]);

        /** @var User $seller */
        $seller = User::factory()->create([
            'role' => 'seller',
            'isVerified' => true,
            'status' => 'frozen',
        ]);

        // Create 2 unpaid commission records for different periods
        \App\Models\CommissionRecord::create([
            'sellerId' => $seller->id,
            'period' => '2026-06',
            'totalSales' => 10000.00,
            'commissionRate' => 5.00,
            'commissionAmount' => 500.00,
            'status' => 'unpaid',
            'dueDate' => now()->subDays(35),
        ]);

        \App\Models\CommissionRecord::create([
            'sellerId' => $seller->id,
            'period' => '2026-07',
            'totalSales' => 8000.00,
            'commissionRate' => 5.00,
            'commissionAmount' => 400.00,
            'status' => 'unpaid',
            'dueDate' => now()->subDays(5),
        ]);

        $this->actingAs($superAdmin);

        // Record payment for only 1 of the 2 periods
        $response = $this->patch("/superadmin/commissions/{$seller->id}/mark-paid", [
            'period' => '2026-06',
            'notes' => 'Settled partial period',
        ]);

        $response->assertRedirect();
        $seller->refresh();

        // Seller must REMAIN frozen because 2026-07 is still unpaid
        $this->assertEquals('frozen', $seller->status);
    }

    /** @test */
    public function suspended_and_frozen_sellers_have_distinct_login_error_messages()
    {
        /** @var User $suspendedSeller */
        $suspendedSeller = User::factory()->create([
            'email' => 'suspended_artisan@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'isVerified' => true,
            'status' => 'suspended',
            'violationReason' => 'Listed prohibited non-barong merchandise',
        ]);

        $responseSuspended = $this->post('/login', [
            'email' => 'suspended_artisan@test.com',
            'password' => 'Password123!',
        ]);

        $responseSuspended->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('suspended for 1 month', session('errors')->first('email'));
        $this->assertStringContainsString('Listed prohibited non-barong merchandise', session('errors')->first('email'));
        $this->assertStringContainsString('permanent ban', session('errors')->first('email'));

        /** @var User $frozenSeller */
        $frozenSeller = User::factory()->create([
            'email' => 'frozen_artisan@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'isVerified' => true,
            'status' => 'frozen',
        ]);

        \App\Models\CommissionRecord::create([
            'sellerId' => $frozenSeller->id,
            'period' => '2026-08',
            'totalSales' => 20000.00,
            'commissionRate' => 5.00,
            'commissionAmount' => 1000.00,
            'status' => 'unpaid',
            'dueDate' => now()->subDays(3),
        ]);

        $responseFrozen = $this->post('/login', [
            'email' => 'frozen_artisan@test.com',
            'password' => 'Password123!',
        ]);

        $responseFrozen->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('temporarily frozen due to an unpaid monthly commission', session('errors')->first('email'));
        $this->assertStringContainsString('1,000.00', session('errors')->first('email'));
        $this->assertStringContainsString('2026-08', session('errors')->first('email'));
    }

    public function test_admin_can_manually_freeze_and_unfreeze_seller(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'email' => 'admin_freeze_test@test.com',
            'role' => 'admin',
            'isVerified' => true,
            'status' => 'active',
        ]);

        /** @var User $seller */
        $seller = User::factory()->create([
            'email' => 'active_to_freeze@test.com',
            'role' => 'seller',
            'isVerified' => true,
            'status' => 'active',
        ]);

        // Freeze seller manually
        $freezeResponse = $this->actingAs($admin)->patch("/admin/sellers/{$seller->id}/freeze", [
            'reason' => 'Administrative shop hold / Pending audit',
        ]);
        $freezeResponse->assertRedirect(route('admin.sellers'));

        $seller->refresh();
        $this->assertEquals('frozen', $seller->status);

        // Unfreeze seller manually
        $unfreezeResponse = $this->actingAs($admin)->patch("/admin/sellers/{$seller->id}/unfreeze");
        $unfreezeResponse->assertRedirect(route('admin.sellers'));

        $seller->refresh();
        $this->assertEquals('active', $seller->status);
    }

    public function test_revoked_or_pending_seller_redirects_to_verification_pending_portal_and_can_upload_documents(): void
    {
        /** @var User $pendingSeller */
        $pendingSeller = User::factory()->create([
            'email' => 'revoked_artisan@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'isVerified' => false,
            'status' => 'pending',
        ]);

        // 1. Logging in redirects directly to verification-pending portal
        $loginResponse = $this->post('/login', [
            'email' => 'revoked_artisan@test.com',
            'password' => 'Password123!',
        ]);
        $loginResponse->assertRedirect(route('seller.verification-pending'));

        // 2. Pending seller can view the verification-pending page
        $portalResponse = $this->actingAs($pendingSeller)->get(route('seller.verification-pending'));
        $portalResponse->assertStatus(200);
        $portalResponse->assertSee('Pending Verification');
        $portalResponse->assertSee('Barangay Residency Certificate');

        // 3. Attempting to access protected seller routes redirects to verification-pending
        $dashboardResponse = $this->actingAs($pendingSeller)->get('/seller/dashboard');
        $dashboardResponse->assertRedirect(route('seller.verification-pending'));

        // 4. Seller can upload updated verification documents
        \Illuminate\Support\Facades\Storage::fake('public');
        $file = \Illuminate\Http\UploadedFile::fake()->create('barangay_cert.pdf', 150, 'application/pdf');

        $uploadResponse = $this->actingAs($pendingSeller)->post(route('seller.verification-pending.upload'), [
            'residencyCertificate' => $file,
        ]);
        $uploadResponse->assertSessionHas('success');

        $pendingSeller->refresh();
        $this->assertNotNull($pendingSeller->residencyCertificate);
        $this->assertEquals('pending', $pendingSeller->status);
    }
}

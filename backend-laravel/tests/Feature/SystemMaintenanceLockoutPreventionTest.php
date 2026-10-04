<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckMaintenance;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemMaintenanceLockoutPreventionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularAdmin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->superAdmin = User::factory()->create([
            'role'     => 'superadmin',
            'status'   => 'active',
            'email'    => 'superadmin@lumbarong.test',
            'password' => Hash::make('Password123!'),
        ]);

        $this->regularAdmin = User::factory()->create([
            'role'     => 'admin',
            'status'   => 'active',
            'email'    => 'admin@lumbarong.test',
            'password' => Hash::make('Password123!'),
        ]);

        $this->customer = User::factory()->create([
            'role'     => 'customer',
            'status'   => 'active',
            'email'    => 'customer@lumbarong.test',
            'password' => Hash::make('Password123!'),
        ]);

        CheckMaintenance::clearMaintenanceCache();
    }

    /**
     * Test: When maintenance is OFF, regular user and guests can browse normally.
     */
    public function test_regular_user_can_browse_when_maintenance_is_off(): void
    {
        SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => '0']);
        CheckMaintenance::clearMaintenanceCache();

        $response = $this->actingAs($this->customer)->get('/');
        $response->assertOk();
    }

    /**
     * Test 1: Regular users and guests are blocked with HTTP 503 during maintenance.
     */
    public function test_regular_user_and_guest_are_blocked_during_maintenance(): void
    {
        SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => '1']);
        SystemSetting::updateOrCreate(['key' => 'maintenance_message'], ['value' => 'Undergoing scheduled maintenance.']);
        CheckMaintenance::clearMaintenanceCache();

        // 1. Guest request blocked with 503
        $guestResponse = $this->get('/');
        $guestResponse->assertStatus(503);

        // 2. Customer request blocked with 503
        $customerResponse = $this->actingAs($this->customer)->get('/');
        $customerResponse->assertStatus(503);

        // 3. API request receives JSON 503
        $apiResponse = $this->getJson('/api/v1/products');
        $apiResponse->assertStatus(503)
            ->assertJson([
                'maintenance' => true,
                'message'     => 'Undergoing scheduled maintenance.',
            ]);
    }

    /**
     * Test 2: Super Admin bypasses maintenance restrictions and has full access.
     */
    public function test_super_admin_bypasses_maintenance(): void
    {
        SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => '1']);
        CheckMaintenance::clearMaintenanceCache();

        $response = $this->actingAs($this->superAdmin)->get('/superadmin/dashboard');
        $response->assertOk();
    }

    /**
     * Test 3: Super Admin logout is REJECTED during active maintenance (lockout prevention).
     */
    public function test_super_admin_logout_is_blocked_during_maintenance(): void
    {
        SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => '1']);
        CheckMaintenance::clearMaintenanceCache();

        // API request logout rejected with 403
        $apiLogoutResponse = $this->actingAs($this->superAdmin)
            ->postJson('/superadmin/logout');

        $apiLogoutResponse->assertStatus(403)
            ->assertJson([
                'error' => 'SUPERADMIN_LOGOUT_BLOCKED_MAINTENANCE'
            ]);

        // Web request logout redirected with error message
        $webLogoutResponse = $this->actingAs($this->superAdmin)
            ->from('/superadmin/dashboard')
            ->post('/superadmin/logout');

        $webLogoutResponse->assertRedirect('/superadmin/dashboard')
            ->assertSessionHas('error');

        // Verify Super Admin is STILL authenticated
        $this->assertAuthenticatedAs($this->superAdmin);
    }

    /**
     * Test 4: Super Admin can log in during maintenance mode.
     */
    public function test_super_admin_can_log_in_during_maintenance(): void
    {
        SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => '1']);
        CheckMaintenance::clearMaintenanceCache();

        $response = $this->post('/superadmin/login', [
            'email'    => 'superadmin@lumbarong.test',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('superadmin.dashboard'));
        $this->assertAuthenticatedAs($this->superAdmin);
    }

    /**
     * Test 5: Non-superadmin cannot log in during maintenance.
     */
    public function test_customer_cannot_log_in_during_maintenance(): void
    {
        SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => '1']);
        CheckMaintenance::clearMaintenanceCache();

        $response = $this->post('/login', [
            'email'    => 'customer@lumbarong.test',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(503);
    }

    /**
     * Test 6: Typed confirmation prevents accidental maintenance mode activation.
     */
    public function test_enabling_maintenance_requires_typed_confirmation(): void
    {
        SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => '0']);
        CheckMaintenance::clearMaintenanceCache();

        // Attempt without typing MAINTENANCE
        $failedResponse = $this->actingAs($this->superAdmin)
            ->from('/superadmin/maintenance')
            ->post('/superadmin/maintenance/toggle', [
                'enable'       => '1',
                'confirmation' => 'WRONG_TEXT',
                'message'      => 'Test maintenance message',
            ]);

        $failedResponse->assertRedirect('/superadmin/maintenance')
            ->assertSessionHas('error');
        $this->assertFalse(CheckMaintenance::isInMaintenance());

        // Attempt with correct typed confirmation
        $successResponse = $this->actingAs($this->superAdmin)
            ->from('/superadmin/maintenance')
            ->post('/superadmin/maintenance/toggle', [
                'enable'       => '1',
                'confirmation' => 'MAINTENANCE',
                'message'      => 'System maintenance underway.',
            ]);

        $successResponse->assertRedirect('/superadmin/maintenance')
            ->assertSessionHas('success');
        $this->assertTrue(CheckMaintenance::isInMaintenance());
    }

    /**
     * Test 7: Super Admin session keep-alive heartbeat returns 200 and regenerates session.
     */
    public function test_super_admin_session_keep_alive(): void
    {
        SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => '1']);
        CheckMaintenance::clearMaintenanceCache();

        $response = $this->actingAs($this->superAdmin)
            ->getJson('/superadmin/session/keep-alive');

        $response->assertOk()
            ->assertJson([
                'status'      => 'ok',
                'maintenance' => true,
                'user'        => $this->superAdmin->name,
            ]);
    }
}

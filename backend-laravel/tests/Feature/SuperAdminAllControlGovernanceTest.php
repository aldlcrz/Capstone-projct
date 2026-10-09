<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\Banner;
use App\Models\CommissionRecord;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAllControlGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $seller;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'name'     => 'Supreme Super Admin',
            'role'     => 'superadmin',
            'email'    => 'superadmin@lumbarong.gov',
            'status'   => 'active',
            'isVerified' => true,
        ]);

        $this->admin = User::factory()->create([
            'name'     => 'Operations Admin',
            'role'     => 'admin',
            'email'    => 'admin@lumbarong.gov',
            'status'   => 'active',
            'isVerified' => true,
        ]);

        $this->seller = User::factory()->create([
            'name'     => 'Test Artisan',
            'role'     => 'seller',
            'email'    => 'seller@artisan.test',
            'shopName' => 'Artisan Handcrafted',
            'status'   => 'active',
            'isVerified' => true,
        ]);

        $this->customer = User::factory()->create([
            'name'     => 'Buyer User',
            'role'     => 'customer',
            'email'    => 'buyer@lumbarong.test',
            'status'   => 'active',
        ]);
    }

    public function test_superadmin_has_full_access_to_all_governance_and_operational_dashboards(): void
    {
        $routes = [
            '/superadmin/dashboard',
            '/superadmin/commissions',
            '/superadmin/payment-settings',
            '/superadmin/orders',
            '/admin/returns',
            '/superadmin/categories',
            '/superadmin/products',
            '/superadmin/banners',
            '/superadmin/sellers',
            '/superadmin/customers',
            '/admin/shipping',
            '/admin/reports',
            '/superadmin/archives',
            '/superadmin/maintenance',
            '/superadmin/audit-logs',
            '/superadmin/error-logs',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->superAdmin)->get($route);
            $this->assertTrue(
                in_array($response->status(), [200, 302]),
                "SuperAdmin failed to access route: {$route} (Status: {$response->status()})"
            );
        }
    }

    public function test_regular_admin_and_customer_are_blocked_from_superadmin_exclusive_routes(): void
    {
        $superAdminOnlyRoutes = [
            '/superadmin/dashboard',
            '/superadmin/commissions',
            '/superadmin/payment-settings',
            '/superadmin/system-health',
            '/superadmin/maintenance',
            '/superadmin/error-logs',
        ];

        foreach ($superAdminOnlyRoutes as $route) {
            // Admin should be redirected or blocked
            $adminResponse = $this->actingAs($this->admin)->get($route);
            $adminResponse->assertRedirect('/superadmin/login');

            // Customer should be redirected or blocked
            $customerResponse = $this->actingAs($this->customer)->get($route);
            $customerResponse->assertRedirect('/superadmin/login');
        }
    }

    public function test_superadmin_can_update_commission_rate_globally(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/superadmin/commission-rate', [
            'rate' => 8.5,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(8.5, (float) SystemSetting::where('key', 'commission_rate')->value('value'));
    }

    public function test_superadmin_can_update_payment_settings_and_qr(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/superadmin/payment-settings', [
            'gcash_number' => '09171234567',
            'maya_number'  => '09187654321',
        ]);

        $response->assertRedirect(route('superadmin.payment-settings'));
        $this->assertEquals('09171234567', SystemSetting::where('key', 'superadmin_gcash_number')->value('value'));
        $this->assertEquals('09187654321', SystemSetting::where('key', 'superadmin_maya_number')->value('value'));
    }

    public function test_superadmin_can_change_user_roles(): void
    {
        $targetUser = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($this->superAdmin)->patch("/superadmin/users/{$targetUser->id}/role", [
            'role' => 'admin',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('admin', $targetUser->fresh()->role);
    }

    public function test_superadmin_can_freeze_and_unfreeze_seller_for_commission(): void
    {
        // Freeze shop
        $freezeResponse = $this->actingAs($this->superAdmin)->patch("/superadmin/shops/{$this->seller->id}/freeze", [
            'period' => '2026-09',
            'reason' => 'Overdue commission payment',
        ]);

        $freezeResponse->assertSessionHas('success');
        $this->assertEquals('frozen', $this->seller->fresh()->status);

        // Unfreeze shop
        $unfreezeResponse = $this->actingAs($this->superAdmin)->patch("/superadmin/shops/{$this->seller->id}/unfreeze");
        $unfreezeResponse->assertSessionHas('success');
        $this->assertEquals('active', $this->seller->fresh()->status);
    }

    public function test_superadmin_can_mark_commission_as_paid(): void
    {
        CommissionRecord::create([
            'sellerId'         => $this->seller->id,
            'period'           => '2026-09',
            'totalSales'       => 50000,
            'commissionRate'   => 5.0,
            'commissionAmount' => 2500,
            'status'           => 'unpaid',
        ]);

        $response = $this->actingAs($this->superAdmin)->patch("/superadmin/commissions/{$this->seller->id}/mark-paid", [
            'period' => '2026-09',
            'notes'  => 'GCash remittance verified',
        ]);

        $response->assertSessionHas('success');
        $record = CommissionRecord::where('sellerId', $this->seller->id)->where('period', '2026-09')->first();
        $this->assertEquals('paid', $record->status);
        $this->assertNotNull($record->paidAt);
    }

    public function test_superadmin_can_ban_and_unban_customers(): void
    {
        // Ban customer
        $banResponse = $this->actingAs($this->superAdmin)->patch("/superadmin/customers/{$this->customer->id}/ban", [
            'reason' => 'Fraudulent dispute activity',
        ]);

        $banResponse->assertRedirect(route('superadmin.customers'));
        $this->assertEquals('blocked', $this->customer->fresh()->status);

        // Unban customer
        $unbanResponse = $this->actingAs($this->superAdmin)->patch("/superadmin/customers/{$this->customer->id}/unban");
        $unbanResponse->assertRedirect(route('superadmin.customers'));
        $this->assertEquals('active', $this->customer->fresh()->status);
    }

    public function test_superadmin_can_clear_cache_and_error_logs(): void
    {
        // Clear Cache
        $cacheResponse = $this->actingAs($this->superAdmin)->post('/superadmin/system-health/clear-cache');
        $cacheResponse->assertSessionHas('success');

        // Clear Error Logs
        $logsResponse = $this->actingAs($this->superAdmin)->post('/superadmin/error-logs/clear');
        $logsResponse->assertSessionHas('success');
    }
}

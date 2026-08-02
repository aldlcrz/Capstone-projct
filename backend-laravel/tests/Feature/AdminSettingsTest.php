<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $this->customer = User::create([
            'name' => 'John Customer',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'role' => 'customer',
        ]);
    }

    public function test_non_admin_cannot_access_settings(): void
    {
        $response = $this->actingAs($this->customer)->get('/admin/settings');
        $response->assertRedirect('/');

        $response = $this->get('/admin/settings');
        $response->assertRedirect('/');
    }

    public function test_admin_can_access_settings(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/settings');
        $response->assertStatus(200);
        $response->assertSee('System Settings');
    }

    public function test_admin_can_update_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/settings', [
                'site_name' => 'Custom LumBarong',
                'commission_rate' => '12.5',
            ]);

        $response->assertRedirect('/admin/settings');
        $this->assertEquals('Custom LumBarong', SystemSetting::where('key', 'site_name')->value('value'));
        $this->assertEquals('12.5', SystemSetting::where('key', 'commission_rate')->value('value'));
    }

    public function test_admin_can_toggle_maintenance_mode(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/maintenance');
        $response->assertStatus(200);
        $response->assertSee('Maintenance Mode');

        // Toggle maintenance on
        $response = $this->actingAs($this->admin)
            ->post('/admin/maintenance/toggle', [
                'enable' => '1',
                'message' => 'Down for updates',
            ]);

        $response->assertRedirect('/admin/maintenance');
        $this->assertEquals('Down for updates', SystemSetting::where('key', 'maintenance_message')->value('value'));
    }

    public function test_maintenance_mode_enforcement(): void
    {
        // 1. By default, maintenance mode is off, so homepage loads fine
        $response = $this->get('/');
        $response->assertStatus(200);

        // 2. Turn maintenance mode ON in database
        SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => '1']);
        SystemSetting::updateOrCreate(['key' => 'maintenance_message'], ['value' => 'Undergoing artisan updates']);

        // 3. Guests should be blocked from homepage and see the premium maintenance page
        $response = $this->get('/');
        $response->assertStatus(503);
        $response->assertSee('Undergoing artisan updates');

        // 4. Login route must be bypassed so admins/guests can access it to authenticate
        $response = $this->get('/login');
        $response->assertStatus(200);

        // 5. API requests must get a 503 JSON response
        $response = $this->getJson('/api/v1/categories');
        $response->assertStatus(503);
        $response->assertJson([
            'message' => 'Undergoing artisan updates',
            'maintenance' => true,
        ]);

        // 6. Customers (non-admin authenticated) should also be blocked from homepage
        $response = $this->actingAs($this->customer)->get('/');
        $response->assertStatus(503);

        // 7. Admins should bypass maintenance
        $response = $this->actingAs($this->admin)->get('/');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_platform_info(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/platform');
        $response->assertStatus(200);
        $response->assertSee('Platform Info');
        $response->assertSee('PHP Runtime');
    }

    public function test_admin_can_access_audit_logs(): void
    {
        // Create order, product, user to generate logs
        $seller = User::create([
            'name' => 'Artisan Seller',
            'email' => 'seller@example.com',
            'password' => bcrypt('password123'),
            'role' => 'seller',
        ]);

        $order = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $seller->id,
            'totalAmount' => 1500,
            'status' => 'Pending',
            'shippingAddress' => [
                'name' => 'John Customer',
                'phone' => '09171234567',
                'street' => '123 Heritage St',
                'city' => 'Manila',
                'province' => 'Metro Manila',
                'postalCode' => '1000',
            ],
        ]);

        $product = Product::create([
            'sellerId' => $seller->id,
            'name' => 'Elegant Piña Barong',
            'description' => 'Beautiful barong',
            'price' => 3500,
            'stock' => 5,
            'status' => 'approved',
        ]);

        // Default all tab
        $response = $this->actingAs($this->admin)->get('/admin/audit-logs');
        $response->assertStatus(200);
        $response->assertSee('Audit Logs');
        $response->assertSee('Placed Order');
        $response->assertSee('Listed Product');
        $response->assertSee('Registered Account');

        // Orders tab
        $response = $this->actingAs($this->admin)->get('/admin/audit-logs?tab=orders');
        $response->assertStatus(200);
        $response->assertSee('Placed Order');
        $response->assertDontSee('Listed Product');

        // Products tab
        $response = $this->actingAs($this->admin)->get('/admin/audit-logs?tab=products');
        $response->assertStatus(200);
        $response->assertSee('Listed Product');
        $response->assertDontSee('Placed Order');

        // Users tab
        $response = $this->actingAs($this->admin)->get('/admin/audit-logs?tab=users');
        $response->assertStatus(200);
        $response->assertSee('Registered Account');
        $response->assertDontSee('Placed Order');

        // Search log
        $response = $this->actingAs($this->admin)->get('/admin/audit-logs?search=Piña');
        $response->assertStatus(200);
        $response->assertSee('Elegant Piña Barong');
        $response->assertDontSee('Placed Order');
    }
}

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

    public function test_superadmin_can_moderate_and_approve_reject_products(): void
    {
        $category = Category::create([
            'id'          => (string) \Illuminate\Support\Str::uuid(),
            'name'        => 'Piña Collection',
            'tags'        => ['Men'],
            'description' => 'Fine piña barongs',
            'image'       => '/uploads/categories/barong.png',
        ]);

        $product = Product::create([
            'id'          => (string) \Illuminate\Support\Str::uuid(),
            'sellerId'    => $this->seller->id,
            'categoryId'  => $category->id,
            'status'      => 'pending',
            'name'        => 'Handwoven Piña Silk Barong',
            'price'       => 4500,
            'stock'       => 10,
            'image'       => ['barong.jpg'],
        ]);

        // SuperAdmin approves product
        $approveResponse = $this->actingAs($this->superAdmin)->post("/superadmin/products/{$product->id}/approve");
        $approveResponse->assertSessionHas('success');
        $this->assertEquals('approved', $product->fresh()->status);

        // SuperAdmin rejects product with reason
        $rejectResponse = $this->actingAs($this->superAdmin)->post("/superadmin/products/{$product->id}/reject", [
            'reason' => 'Sizing measurements require clarification',
        ]);
        $rejectResponse->assertSessionHas('success');
        $this->assertEquals('rejected', $product->fresh()->status);
        $this->assertEquals('Sizing measurements require clarification', $product->fresh()->rejectionReason);
    }

    public function test_superadmin_can_verify_and_reject_payments(): void
    {
        $order = Order::create([
            'id'               => (string) \Illuminate\Support\Str::uuid(),
            'customerId'       => $this->customer->id,
            'sellerId'         => $this->seller->id,
            'customerName'     => 'Buyer User',
            'shippingAddress'  => '123 Heritage St, Lumban, Laguna',
            'paymentMethod'    => 'GCash',
            'paymentStatus'    => 'Pending Verification',
            'paymentReference' => 'GCASH-REF-998877',
            'totalAmount'      => 3200,
            'status'           => 'Pending',
        ]);

        // SuperAdmin verifies payment
        $verifyResponse = $this->actingAs($this->superAdmin)->post("/superadmin/orders/{$order->id}/verify-payment");
        $verifyResponse->assertSessionHas('success');
        $this->assertEquals('Paid', $order->fresh()->paymentStatus);

        $order2 = Order::create([
            'id'               => (string) \Illuminate\Support\Str::uuid(),
            'customerId'       => $this->customer->id,
            'sellerId'         => $this->seller->id,
            'customerName'     => 'Buyer User 2',
            'shippingAddress'  => '123 Heritage St, Lumban, Laguna',
            'paymentMethod'    => 'GCash',
            'paymentStatus'    => 'Pending Verification',
            'paymentReference' => 'GCASH-REF-INVALID',
            'totalAmount'      => 1500,
            'status'           => 'Pending',
        ]);

        // SuperAdmin rejects payment with reason
        $rejectResponse = $this->actingAs($this->superAdmin)->post("/superadmin/orders/{$order2->id}/reject-payment", [
            'reason' => 'Invalid reference number provided',
        ]);
        $rejectResponse->assertRedirect();
        $this->assertEquals('Payment Rejected', $order2->fresh()->paymentStatus);
        $this->assertEquals('Cancelled', $order2->fresh()->status);
    }

    public function test_superadmin_can_manage_return_refund_and_disputes(): void
    {
        $order = Order::create([
            'id'              => (string) \Illuminate\Support\Str::uuid(),
            'customerId'      => $this->customer->id,
            'sellerId'        => $this->seller->id,
            'customerName'    => 'Buyer User',
            'shippingAddress' => '123 Heritage St, Lumban, Laguna',
            'paymentMethod'   => 'GCash',
            'paymentStatus'   => 'Paid',
            'totalAmount'     => 2800,
            'status'          => 'Delivered',
        ]);

        $returnRequest = \App\Models\ReturnRequest::create([
            'id'               => (string) \Illuminate\Support\Str::uuid(),
            'orderId'          => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reason'           => 'Incorrect size delivered',
            'return_status'    => 'disputed',
            'requested_amount' => 2800,
        ]);

        // SuperAdmin views returns index
        $indexResponse = $this->actingAs($this->superAdmin)->get('/superadmin/returns');
        $indexResponse->assertStatus(200);

        // SuperAdmin views single return request JSON
        $showResponse = $this->actingAs($this->superAdmin)->get("/superadmin/returns/{$returnRequest->id}");
        $showResponse->assertStatus(200);

        // SuperAdmin resolves dispute
        $resolveResponse = $this->actingAs($this->superAdmin)->post("/superadmin/returns/{$returnRequest->id}/resolve-dispute", [
            'decision' => 'approve_return',
            'notes'    => 'Dispute resolved in favor of customer due to sizing discrepancy.',
        ]);
        $resolveResponse->assertRedirect();
        $this->assertEquals('admin_review', $returnRequest->fresh()->return_status);
        $this->assertEquals('Approved', $returnRequest->fresh()->status);

        // SuperAdmin records platform refund transfer
        $transferResponse = $this->actingAs($this->superAdmin)->post("/superadmin/returns/{$returnRequest->id}/record-transfer", [
            'refund_amount'       => 2800,
            'transfer_reference'  => 'GCASH-REFUND-445566',
            'destination_account' => '09171234567',
            'destination_name'    => 'Buyer User',
            'notes'               => 'Refund sent via GCash',
        ]);
        $transferResponse->assertRedirect();
        $this->assertEquals('resolved', $returnRequest->fresh()->return_status);
        $this->assertEquals('transferred', $returnRequest->fresh()->refund_status);
    }

    public function test_superadmin_can_manage_shipping_matrix_and_providers(): void
    {
        $provider = \App\Models\ShippingProvider::create([
            'id'                 => (string) \Illuminate\Support\Str::uuid(),
            'name'               => 'J&T Express',
            'code'               => 'jnt',
            'volumetric_divisor' => 3500,
            'is_active'          => true,
        ]);

        $originZone = \App\Models\ShippingZone::create([
            'id'   => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Laguna Origin Zone',
            'code' => 'ZONE_LAG',
        ]);

        $destZone = \App\Models\ShippingZone::create([
            'id'   => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'NCR Destination Zone',
            'code' => 'ZONE_NCR',
        ]);

        // SuperAdmin views shipping index
        $indexResponse = $this->actingAs($this->superAdmin)->get('/superadmin/shipping');
        $indexResponse->assertStatus(200);

        // SuperAdmin creates a shipping rate
        $rateResponse = $this->actingAs($this->superAdmin)->post('/superadmin/shipping/rates', [
            'provider_id'            => $provider->id,
            'origin_zone_id'         => $originZone->id,
            'destination_zone_id'    => $destZone->id,
            'min_weight'             => 0.0,
            'max_weight'             => 5.0,
            'base_rate'              => 120,
            'additional_weight_rate' => 30,
            'estimated_days_min'     => 1,
            'estimated_days_max'     => 2,
            'is_active'              => true,
        ]);
        $rateResponse->assertSessionHas('success');

        // SuperAdmin creates a shipping area
        $areaResponse = $this->actingAs($this->superAdmin)->post('/superadmin/shipping/areas', [
            'zone_id'  => $destZone->id,
            'province' => 'Laguna',
            'city'     => 'Lumban',
        ]);
        $areaResponse->assertSessionHas('success');
    }

    public function test_superadmin_can_resolve_and_delete_incident_reports(): void
    {
        $report = \App\Models\Report::create([
            'id'             => (string) \Illuminate\Support\Str::uuid(),
            'reporterId'     => $this->customer->id,
            'reportedId'     => $this->seller->id,
            'type'           => 'CustomerReportingSeller',
            'reason'         => 'Fabric Discrepancy',
            'description'    => 'Listing stated 100% cocoon silk but delivered poly-blend',
            'status'         => 'Pending',
        ]);

        // SuperAdmin views reports index
        $indexResponse = $this->actingAs($this->superAdmin)->get('/superadmin/reports');
        $indexResponse->assertStatus(200);

        // SuperAdmin resolves report
        $resolveResponse = $this->actingAs($this->superAdmin)->post("/superadmin/reports/{$report->id}/resolve", [
            'resolution_notes' => 'Seller warned and fabric description updated.',
        ]);
        $resolveResponse->assertSessionHas('success');
        $this->assertEquals('Resolved', $report->fresh()->status);

        // SuperAdmin deletes report
        $deleteResponse = $this->actingAs($this->superAdmin)->delete("/superadmin/reports/{$report->id}");
        $deleteResponse->assertSessionHas('success');
        $this->assertNull(\App\Models\Report::find($report->id));
    }

    public function test_superadmin_can_export_global_csv_report(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/superadmin/export-global-report');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_superadmin_can_update_general_system_settings(): void
    {
        $indexResponse = $this->actingAs($this->superAdmin)->get('/superadmin/settings');
        $indexResponse->assertStatus(200);

        $postResponse = $this->actingAs($this->superAdmin)->post('/superadmin/settings', [
            'site_name'     => 'LumBarong Heritage Marketplace',
            'support_email' => 'support@lumbarong.gov',
        ]);
        $postResponse->assertSessionHas('success');
        $this->assertEquals('LumBarong Heritage Marketplace', SystemSetting::where('key', 'site_name')->value('value'));
    }

    public function test_superadmin_can_manage_categories_and_banners(): void
    {
        // Category creation
        $catResponse = $this->actingAs($this->superAdmin)->post('/superadmin/categories', [
            'name'         => 'Modern Filipiniana',
            'description'  => 'Contemporary Philippine formal attire',
            'target_group' => ['Women'],
        ]);
        $catResponse->assertSessionHas('success');
        $this->assertDatabaseHas('categories', ['name' => 'Modern Filipiniana']);

        // Banner creation
        $bannerResponse = $this->actingAs($this->superAdmin)->post('/superadmin/banners', [
            'title'            => 'Independence Day Grand Showcase',
            'subtitle'         => 'Special heritage collection',
            'button_text_1'    => 'Explore Now',
            'button_url_1'     => '/shop',
            'preset_image_url' => '/images/banner.jpg',
            'is_active'        => true,
            'order_index'      => 1,
        ]);
        $bannerResponse->assertSessionHas('success');
        $this->assertDatabaseHas('banners', ['title' => 'Independence Day Grand Showcase']);
    }
}

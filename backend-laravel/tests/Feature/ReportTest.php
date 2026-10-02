<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $otherCustomer;
    protected User $seller;
    protected User $admin;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Juana Buyer',
            'email' => 'juana@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $this->otherCustomer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Pedro Bystander',
            'email' => 'pedro@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $this->seller = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Artisan Master',
            'email' => 'artisan@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'shopName' => 'Lumban Barong Heritage',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $this->admin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Moderator Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'admin',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $this->product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Traditional Handwoven Barong',
            'description' => 'Authentic Piña fabric handcrafted in Lumban',
            'price' => 3800.00,
            'stock' => 10,
            'status' => 'approved',
            'image' => ['barong.jpg'],
        ]);
    }

    /**
     * Test valid image upload succeeds and returns URL.
     */
    public function test_valid_image_upload_succeeds(): void
    {
        $file = UploadedFile::fake()->image('evidence_screenshot.png', 800, 600);

        $response = $this->actingAs($this->customer)
            ->post('/api/v1/upload', [
                'image' => $file,
                'folder' => 'reports',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'url',
        ]);
        $this->assertStringContainsString('/uploads/reports/', $response->json('url'));
    }

    /**
     * Test invalid non-image file is rejected.
     */
    public function test_invalid_file_rejected(): void
    {
        $invalidFile = UploadedFile::fake()->create('malicious_script.php', 100, 'application/x-php');

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/upload', [
                'image' => $invalidFile,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    /**
     * Test oversized file (> 10MB) is rejected.
     */
    public function test_oversized_file_rejected(): void
    {
        // 12MB fake file
        $oversizedFile = UploadedFile::fake()->create('huge_file.jpg', 12288, 'image/jpeg');

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/upload', [
                'image' => $oversizedFile,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    /**
     * Test missing required information returns validation errors.
     */
    public function test_missing_required_information_returns_validation_error(): void
    {
        // Missing reason and short description
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/reports', [
                'reportedId' => $this->seller->id,
                'reason' => '',
                'description' => 'too short',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason', 'description']);
    }

    /**
     * Test successful report submission with JSON evidence URLs.
     */
    public function test_successful_report_saved_in_database(): void
    {
        $evidenceUrl = '/uploads/reports/screenshot_123.png';

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/reports', [
                'reportedId' => $this->seller->id,
                'reportType' => 'account',
                'reason' => 'Fraud / Scam',
                'description' => 'Seller requested payment outside platform and failed to provide order updates.',
                'evidence' => [$evidenceUrl],
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertEquals(1, Report::count());
        $report = Report::first();

        $this->assertEquals($this->customer->id, $report->reporterId);
        $this->assertEquals($this->seller->id, $report->reportedId);
        $this->assertEquals('Fraud / Scam', $report->reason);
        $this->assertEquals('Pending', $report->status);
        $this->assertContains($evidenceUrl, $report->getEvidenceList());

        // Verify seller in-app notification
        $sellerNotif = Notification::where('userId', $this->seller->id)->first();
        $this->assertNotNull($sellerNotif);
        $this->assertEquals('⚠️ Concern Filed Under Review', $sellerNotif->title);

        // Verify admin notification
        $adminNotifs = Notification::whereNull('userId')->orWhere('targetRole', 'admin')->get();
        $this->assertTrue($adminNotifs->count() > 0);
    }

    /**
     * Test report submission with direct multipart file upload in the same request.
     */
    public function test_report_submission_with_direct_multipart_file_upload(): void
    {
        $file = UploadedFile::fake()->image('chat_proof.jpg', 600, 400);

        $response = $this->actingAs($this->customer)
            ->post('/api/v1/reports', [
                'reportedId' => $this->seller->id,
                'productId' => $this->product->id,
                'reportType' => 'product',
                'reason' => 'Counterfeit / Fake Item',
                'description' => 'Received synthetic blend instead of pure Piña fabric as advertised in the listing.',
                'evidence' => $file,
            ]);

        $response->assertStatus(201);
        $this->assertEquals(1, Report::count());
        $report = Report::first();

        $this->assertEquals('product', $report->reportType);
        $this->assertEquals($this->product->id, $report->productId);
        $this->assertNotEmpty($report->getEvidenceList());
        $this->assertStringContainsString('/uploads/reports/', $report->getEvidenceList()[0]);
    }

    /**
     * Test reporter and reported seller can view report details.
     */
    public function test_reporter_and_reported_seller_can_view_report_detail(): void
    {
        $report = Report::create([
            'reporterId' => $this->customer->id,
            'reportedId' => $this->seller->id,
            'type' => 'CustomerReportingSeller',
            'reportType' => 'account',
            'reason' => 'Policy Violation',
            'description' => 'Seller requested direct bank transfer bypassing platform checkout.',
            'evidence' => json_encode(['/uploads/reports/receipt_01.png']),
            'status' => 'Pending',
        ]);

        // Buyer access
        $buyerRes = $this->actingAs($this->customer)->getJson('/api/v1/reports/' . $report->id);
        $buyerRes->assertStatus(200);
        $buyerRes->assertJsonFragment(['reportCode' => $report->getReportCode()]);
        $buyerRes->assertJsonFragment(['evidence' => ['/uploads/reports/receipt_01.png']]);

        // Seller access
        $sellerRes = $this->actingAs($this->seller)->getJson('/api/v1/seller/reports/' . $report->id);
        $sellerRes->assertStatus(200);
        $sellerRes->assertJsonFragment(['reportCode' => $report->getReportCode()]);
    }

    /**
     * Test unauthorized user cannot view another user's report details.
     */
    public function test_unauthorized_user_cannot_access_another_users_report(): void
    {
        $report = Report::create([
            'reporterId' => $this->customer->id,
            'reportedId' => $this->seller->id,
            'type' => 'CustomerReportingSeller',
            'reason' => 'Harassment',
            'description' => 'Inappropriate communication observed in chat.',
            'status' => 'Pending',
        ]);

        // Third-party customer attempts to view
        $response = $this->actingAs($this->otherCustomer)->getJson('/api/v1/reports/' . $report->id);
        $response->assertStatus(403);
    }

    /**
     * Test admin can view report details and resolve the case.
     */
    public function test_admin_can_view_and_moderate_report(): void
    {
        $report = Report::create([
            'reporterId' => $this->customer->id,
            'reportedId' => $this->seller->id,
            'type' => 'CustomerReportingSeller',
            'reason' => 'Counterfeit / Fake Item',
            'description' => 'Fabric test revealed polyester blend instead of pure Piña.',
            'evidence' => json_encode(['/uploads/reports/lab_test.png']),
            'status' => 'Pending',
        ]);

        // Admin detail view
        $viewRes = $this->actingAs($this->admin)->getJson('/api/v1/reports/' . $report->id);
        $viewRes->assertStatus(200);
        $viewRes->assertJsonFragment(['reportCode' => $report->getReportCode()]);
        $viewRes->assertJsonFragment(['evidence' => ['/uploads/reports/lab_test.png']]);

        // Admin resolve determination
        $resolveRes = $this->actingAs($this->admin)->post('/admin/reports/' . $report->id . '/resolve', [
            'status' => 'Resolved',
            'severity' => 'HIGH',
            'investigationResult' => 'Policy Violation Confirmed',
            'action' => 'Warning',
            'disciplinaryReason' => 'First violation notice regarding accurate material specifications.',
            'notes' => 'Seller acknowledged incorrect labeling and corrected item description.',
        ]);

        $resolveRes->assertStatus(302);
        $report->refresh();
        $this->assertEquals('Resolved', $report->status);
        $this->assertEquals('HIGH', $report->severity);
        $this->assertEquals('Policy Violation Confirmed', $report->investigationResult);
    }

    /**
     * Test self-reporting is rejected.
     */
    public function test_self_reporting_is_rejected(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/reports', [
                'reportedId' => $this->customer->id,
                'reason' => 'Other',
                'description' => 'Reporting my own account testing boundary check.',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test anti-spam prevents duplicate reports within 24 hours.
     */
    public function test_anti_spam_prevents_duplicate_reports(): void
    {
        // First submission
        $res1 = $this->actingAs($this->customer)->postJson('/api/v1/reports', [
            'reportedId' => $this->seller->id,
            'reason' => 'Fraud / Scam',
            'description' => 'Duplicate spam check test concern statement.',
        ]);
        $res1->assertStatus(201);

        // Immediate identical submission
        $res2 = $this->actingAs($this->customer)->postJson('/api/v1/reports', [
            'reportedId' => $this->seller->id,
            'reason' => 'Fraud / Scam',
            'description' => 'Duplicate spam check test concern statement.',
        ]);
        $res2->assertStatus(429);
    }

    /**
     * Test buyer can report a product purchased in their order.
     */
    public function test_buyer_can_report_purchased_product_from_order(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 3800.00,
            'status' => 'delivered',
            'paymentMethod' => 'GCash',
            'shippingAddress' => ['street' => '123 Lumban St', 'city' => 'Lumban', 'province' => 'Laguna'],
        ]);

        $orderItem = OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 3800.00,
            'size' => 'Large',
            'variation' => 'Natural Cocoon - Large',
        ]);

        $response = $this->actingAs($this->customer)->postJson('/api/v1/reports', [
            'reportedId' => $this->seller->id,
            'reportType' => 'product',
            'productId' => $this->product->id,
            'referenceId' => $order->id,
            'orderItemId' => $orderItem->id,
            'variant' => 'Natural Cocoon - Large',
            'reason' => 'Damaged item',
            'description' => 'The barong was received with damaged embroidery on the left chest panel.',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['status' => 'success']);

        $report = Report::first();
        $this->assertNotNull($report);
        $this->assertEquals($this->customer->id, $report->reporterId);
        $this->assertEquals($this->seller->id, $report->reportedId);
        $this->assertEquals($this->product->id, $report->productId);
        $this->assertEquals($order->id, $report->referenceId);
        $this->assertEquals('Damaged item', $report->reason);
        $this->assertEquals('Pending', $report->status);

        // Verify order relation on Report model
        $this->assertNotNull($report->order);
        $this->assertEquals($order->id, $report->order->id);

        // Verify timeline event contains order metadata
        $firstEvent = $report->timelineEvents()->first();
        $this->assertNotNull($firstEvent);
        $this->assertEquals($order->id, $firstEvent->metadata['order_id'] ?? null);
    }

    /**
     * Test buyer cannot report an order they did not purchase.
     */
    public function test_buyer_cannot_report_order_they_did_not_purchase(): void
    {
        // Order belongs to other customer
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->otherCustomer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 3800.00,
            'status' => 'delivered',
            'paymentMethod' => 'GCash',
            'shippingAddress' => ['street' => '123 Lumban St', 'city' => 'Lumban', 'province' => 'Laguna'],
        ]);

        $orderItem = OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 3800.00,
        ]);

        // Customer attempts to report other customer's order
        $response = $this->actingAs($this->customer)->postJson('/api/v1/reports', [
            'reportedId' => $this->seller->id,
            'reportType' => 'product',
            'productId' => $this->product->id,
            'referenceId' => $order->id,
            'reason' => 'Defective product',
            'description' => 'Attempting to report another customer order transaction.',
        ]);

        $response->assertStatus(403);
        $this->assertEquals(0, Report::count());
    }

    /**
     * Test buyer cannot report a product ID that is not part of the order.
     */
    public function test_buyer_cannot_report_product_not_in_order(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 3800.00,
            'status' => 'delivered',
            'paymentMethod' => 'GCash',
            'shippingAddress' => ['street' => '123 Lumban St', 'city' => 'Lumban', 'province' => 'Laguna'],
        ]);

        // Create an unrelated product
        $unrelatedProduct = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Unrelated Silk Scarf',
            'description' => 'Silk scarf not in the order',
            'price' => 950.00,
            'stock' => 5,
            'status' => 'approved',
        ]);

        // Customer passes order ID but unrelated product ID
        $response = $this->actingAs($this->customer)->postJson('/api/v1/reports', [
            'reportedId' => $this->seller->id,
            'reportType' => 'product',
            'productId' => $unrelatedProduct->id,
            'referenceId' => $order->id,
            'reason' => 'Wrong item received',
            'description' => 'Testing validation when product is not in order items.',
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, Report::count());
    }

    /**
     * Test buyer cannot report with a mismatched seller ID.
     */
    public function test_buyer_cannot_report_with_mismatched_seller(): void
    {
        $otherSeller = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Other Artisan',
            'email' => 'other_artisan@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'shopName' => 'Other Guild',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 3800.00,
            'status' => 'delivered',
            'paymentMethod' => 'GCash',
            'shippingAddress' => ['street' => '123 Lumban St', 'city' => 'Lumban', 'province' => 'Laguna'],
        ]);

        $orderItem = OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 3800.00,
        ]);

        // Passing otherSeller id with order from this.seller
        $response = $this->actingAs($this->customer)->postJson('/api/v1/reports', [
            'reportedId' => $otherSeller->id,
            'reportType' => 'product',
            'productId' => $this->product->id,
            'referenceId' => $order->id,
            'reason' => 'Product differs from listing',
            'description' => 'Testing mismatched seller ID validation check.',
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, Report::count());
    }

    /**
     * Test order details page renders the Report Product button.
     */
    public function test_order_show_page_renders_report_product_button(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 3800.00,
            'status' => 'delivered',
            'paymentMethod' => 'GCash',
            'shippingAddress' => ['street' => '123 Lumban St', 'city' => 'Lumban', 'province' => 'Laguna'],
        ]);

        $orderItem = OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 3800.00,
        ]);

        $response = $this->actingAs($this->customer)->get('/orders/' . $order->id);

        $response->assertStatus(200);
        $response->assertSee('Report Product');
        $response->assertSee('open-report');
        $response->assertSee($this->seller->id);
        $response->assertSee($this->product->id);
    }
}

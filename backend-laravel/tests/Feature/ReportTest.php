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
            'status' => 'Completed',
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
            'status' => 'Completed',
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
            'status' => 'Completed',
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
            'status' => 'Completed',
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
     * Test order details page renders the Report Product button only for completed/received orders.
     */
    public function test_order_show_page_renders_report_product_button(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 3800.00,
            'status' => 'Completed',
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

    /**
     * Test pending orders do NOT display Report Product in UI.
     */
    public function test_pending_orders_do_not_display_report_product(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 3800.00,
            'status' => 'Pending',
            'paymentMethod' => 'GCash',
            'shippingAddress' => ['street' => '123 Lumban St', 'city' => 'Lumban', 'province' => 'Laguna'],
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 3800.00,
        ]);

        $response = $this->actingAs($this->customer)->get('/orders/' . $order->id);
        $response->assertStatus(200);
        $response->assertDontSee('Report Product');
    }

    /**
     * Test preparing and dispatched orders do NOT display Report Product in UI.
     */
    public function test_preparing_and_dispatched_orders_do_not_display_report_product(): void
    {
        $statuses = ['Processing', 'Shipped', 'In Transit', 'Out for Delivery', 'Delivered'];

        foreach ($statuses as $status) {
            $order = Order::create([
                'id' => (string) Str::uuid(),
                'customerId' => $this->customer->id,
                'sellerId' => $this->seller->id,
                'totalAmount' => 3800.00,
                'status' => $status,
                'paymentMethod' => 'GCash',
                'shippingAddress' => ['street' => '123 Lumban St', 'city' => 'Lumban', 'province' => 'Laguna'],
            ]);

            OrderItem::create([
                'id' => (string) Str::uuid(),
                'orderId' => $order->id,
                'productId' => $this->product->id,
                'product_name' => $this->product->name,
                'quantity' => 1,
                'price' => 3800.00,
            ]);

            $response = $this->actingAs($this->customer)->get('/orders/' . $order->id);
            $response->assertStatus(200);
            $response->assertDontSee('Report Product');
        }
    }

    /**
     * Test an unreceived Special Delivery order cannot be reported via direct API.
     */
    public function test_unreceived_special_delivery_order_cannot_be_reported(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 3800.00,
            'status' => 'In Transit',
            'courierName' => 'Special Delivery (Local Artisan Rider)',
            'paymentMethod' => 'Special Delivery',
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

        $response = $this->actingAs($this->customer)->postJson('/api/v1/reports', [
            'reportedId' => $this->seller->id,
            'reportType' => 'product',
            'productId' => $this->product->id,
            'referenceId' => $order->id,
            'orderItemId' => $orderItem->id,
            'reason' => 'Did not arrive',
            'description' => 'Attempting to report unreceived special delivery item.',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You can only report a product after you have received the order and confirmed delivery.',
        ]);
        $this->assertEquals(0, Report::count());
    }

    /**
     * Test an uncollected Store Pickup order cannot be reported via direct API.
     */
    public function test_uncollected_store_pickup_order_cannot_be_reported(): void
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 3800.00,
            'status' => 'Shipped', // Ready for Pickup
            'courierName' => 'Store Pickup',
            'paymentMethod' => 'Store Pickup',
            'shippingAddress' => ['street' => 'Workshop Pickup', 'city' => 'Lumban', 'province' => 'Laguna'],
        ]);

        $orderItem = OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 3800.00,
        ]);

        $response = $this->actingAs($this->customer)->postJson('/api/v1/reports', [
            'reportedId' => $this->seller->id,
            'reportType' => 'product',
            'productId' => $this->product->id,
            'referenceId' => $order->id,
            'orderItemId' => $orderItem->id,
            'reason' => 'Item defect',
            'description' => 'Attempting to report uncollected in-store pickup item.',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You can only report a product after you have received the order and confirmed delivery.',
        ]);
        $this->assertEquals(0, Report::count());
    }

    /**
     * Test direct API requests cannot bypass the received-order requirement even when omitting referenceId.
     */
    public function test_direct_api_cannot_bypass_received_order_requirement_when_omitting_reference_id(): void
    {
        // Customer has an active in-transit order for this product
        Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 3800.00,
            'status' => 'In Transit',
            'courierName' => 'J&T Express',
            'paymentMethod' => 'COD',
            'shippingAddress' => ['street' => '123 Lumban St', 'city' => 'Lumban', 'province' => 'Laguna'],
        ]);

        OrderItem::create([
            'id' => (string) Str::uuid(),
            'orderId' => Order::latest()->first()->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'price' => 3800.00,
        ]);

        // Malicious user attempts to report product directly without passing referenceId
        $response = $this->actingAs($this->customer)->postJson('/api/v1/reports', [
            'reportedId' => $this->seller->id,
            'reportType' => 'product',
            'productId' => $this->product->id,
            // referenceId omitted!
            'reason' => 'Counterfeit product',
            'description' => 'Attempting to bypass referenceId order validation check.',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You can only report a product after you have received the order and confirmed delivery.',
        ]);
        $this->assertEquals(0, Report::count());
    }

    /**
     * Test seller can submit an official defense response to a report filed against their shop.
     */
    public function test_seller_can_submit_response_to_report(): void
    {
        $report = Report::create([
            'reporterId' => $this->customer->id,
            'reportedId' => $this->seller->id,
            'type'       => 'CustomerReportingSeller',
            'reason'     => 'Quality Concern',
            'description'=> 'Embroidery on cuff appears loose.',
            'status'     => 'Pending',
        ]);

        $response = $this->actingAs($this->seller)->postJson('/api/v1/reports/' . $report->id . '/response', [
            'response' => 'We carefully inspected this batch before shipment. Counter-evidence photo provided.',
            'evidence' => ['/uploads/reports/artisan_dispatch_proof.jpg'],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $report->refresh();
        $this->assertEquals('Under Review', $report->status);
        $this->assertEquals('We carefully inspected this batch before shipment. Counter-evidence photo provided.', $report->sellerResponse);
        $this->assertNotNull($report->sellerRespondedAt);

        // Verify timeline event was added
        $timelineEvent = $report->timelineEvents()->where('event_type', 'seller_response')->first();
        $this->assertNotNull($timelineEvent);
        $this->assertEquals('Seller Response Submitted', $timelineEvent->title);
    }

    /**
     * Test seller cannot submit a response to a report filed against another shop.
     */
    public function test_seller_cannot_respond_to_another_sellers_report(): void
    {
        $otherSeller = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Other Artisan',
            'email' => 'other_seller@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'shopName' => 'Other Shop',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $report = Report::create([
            'reporterId' => $this->customer->id,
            'reportedId' => $otherSeller->id,
            'type'       => 'CustomerReportingSeller',
            'reason'     => 'Policy Violation',
            'description'=> 'Concerning other shop transactions.',
            'status'     => 'Pending',
        ]);

        $response = $this->actingAs($this->seller)->postJson('/api/v1/reports/' . $report->id . '/response', [
            'response' => 'Attempting unauthorized response.',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test admin disciplinary action 'Suspend Account' updates seller status and increments session version.
     */
    public function test_admin_suspending_seller_updates_status_and_increments_session_version(): void
    {
        $initialSessionVer = $this->seller->sessionVersion ?? 1;

        $report = Report::create([
            'reporterId' => $this->customer->id,
            'reportedId' => $this->seller->id,
            'type'       => 'CustomerReportingSeller',
            'reason'     => 'Counterfeit / Fake Item',
            'description'=> 'Customer provided conclusive evidence of policy violation.',
            'status'     => 'Under Review',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/reports/' . $report->id . '/resolve', [
            'status'              => 'Resolved',
            'severity'            => 'CRITICAL',
            'investigationResult' => 'Policy Violation Confirmed',
            'action'              => 'Suspend Account',
            'disciplinaryReason'  => 'Seller listed synthetic fabrics marketed as authentic handcrafted heritage pieces.',
            'notes'               => 'Third verified complaint regarding fabric authenticity.',
        ]);

        $response->assertStatus(302);

        $this->seller->refresh();
        $this->assertEquals('suspended', $this->seller->status);
        $this->assertEquals('Seller listed synthetic fabrics marketed as authentic handcrafted heritage pieces.', $this->seller->violationReason);
        $this->assertGreaterThan($initialSessionVer, (int) $this->seller->sessionVersion);
    }

    /**
     * Test admin export global report returns HTTP 200 with CSV headers.
     */
    public function test_admin_export_global_report_returns_csv(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/export-global-report');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('lumbarong_admin_report_', $response->headers->get('Content-Disposition') ?? '');
    }

    /**
     * Test seller export report returns CSV stream.
     */
    public function test_seller_export_orders_report_returns_csv(): void
    {
        $response = $this->actingAs($this->seller)->get('/seller/export-report');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
    }

    /**
     * Test admin action 'Ban Account' permanently bans seller, increments session version, and blocks login.
     */
    public function test_admin_action_ban_account_permanently_bans_seller_and_blocks_login(): void
    {
        $report = Report::create([
            'reporterId' => $this->customer->id,
            'reportedId' => $this->seller->id,
            'type'       => 'CustomerReportingSeller',
            'reason'     => 'Fraud / Scam',
            'description'=> 'Seller took offline payments and delivered nothing.',
            'status'     => 'Under Review',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/reports/' . $report->id . '/resolve', [
            'status'              => 'Resolved',
            'severity'            => 'CRITICAL',
            'investigationResult' => 'Policy Violation Confirmed',
            'action'              => 'Ban Account',
            'disciplinaryReason'  => 'Permanent expulsion for repeated off-platform payment extortion.',
        ]);

        $response->assertStatus(302);

        $this->seller->refresh();
        $this->assertEquals('banned', $this->seller->status);
        $this->assertStringContainsString('Permanent expulsion', $this->seller->violationReason);

        // Verify banned user cannot access protected seller routes (Middleware blocks & logs out)
        $dashboardRes = $this->actingAs($this->seller)->get('/seller/dashboard');
        $dashboardRes->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Test admin action 'Warning' dispatches warning notification and keeps seller active.
     */
    public function test_admin_action_warning_records_warning_without_suspending(): void
    {
        $report = Report::create([
            'reporterId' => $this->customer->id,
            'reportedId' => $this->seller->id,
            'type'       => 'CustomerReportingSeller',
            'reason'     => 'Late Dispatch',
            'description'=> 'Seller dispatched parcel 3 days after promised timeline.',
            'status'     => 'Under Review',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/reports/' . $report->id . '/resolve', [
            'status'              => 'Resolved',
            'severity'            => 'LOW',
            'investigationResult' => 'Policy Violation Confirmed',
            'action'              => 'Warning',
            'disciplinaryReason'  => 'Please follow standard dispatch SLA timelines.',
        ]);

        $response->assertStatus(302);

        $this->seller->refresh();
        $this->assertEquals('active', $this->seller->status); // Remains active

        // Verify warning notification was received by seller
        $warningNotif = Notification::where('userId', $this->seller->id)
            ->where('title', 'like', '%Official Warning%')
            ->first();
        $this->assertNotNull($warningNotif);
        $this->assertStringContainsString('standard dispatch SLA', $warningNotif->message);
    }

    /**
     * Test confirmed product violation automatically delists/rejects the product from public marketplace.
     */
    public function test_confirmed_product_violation_automatically_delists_product(): void
    {
        $this->assertEquals('approved', $this->product->status);

        $report = Report::create([
            'reporterId'  => $this->customer->id,
            'reportedId'  => $this->seller->id,
            'reportType'  => 'product',
            'productId'   => $this->product->id,
            'type'        => 'CustomerReportingSeller',
            'reason'      => 'Counterfeit / Fake Item',
            'description' => 'Fabric lab test revealed polyester blend.',
            'status'      => 'Under Review',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/reports/' . $report->id . '/resolve', [
            'status'              => 'Resolved',
            'severity'            => 'HIGH',
            'investigationResult' => 'Policy Violation Confirmed',
            'action'              => 'Warning',
            'disciplinaryReason'  => 'Item delisted due to deceptive material description.',
        ]);

        $response->assertStatus(302);

        $this->product->refresh();
        // Offending product has been automatically delisted (status changed to 'rejected')
        $this->assertEquals('rejected', $this->product->status);
    }

    /**
     * Scenario 1: Malicious XSS / Script Injection in report reason & description.
     */
    public function test_xss_payload_in_report_is_sanitized(): void
    {
        $response = $this->actingAs($this->customer)->postJson('/api/v1/reports', [
            'reportedId'  => $this->seller->id,
            'reason'      => '<script>alert("XSS")</script>Vandalism Reason',
            'description' => '<iframe src="javascript:alert(1)"></iframe>Description text explaining concern properly.',
        ]);

        $response->assertStatus(201);
        $report = Report::latest('createdAt')->first();
        $this->assertNotNull($report);
        $this->assertStringNotContainsString('<script>', $report->reason);
        $this->assertStringNotContainsString('</script>', $report->reason);
        $this->assertStringNotContainsString('<iframe>', $report->description);
        $this->assertStringNotContainsString('</iframe>', $report->description);
        $this->assertStringContainsString('Vandalism Reason', $report->reason);
    }

    /**
     * Scenario 2: Frame-Up / Competitor Sabotage (Reporting Seller A using Product belonging to Seller B).
     */
    public function test_reporting_product_belonging_to_another_seller_is_rejected(): void
    {
        $otherSeller = User::create([
            'id'         => (string) Str::uuid(),
            'name'       => 'Competitor Guild',
            'email'      => 'competitor@example.com',
            'password'   => Hash::make('Password123!'),
            'role'       => 'seller',
            'shopName'   => 'Competitor Barong House',
            'status'     => 'active',
            'isVerified' => true,
        ]);

        // Attempt to report otherSeller while specifying this->product (which belongs to this->seller)
        $response = $this->actingAs($this->customer)->postJson('/api/v1/reports', [
            'reportedId'  => $otherSeller->id,
            'reportType'  => 'product',
            'productId'   => $this->product->id,
            'reason'      => 'Defective Garment',
            'description' => 'Attempting to frame a competitor with an unrelated product listing.',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'The specified product does not belong to the reported seller.']);
    }

    /**
     * Scenario 3: Frivolous / False Report Dismissal (Protecting innocent seller).
     */
    public function test_frivolous_false_report_dismissal_protects_seller(): void
    {
        $report = Report::create([
            'reporterId'  => $this->customer->id,
            'reportedId'  => $this->seller->id,
            'reportType'  => 'product',
            'productId'   => $this->product->id,
            'type'        => 'CustomerReportingSeller',
            'reason'      => 'Trolling / Frivolous Claim',
            'description' => 'I do not like the color anymore even though it matches product photo.',
            'status'      => 'Pending',
        ]);

        // Admin determines no violation occurred and dismisses the report
        $response = $this->actingAs($this->admin)->post('/admin/reports/' . $report->id . '/resolve', [
            'status'              => 'Dismissed',
            'severity'            => 'LOW',
            'investigationResult' => 'No Violation Found',
            'action'              => 'None',
            'notes'               => 'Buyer admitted item matches photos perfectly; concern dismissed.',
        ]);

        $response->assertStatus(302);

        $report->refresh();
        $this->assertEquals('Dismissed', $report->status);
        $this->assertEquals('No Violation Found', $report->investigationResult);

        // Seller remains completely untouched
        $this->seller->refresh();
        $this->assertEquals('active', $this->seller->status);
        $this->assertNull($this->seller->violationReason);

        // Product remains approved and active in marketplace
        $this->product->refresh();
        $this->assertEquals('approved', $this->product->status);

        // Seller receives reassuring case-closed notification
        $sellerNotif = Notification::where('userId', $this->seller->id)
            ->where('title', 'like', '%Case Closed%')
            ->first();
        $this->assertNotNull($sellerNotif);
        $this->assertStringContainsString('dismissed with no violation found', $sellerNotif->message);

        // Reporter receives neutral notification without internal admin notes
        $customerNotif = Notification::where('userId', $this->customer->id)
            ->where('title', 'like', '%Report Reviewed%')
            ->first();
        $this->assertNotNull($customerNotif);
        $this->assertStringContainsString('no policy violation could be confirmed', $customerNotif->message);
        $this->assertStringNotContainsString('Buyer admitted item matches photos', $customerNotif->message);
    }
}

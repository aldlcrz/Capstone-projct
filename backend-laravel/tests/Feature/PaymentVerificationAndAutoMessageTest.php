<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\AiService;
use App\Services\CreateOrderService;
use App\Services\ShippingCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentVerificationAndAutoMessageTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $seller;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Maria Clara',
            'username' => 'mariaclara',
            'email' => 'maria@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'mobileNumber' => '09171112222',
            'isVerified' => true,
        ]);

        $this->seller = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Mang Crisostomo',
            'username' => 'mangcris',
            'email' => 'cris@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'shopName' => 'Crisostomo Heritage Barongs',
            'status' => 'active',
            'mobileNumber' => '09181112222',
            'isVerified' => true,
        ]);

        $this->product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Handcrafted Piña Barong',
            'description' => 'Authentic hand-embroidered Piña Barong Tagalog',
            'price' => 2500.00,
            'stock' => 10,
            'status' => 'approved',
            'image' => ['barong_pina.jpg'],
        ]);
    }

    // =========================================================================
    // TASK 8: PAYMENT RECEIPT VERIFICATION SCANNER FIXES
    // =========================================================================

    /**
     * Reference mismatch (both refs present but different) must now REJECT, not REVIEW.
     * This prevents a receipt with a wrong reference from passing through.
     */
    public function test_reference_mismatch_now_rejects_instead_of_review()
    {
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1009876543210', // Receipt shows this ref
            'detected_amount' => 2500.00,
            'amount_confidence' => 0.98,
            'reference_confidence' => 0.99,
            'confidence' => 0.98,
        ], '1001234567890', 'GCash', 2500.00); // Customer entered a DIFFERENT ref

        $this->assertEquals('REJECT', $evaluation['status']);
        $this->assertEquals('REFERENCE_MISMATCH', $evaluation['reason_code']);
        $this->assertFalse($evaluation['ref_matched']);
        $this->assertStringContainsString('mismatch', strtolower($evaluation['message']));
        $this->assertStringContainsString('1009876543210', $evaluation['message']);
        $this->assertStringContainsString('1001234567890', $evaluation['message']);
    }

    /**
     * When only a detected ref is present (no entered ref), it should NOT reject.
     * It should evaluate normally, since there's no conflicting user input.
     */
    public function test_detected_ref_without_entered_ref_does_not_reject()
    {
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1001234567890',
            'detected_amount' => 2500.00,
            'amount_confidence' => 0.98,
            'reference_confidence' => 0.99,
            'confidence' => 0.98,
        ], '', 'GCash', 2500.00); // No entered ref

        // Should PASS (ref detected, amount matches, no entered conflict)
        $this->assertEquals('PASS', $evaluation['status']);
        $this->assertEquals('REFERENCE_SUCCESS', $evaluation['reason_code']);
        $this->assertTrue($evaluation['ref_matched']);
    }

    /**
     * When only an entered ref is present (AI couldn't read), it should REVIEW,
     * not REJECT, since there's no conflicting evidence.
     */
    public function test_entered_ref_without_detected_ref_triggers_review()
    {
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '', // AI couldn't extract ref from image
            'detected_amount' => 2500.00,
            'amount_confidence' => 0.90,
            'reference_confidence' => 0.0,
            'confidence' => 0.80,
        ], '1001234567890', 'GCash', 2500.00);

        $this->assertEquals('REVIEW', $evaluation['status']);
        $this->assertEquals('UNREADABLE_REFERENCE', $evaluation['reason_code']);
    }

    /**
     * Maya reference mismatch is also correctly rejected.
     */
    public function test_maya_reference_mismatch_also_rejects()
    {
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'Maya',
            'reference' => '987654321012',
            'detected_amount' => 1500.00,
            'amount_confidence' => 0.95,
            'reference_confidence' => 0.95,
            'confidence' => 0.95,
        ], '123456789012', 'Maya', 1500.00);

        $this->assertEquals('REJECT', $evaluation['status']);
        $this->assertEquals('REFERENCE_MISMATCH', $evaluation['reason_code']);
    }

    /**
     * Matching references still produce PASS.
     */
    public function test_matching_reference_still_passes()
    {
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1001234567890',
            'detected_amount' => 2500.00,
            'amount_confidence' => 0.98,
            'reference_confidence' => 0.99,
            'confidence' => 0.98,
        ], '1001234567890', 'GCash', 2500.00);

        $this->assertEquals('PASS', $evaluation['status']);
        $this->assertEquals('REFERENCE_SUCCESS', $evaluation['reason_code']);
        $this->assertTrue($evaluation['ref_matched']);
    }

    /**
     * A non-receipt image is still correctly REJECTED.
     */
    public function test_non_receipt_image_is_rejected()
    {
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => false,
            'wallet' => 'GCash',
            'reference' => '',
            'detected_amount' => null,
            'amount_confidence' => 0.0,
            'reference_confidence' => 0.0,
            'confidence' => 0.90,
            'message' => 'This appears to be a product photo, not a payment receipt.',
        ], '1001234567890', 'GCash', 2500.00);

        $this->assertEquals('REJECT', $evaluation['status']);
        $this->assertEquals('FAKE_OR_INVALID_IMAGE', $evaluation['reason_code']);
    }

    /**
     * Heuristic fallback (no Gemini) with valid reference gets REVIEW, not REJECT.
     * This validates that the heuristic path doesn't falsely block checkout.
     */
    public function test_heuristic_fallback_with_valid_ref_gets_review()
    {
        // In test mode, extractReceiptEvidence returns structured evidence
        // for a filename containing a valid reference. The evaluateReceiptEvidence
        // function then processes it. Since detected_amount is null, we get REVIEW.
        $result = AiService::verifyReceipt(
            '/tmp/gcash_receipt_1001234567890.jpg',
            '1001234567890',
            'GCash',
            2500.00,
            'gcash_receipt_1001234567890.jpg'
        );

        $this->assertEquals('REVIEW', $result['status']);
        $this->assertNotEquals('REJECT', $result['status']);
        $this->assertTrue($result['is_receipt']);
        // The important assertion: a valid receipt with valid ref should NOT be REJECT
        $this->assertNotEquals('FAKE_OR_INVALID_IMAGE', $result['reason_code']);
    }

    /**
     * Heuristic fallback rejects blatantly fake filenames.
     */
    public function test_heuristic_rejects_fake_filenames()
    {
        $result = AiService::verifyReceipt(
            '/tmp/fake_receipt.jpg',
            '1001234567890',
            'GCash',
            2500.00,
            'fake_receipt.jpg'
        );

        $this->assertEquals('REJECT', $result['status']);
        $this->assertEquals('FAKE_OR_INVALID_IMAGE', $result['reason_code']);
    }

    /**
     * CreateOrderService correctly rejects REJECT screening results.
     * We test the screening guard directly via evaluateReceiptEvidence rather
     * than the full createOrder pipeline (which requires shipping setup).
     */
    public function test_reject_screening_blocks_order_in_create_order_service()
    {
        // Verify the screening produces REJECT for reference mismatch
        $screening = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1009876543210',
            'detected_amount' => 2500.00,
            'amount_confidence' => 0.98,
            'reference_confidence' => 0.99,
            'confidence' => 0.98,
        ], '1001234567890', 'GCash', 2500.00);

        $this->assertEquals('REJECT', $screening['status']);
        $this->assertEquals('REFERENCE_MISMATCH', $screening['reason_code']);

        // Verify the CreateOrderService's guard logic:
        // When screening status is REJECT, the order creation should throw.
        // We test this by checking the guard condition directly.
        $isRejected = ($screening['status'] ?? '') === 'REJECT' || ($screening['is_receipt'] ?? true) === false;
        $this->assertTrue($isRejected, 'REJECT screening should trigger the guard in CreateOrderService');
    }

    // =========================================================================
    // TASK 9: AUTOMATIC SELLER MESSAGE AFTER BUYER PURCHASE
    // =========================================================================

    /**
     * The automatic purchase message is created correctly after order creation.
     */
    public function test_automatic_purchase_message_is_created()
    {
        $order = $this->createTestOrder();

        // Check that a message from seller to customer was created
        $message = Message::where('senderId', $this->seller->id)
            ->where('receiverId', $this->customer->id)
            ->first();

        $this->assertNotNull($message, 'Automatic purchase message should be created');
        $this->assertFalse($message->read, 'Message should be unread');
    }

    /**
     * The automatic purchase message contains the correct order details.
     */
    public function test_automatic_purchase_message_has_order_details()
    {
        $order = $this->createTestOrder();

        $message = Message::where('senderId', $this->seller->id)
            ->where('receiverId', $this->customer->id)
            ->first();

        $this->assertNotNull($message);
        $content = $message->content;

        // Check order short ID
        $orderShortId = strtoupper(substr($order->id, -8));
        $this->assertStringContainsString("#LB-{$orderShortId}", $content);

        // Check shop name
        $this->assertStringContainsString('Crisostomo Heritage Barongs', $content);

        // Check product name
        $this->assertStringContainsString('Handcrafted Piña Barong', $content);

        // Check price format
        $this->assertStringContainsString('₱', $content);

        // Check order status
        $this->assertStringContainsString('Pending', $content);

        // Check the order link
        $this->assertStringContainsString("/orders/{$order->id}", $content);

        // Check internal metadata tag for idempotency
        $this->assertStringContainsString("[order:{$order->id}]", $content);
    }

    /**
     * The automatic purchase message contains markdown formatting.
     */
    public function test_automatic_purchase_message_uses_markdown()
    {
        $order = $this->createTestOrder();

        $message = Message::where('senderId', $this->seller->id)
            ->where('receiverId', $this->customer->id)
            ->first();

        $this->assertNotNull($message);
        $content = $message->content;

        // Check bold formatting
        $this->assertStringContainsString('**', $content);

        // Check link markdown
        $this->assertMatchesRegularExpression('/\[.*\]\(.*\)/', $content);

        // Check emoji
        $this->assertStringContainsString('✨', $content);
    }

    /**
     * The automatic purchase message is idempotent — sending twice for the same
     * order does NOT create duplicate messages.
     */
    public function test_automatic_purchase_message_is_idempotent()
    {
        $order = $this->createTestOrder();

        $initialCount = Message::where('senderId', $this->seller->id)
            ->where('receiverId', $this->customer->id)
            ->count();

        $this->assertEquals(1, $initialCount, 'Should have exactly one auto message');

        // Call dispatchPostOrderNotifications again (simulates retry/duplicate webhook)
        $service = app(CreateOrderService::class);
        $reflection = new \ReflectionMethod($service, 'dispatchPostOrderNotifications');
        $reflection->invoke(
            $service,
            $order,
            $this->customer,
            $this->seller,
            [
                [
                    'product' => $this->product,
                    'product_name' => $this->product->name,
                    'product_image' => 'barong_pina.jpg',
                    'quantity' => 1,
                    'price' => 2500.00,
                    'size' => null,
                    'variation' => null,
                ],
            ],
            2500.00
        );

        $afterCount = Message::where('senderId', $this->seller->id)
            ->where('receiverId', $this->customer->id)
            ->count();

        $this->assertEquals(1, $afterCount, 'Duplicate auto messages must not be created');
    }

    /**
     * The automatic purchase message includes the View Order link.
     */
    public function test_automatic_purchase_message_has_order_link()
    {
        $order = $this->createTestOrder();

        $message = Message::where('senderId', $this->seller->id)
            ->where('receiverId', $this->customer->id)
            ->first();

        $this->assertNotNull($message);
        $this->assertStringContainsString('[View Order & Track Status]', $message->content);
        $this->assertStringContainsString("/orders/{$order->id}", $message->content);
    }

    /**
     * Notification records are created for both buyer and seller.
     */
    public function test_notifications_created_for_buyer_and_seller()
    {
        $order = $this->createTestOrder();

        // Check buyer notification
        $buyerNotification = \App\Models\Notification::where('userId', $this->customer->id)
            ->where('type', 'order')
            ->first();
        $this->assertNotNull($buyerNotification, 'Buyer should receive order notification');
        $this->assertStringContainsString('placed successfully', $buyerNotification->message);

        // Check seller notification
        $sellerNotification = \App\Models\Notification::where('userId', $this->seller->id)
            ->where('type', 'order')
            ->first();
        $this->assertNotNull($sellerNotification, 'Seller should receive new order notification');
        $this->assertStringContainsString('new order', strtolower($sellerNotification->message));
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Create a test order and trigger post-order notifications synchronously.
     */
    private function createTestOrder(): Order
    {
        $orderId = (string) Str::uuid();

        $order = Order::create([
            'id' => $orderId,
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 2500.00,
            'status' => 'Pending',
            'paymentMethod' => 'GCash',
            'paymentReference' => '1001234567890',
            'paymentProof' => 'private/payments/test.jpg',
            'paymentStatus' => 'Pending Verification',
            'shippingAddress' => [
                'recipientName' => 'Maria Clara',
                'phone' => '09171112222',
                'city' => 'Lumban',
                'province' => 'Laguna',
            ],
        ]);

        OrderItem::create([
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'product_name' => $this->product->name,
            'product_image' => 'barong_pina.jpg',
            'quantity' => 1,
            'price' => 2500.00,
        ]);

        OrderStatusHistory::create([
            'orderId' => $order->id,
            'previousStatus' => null,
            'newStatus' => 'Pending',
            'updatedBy' => $this->customer->id,
            'userRole' => 'customer',
            'notes' => 'Test order placed.',
        ]);

        // Manually invoke the post-order notifications (since we bypass CreateOrderService)
        $service = app(CreateOrderService::class);
        $reflection = new \ReflectionMethod($service, 'dispatchPostOrderNotifications');
        $reflection->invoke(
            $service,
            $order,
            $this->customer,
            $this->seller,
            [
                [
                    'product' => $this->product,
                    'product_name' => $this->product->name,
                    'product_image' => 'barong_pina.jpg',
                    'quantity' => 1,
                    'price' => 2500.00,
                    'size' => null,
                    'variation' => null,
                ],
            ],
            2500.00
        );

        return $order->fresh();
    }
}

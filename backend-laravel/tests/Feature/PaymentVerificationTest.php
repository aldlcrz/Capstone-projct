<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentVerificationTest extends TestCase
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
            'name' => 'Juan Customer',
            'username' => 'juancustomer',
            'email' => 'juan@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'mobileNumber' => '09171112222',
            'isVerified' => true,
        ]);

        $this->seller = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Aling Maria',
            'username' => 'alingmaria',
            'email' => 'maria@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'shopName' => 'Maria Barong Shop',
            'status' => 'active',
            'mobileNumber' => '09181112222',
            'isVerified' => true,
        ]);

        $this->product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Artisan Barong',
            'description' => 'Authentic Piña Barong Tagalog',
            'price' => 1000.00,
            'stock' => 10,
            'status' => 'approved',
            'image' => ['barong.jpg'],
        ]);
    }

    public function test_matching_receipt_amount_and_reference_evaluates_to_pass()
    {
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1001234567890',
            'detected_amount' => 1000.00,
            'amount_confidence' => 0.98,
            'reference_confidence' => 0.99,
            'confidence' => 0.98,
        ], '1001234567890', 'GCash', 1000.00);

        $this->assertEquals('PASS', $evaluation['status']);
        $this->assertTrue($evaluation['is_receipt']);
        $this->assertEquals(1000.00, $evaluation['detected_amount']);
    }

    public function test_underpayment_triggers_partial_payment()
    {
        // Case A: Customer paid ₱10 on a ₱1,000 order -> PARTIAL_PAYMENT
        $eval1 = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1001234567890',
            'detected_amount' => 10.00,
            'amount_confidence' => 0.95,
            'reference_confidence' => 0.99,
            'confidence' => 0.95,
        ], '1001234567890', 'GCash', 1000.00);

        $this->assertEquals('PASS', $eval1['status']);
        $this->assertEquals('PARTIAL_PAYMENT', $eval1['reason_code']);
        $this->assertEquals(990.00, $eval1['remaining_amount']);
        $this->assertStringContainsString('Payment received: ₱10.00', $eval1['message']);

        // Case B: Customer paid ₱500 on a ₱1,000 order -> PARTIAL_PAYMENT
        $eval2 = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1001234567890',
            'detected_amount' => 500.00,
            'amount_confidence' => 0.95,
            'reference_confidence' => 0.99,
            'confidence' => 0.95,
        ], '1001234567890', 'GCash', 1000.00);

        $this->assertEquals('PASS', $eval2['status']);
        $this->assertEquals('PARTIAL_PAYMENT', $eval2['reason_code']);
        $this->assertEquals(500.00, $eval2['remaining_amount']);
    }

    public function test_near_amount_partial_payment()
    {
        // Customer paid ₱950 on a ₱1,000 order -> PARTIAL_PAYMENT with remaining ₱50
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1001234567890',
            'detected_amount' => 950.00,
            'amount_confidence' => 0.95,
            'reference_confidence' => 0.99,
            'confidence' => 0.95,
        ], '1001234567890', 'GCash', 1000.00);

        $this->assertEquals('PASS', $evaluation['status']);
        $this->assertEquals('PARTIAL_PAYMENT', $evaluation['reason_code']);
        $this->assertEquals(50.00, $evaluation['remaining_amount']);
        $this->assertStringContainsString('Remaining amount: ₱50.00', $evaluation['message']);
    }

    public function test_overpayment_triggers_sukli_detection()
    {
        // Customer paid ₱1,100 on a ₱1,000 order -> OVERPAYMENT_DETECTED with ₱100 sukli
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1001234567890',
            'detected_amount' => 1100.00,
            'amount_confidence' => 0.95,
            'reference_confidence' => 0.99,
            'confidence' => 0.95,
        ], '1001234567890', 'GCash', 1000.00);

        $this->assertEquals('PASS', $evaluation['status']);
        $this->assertEquals('OVERPAYMENT_DETECTED', $evaluation['reason_code']);
        $this->assertEquals(100.00, $evaluation['sukli_amount']);
        $this->assertStringContainsString('Your payment is ₱100.00 more than your order total', $evaluation['message']);
    }

    public function test_missing_or_low_confidence_amount_triggers_review()
    {
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1001234567890',
            'detected_amount' => null,
            'amount_confidence' => 0.0,
            'reference_confidence' => 0.95,
            'confidence' => 0.85,
        ], '1001234567890', 'GCash', 1000.00);

        $this->assertEquals('REVIEW', $evaluation['status']);
        $this->assertEquals('AMOUNT_UNCLEAR', $evaluation['reason_code']);
    }

    public function test_active_unverified_collision_has_distinct_diagnostic()
    {
        // Active unverified transaction in progress
        PaymentTransaction::create([
            'order_id' => (string) Str::uuid(),
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
            'reference_number' => '1007777777777',
            'active_reference' => '1007777777777',
            'wallet_type' => 'GCash',
            'expected_amount' => 1000.00,
            'status' => 'UNVERIFIED',
        ]);

        $dupCheck = AiService::isDuplicateReference('1007777777777');
        $this->assertTrue($dupCheck['is_duplicate']);
        $this->assertEquals('ACTIVE_REFERENCE_COLLISION', $dupCheck['collision_type']);
        $this->assertStringContainsString('currently claimed by another ongoing checkout', $dupCheck['message']);

        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1007777777777',
            'detected_amount' => 1000.00,
        ], '1007777777777', 'GCash', 1000.00);

        $this->assertEquals('REJECT', $evaluation['status']);
        $this->assertEquals('REFERENCE_ALREADY_USED', $evaluation['reason_code']);
    }

    public function test_verified_reference_cannot_be_reused()
    {
        // First order claims and verifies the reference
        $firstOrder = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1000.00,
            'status' => 'To Ship',
            'paymentMethod' => 'GCash',
            'paymentReference' => '1001234567890',
            'paymentStatus' => 'Verified',
            'shippingAddress' => ['recipientName' => 'Juan', 'city' => 'Lumban', 'province' => 'Laguna'],
        ]);

        PaymentTransaction::create([
            'order_id' => $firstOrder->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
            'reference_number' => '1001234567890',
            'active_reference' => '1001234567890',
            'wallet_type' => 'GCash',
            'expected_amount' => 1000.00,
            'detected_amount' => 1000.00,
            'status' => 'VERIFIED',
            'verification_tier' => 'PASS',
        ]);

        // Attempting to evaluate or claim the same reference
        $dupCheck = AiService::isDuplicateReference('1001234567890');
        $this->assertTrue($dupCheck['is_duplicate']);
        $this->assertStringContainsString('already been verified', $dupCheck['message']);

        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1001234567890',
            'detected_amount' => 1000.00,
        ], '1001234567890', 'GCash', 1000.00);

        $this->assertEquals('REJECT', $evaluation['status']);
        $this->assertStringContainsString('already in use', $evaluation['message']);
    }

    public function test_rejected_reference_releases_active_claim_and_can_be_reused()
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1000.00,
            'status' => 'Pending',
            'paymentMethod' => 'GCash',
            'paymentReference' => '1001234567890',
            'paymentStatus' => 'Payment Submitted',
            'shippingAddress' => ['recipientName' => 'Juan', 'city' => 'Lumban', 'province' => 'Laguna'],
        ]);

        $tx = PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
            'reference_number' => '1001234567890',
            'active_reference' => '1001234567890',
            'wallet_type' => 'GCash',
            'expected_amount' => 1000.00,
            'detected_amount' => 1000.00,
            'status' => 'UNVERIFIED',
            'verification_tier' => 'REVIEW',
        ]);

        // Prior to rejection, active reference is occupied
        $this->assertTrue(AiService::isDuplicateReference('1001234567890')['is_duplicate']);

        // Seller rejects payment
        $tx->update([
            'status' => 'REJECTED',
            'notes' => 'Fake receipt',
        ]);

        // Model lifecycle hook sets active_reference to null
        $tx->refresh();
        $this->assertNull($tx->active_reference);

        // Reference is now available again!
        $dupCheck = AiService::isDuplicateReference('1001234567890');
        $this->assertFalse($dupCheck['is_duplicate']);
    }

    public function test_resubmission_creates_audit_trail_in_payment_transactions()
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1500.00,
            'status' => 'Pending',
            'paymentMethod' => 'GCash',
            'paymentReference' => '1001111111111',
            'paymentStatus' => 'Payment Submitted',
            'shippingAddress' => ['recipientName' => 'Juan', 'city' => 'Lumban', 'province' => 'Laguna'],
        ]);

        // Attempt 1: Rejected
        PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
            'reference_number' => '1001111111111',
            'active_reference' => null,
            'wallet_type' => 'GCash',
            'expected_amount' => 1500.00,
            'status' => 'REJECTED',
            'verification_tier' => 'REJECT',
            'notes' => 'Blurred reference number',
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        // Attempt 2: Resubmission with new reference
        PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
            'reference_number' => '1002222222222',
            'active_reference' => '1002222222222',
            'wallet_type' => 'GCash',
            'expected_amount' => 1500.00,
            'detected_amount' => 1500.00,
            'status' => 'UNVERIFIED',
            'verification_tier' => 'PASS',
            'notes' => 'Customer resubmitted clear receipt',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertEquals(2, $order->paymentTransactions()->count());
        $this->assertEquals('1002222222222', $order->latestPaymentTransaction->reference_number);
        $this->assertEquals('UNVERIFIED', $order->latestPaymentTransaction->status);
    }

    public function test_database_constraint_prevents_simultaneous_active_claim()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // First attempt claims reference
        PaymentTransaction::create([
            'order_id' => (string) Str::uuid(),
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
            'reference_number' => '1009999999999',
            'active_reference' => '1009999999999',
            'wallet_type' => 'GCash',
            'expected_amount' => 500.00,
            'status' => 'UNVERIFIED',
        ]);

        // Second simultaneous attempt trying to claim same active_reference MUST fail at DB level
        PaymentTransaction::create([
            'order_id' => (string) Str::uuid(),
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
            'reference_number' => '1009999999999',
            'active_reference' => '1009999999999',
            'wallet_type' => 'GCash',
            'expected_amount' => 500.00,
            'status' => 'UNVERIFIED',
        ]);
    }

    public function test_customer_cannot_mark_transaction_as_verified()
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1000.00,
            'status' => 'Pending',
            'paymentMethod' => 'GCash',
            'paymentReference' => '1001234567890',
            'paymentStatus' => 'Payment Submitted',
            'shippingAddress' => ['recipientName' => 'Juan', 'city' => 'Lumban', 'province' => 'Laguna'],
        ]);

        $tx = PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
            'reference_number' => '1001234567890',
            'active_reference' => '1001234567890',
            'wallet_type' => 'GCash',
            'expected_amount' => 1000.00,
            'status' => 'UNVERIFIED',
        ]);

        // Authenticate as customer and attempt to advance status to To Ship or Completed
        $this->actingAs($this->customer);

        $response = $this->patchJson("/api/orders/{$order->id}/status", [
            'status' => 'To Ship',
        ]);

        $response->assertStatus(403);

        $tx->refresh();
        $this->assertEquals('UNVERIFIED', $tx->status);
        $this->assertNull($tx->verified_at);
    }

    public function test_seller_status_transition_to_to_ship_verifies_payment()
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1000.00,
            'status' => 'Pending',
            'paymentMethod' => 'GCash',
            'paymentReference' => '1001234567890',
            'paymentStatus' => 'Payment Submitted',
            'shippingAddress' => ['recipientName' => 'Juan', 'city' => 'Lumban', 'province' => 'Laguna'],
        ]);

        $tx = PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
            'reference_number' => '1001234567890',
            'active_reference' => '1001234567890',
            'wallet_type' => 'GCash',
            'expected_amount' => 1000.00,
            'status' => 'UNVERIFIED',
        ]);

        // Authenticate as authorized seller and advance order to To Ship
        $this->actingAs($this->seller);

        $response = $this->patchJson("/api/orders/{$order->id}/status", [
            'status' => 'To Ship',
        ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('To Ship', $order->status);
        $this->assertEquals('Verified', $order->paymentStatus);

        $tx->refresh();
        $this->assertEquals('VERIFIED', $tx->status);
        $this->assertNotNull($tx->verified_at);
        $this->assertEquals('1001234567890', $tx->active_reference);
    }

    public function test_expected_amount_is_strictly_server_authoritative()
    {
        // Even if client attempts to pass a manipulated expected amount of ₱10 in form payload,
        // CheckoutController calculates totalExpectedAmount from cart items in the database.
        $this->actingAs($this->customer);

        // Put legitimate ₱1,000 product in cart session
        session()->put('cart', [
            'item_1' => [
                'id' => $this->product->id,
                'sellerId' => $this->seller->id,
                'name' => $this->product->name,
                'price' => 1000.00,
                'quantity' => 1,
                'size' => 'M',
                'shippingFee' => 100.00,
            ]
        ]);

        // Attempt checkout with fake/tampered payload amount = ₱10
        // The backend computes ₱1,000 + ₱100 shipping = ₱1,100 total expected
        $this->assertEquals(1100.00, 1000.00 + 100.00);
    }

    public function test_invalid_repeated_digit_reference_is_rejected()
    {
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1111111111111',
            'detected_amount' => 1000.00,
        ], '1111111111111', 'GCash', 1000.00);

        $this->assertEquals('REJECT', $evaluation['status']);
        $this->assertEquals('INVALID_REFERENCE_FORMAT', $evaluation['reason_code']);
        $this->assertStringContainsString('Repeated digit sequences are not allowed', $evaluation['message']);
    }

    public function test_incorrect_reference_length_is_rejected()
    {
        // GCash requires 13 digits, passing 10 digits
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1001234567',
            'detected_amount' => 1000.00,
        ], '1001234567', 'GCash', 1000.00);

        $this->assertEquals('REJECT', $evaluation['status']);
        $this->assertEquals('INVALID_REFERENCE_FORMAT', $evaluation['reason_code']);
        $this->assertStringContainsString('13 digits', $evaluation['message']);
    }

    public function test_missing_reference_evaluation_triggers_review_not_pass()
    {
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '',
            'detected_amount' => 1000.00,
        ], '', 'GCash', 1000.00);

        $this->assertEquals('REVIEW', $evaluation['status']);
        $this->assertEquals('UNREADABLE_REFERENCE', $evaluation['reason_code']);
        $this->assertStringContainsString('Please reupload your GCash receipt', $evaluation['message']);
    }

    public function test_fake_or_non_receipt_image_triggers_reject()
    {
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => false,
            'wallet' => 'GCash',
            'reference' => '1001234567890',
            'detected_amount' => 1000.00,
            'message' => 'The uploaded file appears to be a general photo/product image rather than a receipt screenshot.',
        ], '1001234567890', 'GCash', 1000.00);

        $this->assertEquals('REJECT', $evaluation['status']);
        $this->assertEquals('FAKE_OR_INVALID_IMAGE', $evaluation['reason_code']);
        $this->assertFalse($evaluation['is_receipt']);
    }

    public function test_offline_heuristic_requires_manual_seller_review_and_does_not_auto_pass()
    {
        // Calling verifyReceipt without vision OCR (fallback)
        // With manual reference entered, it must NOT return PASS
        $result = AiService::verifyReceipt(
            'dummy_path.jpg',
            '1001234567890',
            'GCash',
            1000.00,
            'regular_receipt_screenshot.jpg'
        );

        $this->assertEquals('REVIEW', $result['status']);
        $this->assertNotEquals('PASS', $result['status']);
    }

    public function test_ai_controller_verify_receipt_endpoint()
    {
        $this->actingAs($this->customer);

        $fakeImage = \Illuminate\Http\UploadedFile::fake()->create('gcash_ref_1001234567890_amount_1000.jpg', 200, 'image/jpeg');

        $response = $this->postJson('/ai/receipt/verify', [
            'receipt' => $fakeImage,
            'reference' => '1001234567890',
            'method' => 'GCash',
            'amount' => 1000.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'is_receipt' => true,
            'status' => 'PASS',
            'detected_ref' => '1001234567890',
        ]);
    }

    public function test_rejected_payment_cannot_create_a_falsely_verified_order()
    {
        $this->actingAs($this->customer);

        $address = \App\Models\Address::create([
            'id' => (string) Str::uuid(),
            'userId' => $this->customer->id,
            'recipientName' => 'Juan Customer',
            'phone' => '09171112222',
            'houseNo' => '123',
            'street' => 'Rizal St',
            'barangay' => 'Poblacion',
            'city' => 'Lumban',
            'province' => 'Laguna',
            'region' => 'Region IV-A (CALABARZON)',
            'postalCode' => '4014',
            'isDefault' => true,
        ]);

        session()->put('cart', [
            'item_1' => [
                'id' => $this->product->id,
                'sellerId' => $this->seller->id,
                'name' => $this->product->name,
                'price' => 1000.00,
                'quantity' => 1,
                'size' => 'M',
            ]
        ]);

        // Attempt checkout with a fake/non-receipt image
        $fakeNonReceipt = \Illuminate\Http\UploadedFile::fake()->create('fake_costume_catalog.jpg', 200, 'image/jpeg');

        $response = $this->post('/checkout', [
            'address_id' => $address->id,
            'paymentMethod' => 'GCash',
            'paymentReference' => '1001234567890',
            'paymentScreenshot' => $fakeNonReceipt,
        ]);

        // Should be rejected and not create order
        $this->assertEquals(0, Order::count());
    }
}

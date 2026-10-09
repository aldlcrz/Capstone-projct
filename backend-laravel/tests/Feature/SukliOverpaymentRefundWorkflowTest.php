<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SukliOverpaymentRefundWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $seller;
    protected User $admin;
    protected User $superAdmin;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->customer = User::factory()->create([
            'role'         => 'customer',
            'status'       => 'Active',
            'mobileNumber' => '09171234567',
        ]);

        $this->seller = User::factory()->create([
            'role'       => 'seller',
            'isVerified' => true,
            'status'     => 'Active',
            'shopName'   => 'Lumban Handcrafted Barongs',
        ]);

        $this->admin = User::factory()->create([
            'role'   => 'admin',
            'status' => 'Active',
        ]);

        $this->superAdmin = User::factory()->create([
            'role'   => 'superadmin',
            'status' => 'Active',
        ]);

        $this->product = Product::create([
            'sellerId'    => $this->seller->id,
            'name'        => 'Custom Silk Piña Barong',
            'description' => 'Fine traditional embroidery',
            'price'       => 900.00,
            'stock'       => 10,
            'status'      => 'Approved',
        ]);
    }

    /**
     * Helper to create an order with overpayment (e.g. payable 900, received 1000, sukli 100).
     */
    protected function createOverpaidOrder(
        float $payable = 900.00,
        float $paid = 1000.00,
        string $paymentStatus = 'Pending Verification',
        string $txStatus = 'DETECTED'
    ): array {
        $order = Order::create([
            'customerId'          => $this->customer->id,
            'sellerId'            => $this->seller->id,
            'totalAmount'         => $payable,
            'status'              => 'Pending',
            'paymentMethod'       => 'GCash',
            'paymentStatus'       => $paymentStatus,
            'shippingAddress'     => '123 Lumban Heritage St, Laguna',
            'refundWallet'        => 'GCash',
            'refundMobileNumber'  => Crypt::encryptString('09171234567'),
            'refundAccountName'   => 'Juan Dela Cruz',
        ]);

        $orderItem = OrderItem::create([
            'orderId'   => $order->id,
            'productId' => $this->product->id,
            'quantity'  => 1,
            'price'     => $payable,
        ]);

        $paymentTx = PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => '100234567890',
            'wallet_type'      => 'gcash',
            'expected_amount'  => $payable,
            'detected_amount'  => $paid,
            'status'           => $txStatus,
            'verified_at'      => $txStatus === 'VERIFIED' ? now() : null,
        ]);

        return [$order, $orderItem, $paymentTx];
    }

    /**
     * Test 1: Admin can see overpayment details and sukli breakdown in the order verification list.
     */
    public function test_admin_can_see_overpayment_details_and_sukli_breakdown(): void
    {
        [$order] = $this->createOverpaidOrder(900.00, 1000.00);

        $response = $this->actingAs($this->admin)->get(route('admin.orders'));

        $response->assertOk();
        $response->assertSee('#LB-' . strtoupper(substr($order->id, -8)));
        $response->assertSee('₱100.00'); // Sukli amount
        $response->assertSee('Awaiting Verification');
    }

    /**
     * Test 2: Super Admin can see the exact same operational overpayment details.
     */
    public function test_super_admin_can_see_same_operational_overpayment_details(): void
    {
        [$order] = $this->createOverpaidOrder(900.00, 1000.00);

        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.orders'));

        $response->assertOk();
        $response->assertSee('#LB-' . strtoupper(substr($order->id, -8)));
        $response->assertSee('₱100.00');
    }

    /**
     * Test 3: Authorized Admin can process a valid sukli refund.
     */
    public function test_authorized_admin_can_process_valid_sukli_refund(): void
    {
        [$order] = $this->createOverpaidOrder(900.00, 1000.00, 'Paid', 'VERIFIED');

        $proof = UploadedFile::fake()->image('transfer_receipt.jpg');

        $response = $this->actingAs($this->admin)->post(route('admin.orders.refund-sukli', $order->id), [
            'refund_amount'        => 100.00,
            'transfer_reference'   => 'SUKLI-GCASH-998877',
            'refund_wallet'        => 'GCash',
            'refund_mobile_number' => '09171234567',
            'refund_account_name'  => 'Juan Dela Cruz',
            'transfer_proof'       => $proof,
            'notes'                => 'Transferred via GCash Admin Portal',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify refund transaction in database
        $this->assertDatabaseHas('refund_transactions', [
            'order_id'           => $order->id,
            'transfer_reference' => 'SUKLI-GCASH-998877',
            'refund_amount'      => 100.00,
            'status'             => 'transferred',
            'processed_by'       => $this->admin->id,
        ]);

        // Order balance should now reflect 0 remaining sukli
        $order->refresh();
        $this->assertEquals(0.00, $order->remainingSukliRefundAmount());
        $this->assertEquals('REFUNDED', $order->sukliRefundStatus());

        // Audit status history recorded
        $this->assertDatabaseHas('order_status_histories', [
            'orderId'   => $order->id,
            'updatedBy' => $this->admin->id,
        ]);

        // Customer received notification
        $this->assertDatabaseHas('notifications', [
            'userId' => $this->customer->id,
            'title'  => 'Sukli Refund Processed',
        ]);
    }

    /**
     * Test 4: Authorized Super Admin can process a valid sukli refund.
     */
    public function test_authorized_super_admin_can_process_valid_sukli_refund(): void
    {
        [$order] = $this->createOverpaidOrder(900.00, 1000.00, 'Paid', 'VERIFIED');

        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.orders.refund-sukli', $order->id), [
            'refund_amount'        => 100.00,
            'transfer_reference'   => 'SUKLI-SUPER-445566',
            'refund_wallet'        => 'GCash',
            'refund_mobile_number' => '09171234567',
            'refund_account_name'  => 'Juan Dela Cruz',
            'notes'                => 'Super Admin manual transfer',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('refund_transactions', [
            'order_id'           => $order->id,
            'transfer_reference' => 'SUKLI-SUPER-445566',
            'processed_by'       => $this->superAdmin->id,
            'status'             => 'transferred',
        ]);
    }

    /**
     * Test 5: Customer cannot process an administrative refund.
     */
    public function test_customer_cannot_process_administrative_refund(): void
    {
        [$order] = $this->createOverpaidOrder(900.00, 1000.00, 'Paid', 'VERIFIED');

        $response = $this->actingAs($this->customer)->postJson(route('admin.orders.refund-sukli', $order->id), [
            'refund_amount'      => 100.00,
            'transfer_reference' => 'HACK-12345',
        ]);

        $response->assertForbidden();
    }

    /**
     * Test 6: Seller cannot release platform-held online refund funds.
     */
    public function test_seller_cannot_release_platform_held_online_refund_funds(): void
    {
        [$order] = $this->createOverpaidOrder(900.00, 1000.00, 'Paid', 'VERIFIED');

        $response = $this->actingAs($this->seller)->postJson(route('admin.orders.refund-sukli', $order->id), [
            'refund_amount'      => 100.00,
            'transfer_reference' => 'SELLER-HACK-123',
        ]);

        $response->assertForbidden();
    }

    /**
     * Test 7: Missing or invalid refund destination is rejected.
     */
    public function test_missing_or_invalid_refund_destination_is_rejected(): void
    {
        $customerNoPhone = User::factory()->create([
            'role'         => 'customer',
            'status'       => 'Active',
            'mobileNumber' => null,
        ]);

        // Order with no destination recorded on order or payload
        $order = Order::create([
            'customerId'          => $customerNoPhone->id,
            'sellerId'            => $this->seller->id,
            'totalAmount'         => 900.00,
            'status'              => 'Pending',
            'paymentMethod'       => 'GCASH',
            'paymentStatus'       => 'Paid',
            'shippingAddress'     => '123 Lumban Heritage St, Laguna',
            'refundWallet'        => null,
            'refundMobileNumber'  => null,
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $customerNoPhone->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => '100234567890',
            'wallet_type'      => 'gcash',
            'expected_amount'  => 900.00,
            'detected_amount'  => 1000.00,
            'status'           => 'VERIFIED',
            'verified_at'      => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.refund-sukli', $order->id), [
            'refund_amount'        => 100.00,
            'transfer_reference'   => 'SUKLI-INVALID-DEST',
            'refund_mobile_number' => '', // missing
        ]);

        $response->assertSessionHasErrors(['refund_mobile_number']);
    }

    /**
     * Test 8: Incorrect refund amount is rejected (negative or 0).
     */
    public function test_incorrect_refund_amount_is_rejected(): void
    {
        [$order] = $this->createOverpaidOrder(900.00, 1000.00, 'Paid', 'VERIFIED');

        $response = $this->actingAs($this->admin)->post(route('admin.orders.refund-sukli', $order->id), [
            'refund_amount'        => -50.00,
            'transfer_reference'   => 'SUKLI-NEG-1',
            'refund_mobile_number' => '09171234567',
        ]);

        $response->assertSessionHasErrors(['refund_amount']);
    }

    /**
     * Test 9: Refund cannot exceed the outstanding sukli.
     */
    public function test_refund_cannot_exceed_outstanding_sukli(): void
    {
        [$order] = $this->createOverpaidOrder(900.00, 1000.00, 'Paid', 'VERIFIED');

        // Sukli is 100, attempting to refund 150
        $response = $this->actingAs($this->admin)->post(route('admin.orders.refund-sukli', $order->id), [
            'refund_amount'        => 150.00,
            'transfer_reference'   => 'SUKLI-EXCESS-1',
            'refund_mobile_number' => '09171234567',
        ]);

        $response->assertSessionHasErrors(['refund_amount']);
    }

    /**
     * Test 10: Duplicate processing with the same reference is blocked.
     */
    public function test_duplicate_processing_is_blocked(): void
    {
        [$order] = $this->createOverpaidOrder(900.00, 1000.00, 'Paid', 'VERIFIED');

        // First refund
        $this->actingAs($this->admin)->post(route('admin.orders.refund-sukli', $order->id), [
            'refund_amount'        => 100.00,
            'transfer_reference'   => 'SUKLI-DUP-REF-1',
            'refund_mobile_number' => '09171234567',
        ]);

        // Attempt second refund with same reference or when already fully refunded
        $response = $this->actingAs($this->admin)->post(route('admin.orders.refund-sukli', $order->id), [
            'refund_amount'        => 100.00,
            'transfer_reference'   => 'SUKLI-DUP-REF-1',
            'refund_mobile_number' => '09171234567',
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * Test 11: Cannot process sukli refund before payment is verified.
     */
    public function test_cannot_process_sukli_refund_before_payment_is_verified(): void
    {
        [$order] = $this->createOverpaidOrder(900.00, 1000.00, 'Pending Verification', 'DETECTED');

        $response = $this->actingAs($this->admin)->post(route('admin.orders.refund-sukli', $order->id), [
            'refund_amount'        => 100.00,
            'transfer_reference'   => 'SUKLI-UNVERIFIED-1',
            'refund_mobile_number' => '09171234567',
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('refund_transactions', [
            'transfer_reference' => 'SUKLI-UNVERIFIED-1',
        ]);
    }

    /**
     * Test 12: Original incoming payment amounts remain immutable.
     */
    public function test_original_incoming_payment_amounts_remain_immutable(): void
    {
        [$order, $orderItem, $paymentTx] = $this->createOverpaidOrder(900.00, 1000.00, 'Paid', 'VERIFIED');

        $this->actingAs($this->admin)->post(route('admin.orders.refund-sukli', $order->id), [
            'refund_amount'        => 100.00,
            'transfer_reference'   => 'SUKLI-IMMUTABLE-1',
            'refund_mobile_number' => '09171234567',
        ]);

        // Assert payment transaction amount is still 1000.00 and order total is still 900.00
        $paymentTx->refresh();
        $order->refresh();

        $this->assertEquals(1000.00, (float) $paymentTx->detected_amount);
        $this->assertEquals(900.00, (float) $paymentTx->expected_amount);
        $this->assertEquals(900.00, (float) $order->totalAmount);
    }

    /**
     * Test 13: Multiple receipt transactions are accounted for correctly.
     */
    public function test_multiple_receipt_transactions_are_accounted_for_correctly(): void
    {
        // Order payable: 900
        // Receipt A: 850
        // Receipt B: 100
        // Total received: 950
        // Sukli: 50
        $order = Order::create([
            'customerId'          => $this->customer->id,
            'sellerId'            => $this->seller->id,
            'totalAmount'         => 900.00,
            'status'              => 'Pending',
            'paymentMethod'       => 'GCASH',
            'paymentStatus'       => 'Paid',
            'shippingAddress'     => '123 Lumban Heritage St, Laguna',
            'refundMobileNumber'  => Crypt::encryptString('09171234567'),
            'refundWallet'        => 'GCash',
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => 'MULTI-RECEIPT-A',
            'wallet_type'      => 'gcash',
            'expected_amount'  => 900.00,
            'detected_amount'  => 850.00,
            'status'           => 'VERIFIED',
            'verified_at'      => now(),
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => 'MULTI-RECEIPT-B',
            'wallet_type'      => 'gcash',
            'expected_amount'  => 50.00,
            'detected_amount'  => 100.00,
            'status'           => 'VERIFIED',
            'verified_at'      => now(),
        ]);

        $this->assertEquals(950.00, $order->totalReceivedPayments());
        $this->assertEquals(50.00, $order->authoritativeSukliAmount());
        $this->assertEquals(50.00, $order->remainingSukliRefundAmount());

        // Admin processes 50 sukli
        $response = $this->actingAs($this->admin)->post(route('admin.orders.refund-sukli', $order->id), [
            'refund_amount'        => 50.00,
            'transfer_reference'   => 'SUKLI-MULTI-FINAL',
            'refund_mobile_number' => '09171234567',
        ]);

        $response->assertRedirect();
        $this->assertEquals(0.00, $order->fresh()->remainingSukliRefundAmount());
        $this->assertEquals('REFUNDED', $order->fresh()->sukliRefundStatus());
    }

    /**
     * Test 14: Customer status changes only after appropriate administrative event.
     */
    public function test_customer_status_changes_only_after_appropriate_administrative_event(): void
    {
        [$order] = $this->createOverpaidOrder(900.00, 1000.00, 'Pending Verification', 'DETECTED');

        // Stage 1: Before verification
        $this->assertEquals('PENDING_VERIFICATION', $order->sukliRefundStatus());

        // Stage 2: Admin verifies payment
        $this->actingAs($this->admin)->post(route('admin.orders.verify-payment', $order->id));
        $order->refresh();
        $this->assertEquals('OVERPAYMENT_PENDING_REFUND', $order->sukliRefundStatus());

        // Customer order show page should show awaiting refund processing
        $showResponse = $this->actingAs($this->customer)->get(route('orders.show', $order->id));
        $showResponse->assertOk();
        $showResponse->assertSee('awaiting refund processing');

        // Stage 3: Admin executes refund transfer
        $this->actingAs($this->admin)->post(route('admin.orders.refund-sukli', $order->id), [
            'refund_amount'        => 100.00,
            'transfer_reference'   => 'SUKLI-LIFECYCLE-1',
            'refund_mobile_number' => '09171234567',
        ]);
        $order->refresh();
        $this->assertEquals('REFUNDED', $order->sukliRefundStatus());

        // Customer order show page should now show refund has been processed
        $showResponse2 = $this->actingAs($this->customer)->get(route('orders.show', $order->id));
        $showResponse2->assertOk();
        $showResponse2->assertSee('sukli refund has been processed');
        $showResponse2->assertSee('SUKLI-LIFECYCLE-1');
    }

    /**
     * Test 15: Order fulfillment lifecycle remains separate and unbroken.
     */
    public function test_order_fulfillment_lifecycle_remains_separate_and_unbroken(): void
    {
        [$order] = $this->createOverpaidOrder(900.00, 1000.00, 'Paid', 'VERIFIED');
        $order->update(['status' => 'In Transit']);

        $this->actingAs($this->admin)->post(route('admin.orders.refund-sukli', $order->id), [
            'refund_amount'        => 100.00,
            'transfer_reference'   => 'SUKLI-FULFILLMENT-1',
            'refund_mobile_number' => '09171234567',
        ]);

        $order->refresh();
        // Fulfillment status should remain 'In Transit', not mutated to Refunded or Cancelled
        $this->assertEquals('In Transit', $order->status);
        $this->assertEquals('REFUNDED', $order->sukliRefundStatus());
    }
}

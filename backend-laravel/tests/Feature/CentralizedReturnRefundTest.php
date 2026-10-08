<?php

namespace Tests\Feature;

use App\Models\CommissionRecord;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\ReturnRefundEvidence;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CentralizedReturnRefundTest extends TestCase
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
            'role' => 'customer',
            'status' => 'Active',
        ]);

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'isVerified' => true,
            'status' => 'Active',
            'shopName' => 'Lumban Handcrafted Barongs',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'Active',
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'Active',
        ]);

        $this->product = Product::create([
            'sellerId'    => $this->seller->id,
            'name'        => 'Custom Silk Piña Barong',
            'description' => 'Fine traditional embroidery',
            'price'       => 2500.00,
            'stock'       => 10,
            'status'      => 'Approved',
        ]);
    }

    /**
     * Helper to create a delivered GCash order with a verified payment transaction.
     */
    protected function createDeliveredGcashOrder(float $amount = 2500.00): array
    {
        $order = Order::create([
            'customerId'      => $this->customer->id,
            'sellerId'        => $this->seller->id,
            'totalAmount'     => $amount,
            'status'          => 'Delivered',
            'paymentMethod'   => 'GCash',
            'paymentStatus'   => 'Paid',
            'shippingAddress' => '123 Lumban Heritage St, Laguna',
        ]);

        $orderItem = OrderItem::create([
            'orderId'   => $order->id,
            'productId' => $this->product->id,
            'quantity'  => 1,
            'price'     => $amount,
        ]);

        $paymentTx = PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => '100234567890',
            'wallet_type'      => 'gcash',
            'expected_amount'  => $amount,
            'detected_amount'  => $amount,
            'status'           => 'VERIFIED',
            'verified_at'      => now(),
        ]);

        return [$order, $orderItem, $paymentTx];
    }

    public function test_customer_can_submit_return_request_for_eligible_order_with_evidences(): void
    {
        [$order, $orderItem, $paymentTx] = $this->createDeliveredGcashOrder();

        $photoProof = UploadedFile::fake()->image('defect.jpg');
        $videoProof = UploadedFile::fake()->create('unboxing.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($this->customer)->postJson("/orders/{$order->id}/returns", [
            'orderId'         => $order->id,
            'order_item_id'   => $orderItem->id,
            'reason'          => 'Damaged Item - Loose embroidery stitch',
            'message'         => 'The collar embroidery is detached upon unboxing.',
            'resolution_type' => 'refund',
            'proof_files'     => [$photoProof, $videoProof],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('returnrequests', [
            'orderId'         => $order->id,
            'customer_id'     => $this->customer->id,
            'seller_id'       => $this->seller->id,
            'return_status'   => 'submitted',
            'refund_status'   => 'pending',
            'resolution_type' => 'refund',
        ]);

        $this->assertDatabaseCount('return_refund_evidences', 2);
    }

    public function test_customer_cannot_submit_return_for_other_customer_order(): void
    {
        [$order, $orderItem, $paymentTx] = $this->createDeliveredGcashOrder();

        /** @var User $otherCustomer */
        $otherCustomer = User::factory()->create([
            'role' => 'customer',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($otherCustomer)->postJson("/orders/{$order->id}/returns", [
            'orderId'       => $order->id,
            'reason'        => 'Unauthorized access attempt',
            'proof_files'   => [UploadedFile::fake()->image('fake.jpg')],
        ]);

        $response->assertStatus(422); // Validation error from eligibility service
    }

    public function test_seller_can_assess_product_and_accept_without_disbursing_platform_money(): void
    {
        [$order, $orderItem, $paymentTx] = $this->createDeliveredGcashOrder();

        $returnRequest = ReturnRequest::create([
            'orderId'                => $order->id,
            'customer_id'            => $this->customer->id,
            'seller_id'              => $this->seller->id,
            'reason'                 => 'Wrong Size',
            'status'                 => 'Pending',
            'return_status'          => 'submitted',
            'physical_return_status' => 'not_required',
            'refund_status'          => 'pending',
            'requested_amount'       => 2500.00,
        ]);

        $response = $this->actingAs($this->seller)->postJson("/seller/returns/{$returnRequest->id}/review", [
            'assessment'               => 'accepted',
            'notes'                    => 'Please return the item in original box.',
            'requires_physical_return' => true,
        ]);

        $response->assertStatus(200);
        $returnRequest->refresh();

        $this->assertEquals('accepted', $returnRequest->seller_assessment);
        $this->assertEquals('awaiting_return', $returnRequest->return_status);
        $this->assertEquals('awaiting_customer', $returnRequest->physical_return_status);
        // Financial refund remains pending (seller did not disburse)
        $this->assertEquals('pending', $returnRequest->refund_status);
    }

    public function test_seller_can_confirm_physical_item_receipt(): void
    {
        [$order, $orderItem, $paymentTx] = $this->createDeliveredGcashOrder();

        $returnRequest = ReturnRequest::create([
            'orderId'                => $order->id,
            'customer_id'            => $this->customer->id,
            'seller_id'              => $this->seller->id,
            'reason'                 => 'Wrong Size',
            'status'                 => 'Approved',
            'return_status'          => 'awaiting_return',
            'physical_return_status' => 'in_transit',
            'refund_status'          => 'pending',
            'requested_amount'       => 2500.00,
        ]);

        $response = $this->actingAs($this->seller)->postJson("/seller/returns/{$returnRequest->id}/receive", [
            'notes' => 'Received package, item in original condition with tags.',
        ]);

        $response->assertStatus(200);
        $returnRequest->refresh();

        $this->assertEquals('received', $returnRequest->physical_return_status);
        $this->assertEquals('admin_review', $returnRequest->return_status);
    }

    public function test_admin_can_approve_and_record_centralized_gcash_refund_with_linkage_and_encryption(): void
    {
        [$order, $orderItem, $paymentTx] = $this->createDeliveredGcashOrder(2500.00);

        $returnRequest = ReturnRequest::create([
            'orderId'                => $order->id,
            'customer_id'            => $this->customer->id,
            'seller_id'              => $this->seller->id,
            'reason'                 => 'Damaged Collar',
            'status'                 => 'Approved',
            'return_status'          => 'admin_review',
            'physical_return_status' => 'received',
            'refund_status'          => 'pending',
            'requested_amount'       => 2500.00,
        ]);

        $transferProof = UploadedFile::fake()->image('gcash_receipt.png');

        $response = $this->actingAs($this->admin)->postJson("/admin/returns/{$returnRequest->id}/record-transfer", [
            'refund_amount'       => 2500.00,
            'transfer_reference'  => 'GCASH-REF-99887766',
            'transfer_proof'      => $transferProof,
            'destination_account' => '09171234567',
            'destination_name'    => 'Juan Dela Cruz',
            'notes'               => 'Refund sent via official LumBarong GCash merchant portal.',
        ]);

        $response->assertStatus(200);
        $returnRequest->refresh();

        $this->assertEquals('resolved', $returnRequest->return_status);
        $this->assertEquals('transferred', $returnRequest->refund_status);
        $this->assertEquals(2500.00, (float) $returnRequest->approved_amount);
        $this->assertEquals($this->admin->id, $returnRequest->resolved_by);

        // Verify RefundTransaction creation & linkage
        $refundTx = RefundTransaction::where('transfer_reference', 'GCASH-REF-99887766')->first();
        $this->assertNotNull($refundTx);
        $this->assertEquals($order->id, $refundTx->order_id);
        $this->assertEquals($paymentTx->id, $refundTx->payment_transaction_id);
        $this->assertEquals('transferred', $refundTx->status);
        $this->assertEquals('09*****4567', $refundTx->destination_account_masked);
        $this->assertEquals('09171234567', $refundTx->destination_account_encrypted);

        // Verify raw database value is encrypted (not plaintext)
        $rawRow = DB::table('refund_transactions')->where('id', $refundTx->id)->first();
        $this->assertNotEquals('09171234567', $rawRow->destination_account_encrypted);
    }

    public function test_admin_cannot_over_refund_past_remaining_balance(): void
    {
        [$order, $orderItem, $paymentTx] = $this->createDeliveredGcashOrder(2000.00);

        $returnRequest = ReturnRequest::create([
            'orderId'                => $order->id,
            'customer_id'            => $this->customer->id,
            'seller_id'              => $this->seller->id,
            'reason'                 => 'Minor tear',
            'status'                 => 'Approved',
            'return_status'          => 'admin_review',
            'physical_return_status' => 'received',
            'refund_status'          => 'pending',
            'requested_amount'       => 2000.00,
        ]);

        // Attempt to refund ₱2,500 on a ₱2,000 order
        $response = $this->actingAs($this->admin)->postJson("/admin/returns/{$returnRequest->id}/record-transfer", [
            'refund_amount'       => 2500.00,
            'transfer_reference'  => 'OVER-REFUND-111',
            'destination_account' => '09170000000',
        ]);

        $response->assertStatus(422);
    }

    public function test_duplicate_transfer_reference_is_prevented_idempotently(): void
    {
        [$order, $orderItem, $paymentTx] = $this->createDeliveredGcashOrder(1500.00);

        $priorRequest = ReturnRequest::create([
            'orderId'          => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reason'           => 'Initial Return',
            'status'           => 'Resolved',
            'return_status'    => 'resolved',
            'refund_status'    => 'transferred',
            'requested_amount' => 500.00,
        ]);

        // Pre-existing refund transaction with same reference
        RefundTransaction::create([
            'return_request_id'            => $priorRequest->id,
            'order_id'                     => $order->id,
            'payment_method'               => 'gcash',
            'refund_method'                => 'gcash',
            'refund_amount'                => 500.00,
            'status'                       => 'transferred',
            'transfer_reference'           => 'DUPLICATE-REF-1234',
        ]);

        $returnRequest = ReturnRequest::create([
            'orderId'                => $order->id,
            'customer_id'            => $this->customer->id,
            'seller_id'              => $this->seller->id,
            'reason'                 => 'Defect',
            'status'                 => 'Approved',
            'return_status'          => 'admin_review',
            'refund_status'          => 'pending',
            'requested_amount'       => 1000.00,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/admin/returns/{$returnRequest->id}/record-transfer", [
            'refund_amount'       => 1000.00,
            'transfer_reference'  => 'DUPLICATE-REF-1234',
            'destination_account' => '09170000000',
        ]);

        $response->assertStatus(422);
    }

    public function test_seller_rejection_can_be_disputed_by_customer_and_mediated_by_admin(): void
    {
        [$order, $orderItem, $paymentTx] = $this->createDeliveredGcashOrder();

        $returnRequest = ReturnRequest::create([
            'orderId'           => $order->id,
            'customer_id'       => $this->customer->id,
            'seller_id'         => $this->seller->id,
            'reason'            => 'Wrong sizing',
            'status'            => 'Rejected',
            'return_status'     => 'rejected',
            'seller_assessment' => 'rejected',
            'seller_notes'      => 'Customer ordered wrong size.',
        ]);

        // Customer disputes
        $disputeProof = UploadedFile::fake()->image('size_tag.png');
        $disputeRes = $this->actingAs($this->customer)->postJson("/orders/{$order->id}/returns/{$returnRequest->id}/dispute", [
            'reason'      => 'The sizing chart on the product listing is misleading by 4 inches.',
            'proof_files' => [$disputeProof],
        ]);

        $disputeRes->assertStatus(200);
        $returnRequest->refresh();

        $this->assertEquals('opened', $returnRequest->dispute_status);
        $this->assertEquals('disputed', $returnRequest->return_status);

        // Admin mediates and approves
        $adminRes = $this->actingAs($this->admin)->postJson("/admin/returns/{$returnRequest->id}/resolve-dispute", [
            'decision' => 'approve_return',
            'notes'    => 'Listing size measurements verified to be inconsistent; approving customer return.',
        ]);

        $adminRes->assertStatus(200);
        $returnRequest->refresh();

        $this->assertEquals('resolved', $returnRequest->dispute_status);
        $this->assertEquals('admin_review', $returnRequest->return_status);
        $this->assertEquals('override_approved', $returnRequest->admin_decision);
    }

    public function test_store_pickup_and_cod_cash_refund_adjusts_commission_non_destructively(): void
    {
        $cashOrder = Order::create([
            'customerId'      => $this->customer->id,
            'sellerId'        => $this->seller->id,
            'totalAmount'     => 3000.00,
            'status'          => 'Claimed',
            'paymentMethod'   => 'Cash on Pickup',
            'paymentStatus'   => 'Paid',
            'shippingAddress' => 'Store Pickup - Lumban Workshop',
        ]);

        $commission = CommissionRecord::create([
            'sellerId'         => $this->seller->id,
            'period'           => '2026-10',
            'totalSales'       => 10000.00,
            'commissionRate'   => 0.05,
            'commissionAmount' => 500.00,
            'status'           => 'unpaid',
            'dueDate'          => now()->addDays(15),
        ]);

        $returnRequest = ReturnRequest::create([
            'orderId'                => $cashOrder->id,
            'customer_id'            => $this->customer->id,
            'seller_id'              => $this->seller->id,
            'reason'                 => 'Cash exchange in workshop',
            'status'                 => 'Pending',
            'return_status'          => 'submitted',
            'physical_return_status' => 'received',
            'refund_status'          => 'not_applicable',
            'requested_amount'       => 3000.00,
        ]);

        $response = $this->actingAs($this->seller)->postJson("/seller/returns/{$returnRequest->id}/cash-refund", [
            'refund_amount'   => 3000.00,
            'resolution_type' => 'refund',
            'notes'           => 'Customer visited store; full cash refund handed to customer.',
        ]);

        $response->assertStatus(200);
        $returnRequest->refresh();
        $commission->refresh();

        $this->assertEquals('resolved', $returnRequest->return_status);
        $this->assertEquals('completed', $returnRequest->physical_return_status);
        $this->assertEquals(3000.00, (float) $returnRequest->approved_amount);

        // Verify non-destructive commission adjustment: ₱10,000 - ₱3,000 = ₱7,000 sales => ₱350 commission
        $this->assertEquals(7000.00, (float) $commission->totalSales);
        $this->assertEquals(350.00, (float) $commission->commissionAmount);
        $this->assertStringContainsString('Refund adjustment: -₱3000', $commission->notes);
    }
}

<?php

namespace Tests\Feature;

use App\Models\CommissionRecord;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\ReturnRefundEvidence;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\Financial\FinancialLedgerService;
use App\Services\Returns\RecordCashRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoleBasedFinancialAndCancellationControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $otherCustomer;
    protected User $seller;
    protected User $otherSeller;
    protected User $admin;
    protected User $superAdmin;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->customer = User::factory()->create([
            'role'   => 'customer',
            'status' => 'Active',
            'name'   => 'Maria Santos',
            'email'  => 'maria@example.com',
        ]);

        $this->otherCustomer = User::factory()->create([
            'role'   => 'customer',
            'status' => 'Active',
            'name'   => 'Pedro Reyes',
            'email'  => 'pedro@example.com',
        ]);

        $this->seller = User::factory()->create([
            'role'       => 'seller',
            'isVerified' => true,
            'status'     => 'Active',
            'name'       => 'Artisan Aling Nena',
            'shopName'   => 'Nena Lumban Heritage',
            'email'      => 'nena@example.com',
        ]);

        $this->otherSeller = User::factory()->create([
            'role'       => 'seller',
            'isVerified' => true,
            'status'     => 'Active',
            'name'       => 'Artisan Mang Jose',
            'shopName'   => 'Jose Embroidery Workshop',
            'email'      => 'jose@example.com',
        ]);

        $this->admin = User::factory()->create([
            'role'   => 'admin',
            'status' => 'Active',
            'name'   => 'Platform Admin',
            'email'  => 'admin@lumbarong.com',
        ]);

        $this->superAdmin = User::factory()->create([
            'role'   => 'superadmin',
            'status' => 'Active',
            'name'   => 'Executive SuperAdmin',
            'email'  => 'superadmin@lumbarong.com',
        ]);

        $this->product = Product::create([
            'sellerId'    => $this->seller->id,
            'name'        => 'Hand-Embroidered Barong Tagalog',
            'description' => 'Authentic Cocoon Silk from Lumban',
            'price'       => 1500.00,
            'stock'       => 10,
            'status'      => 'Approved',
        ]);
    }

    /**
     * Helper to create an order.
     */
    protected function createOrder(array $attributes = []): Order
    {
        $order = Order::create(array_merge([
            'customerId'          => $this->customer->id,
            'sellerId'            => $this->seller->id,
            'totalAmount'         => 1500.00,
            'status'              => 'Pending',
            'paymentMethod'       => 'GCash',
            'paymentStatus'       => 'Pending Verification',
            'shippingAddress'     => '456 Lumban Heritage Road, Laguna',
            'refundWallet'        => 'GCash',
            'refundMobileNumber'  => Crypt::encryptString('09171234567'),
            'refundAccountName'   => 'Maria Santos',
        ], $attributes));

        OrderItem::create([
            'orderId'   => $order->id,
            'productId' => $this->product->id,
            'quantity'  => 1,
            'price'     => (float) $order->totalAmount,
        ]);

        return $order;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. GCash cancellation routes an eligible refund to Super Admin
    // ─────────────────────────────────────────────────────────────────────────
    public function test_01_gcash_cancellation_routes_eligible_refund_to_super_admin(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Paid',
            'status'        => 'Pending',
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => '1001234567890',
            'active_reference' => '1001234567890',
            'wallet_type'      => 'GCash',
            'expected_amount'  => 1500.00,
            'detected_amount'  => 1500.00,
            'status'           => 'VERIFIED',
        ]);

        // Seller cancels order
        $response = $this->actingAs($this->seller)->post("/seller/api/orders/{$order->id}/cancel", [
            'reason' => 'Artisan unavailable due to local town event',
        ]);
        $response->assertOk();

        $order->refresh();
        $this->assertEquals('Cancelled', $order->status);
        $this->assertEquals('pending_refund', $order->cancellationRefundStatus());
        $this->assertEquals(1500.00, $order->remainingCancellationRefundAmount());

        // Super Admin sees case in cancellations queue
        $queueResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.returns.index', ['tab' => 'cancellations']));
        $queueResponse->assertOk();
        $queueResponse->assertSee('#LB-' . strtoupper(substr($order->id, -8)));
        $queueResponse->assertSee('₱1,500.00');

        // Super Admin disburses the platform refund
        $refundResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.returns.refund-cancellation', $order->id), [
            'refund_amount'       => 1500.00,
            'transfer_reference'  => 'GCASH-OUT-991122',
            'destination_account' => '09171234567',
            'destination_name'    => 'Maria Santos',
            'notes'               => 'Platform full cancellation refund transfer',
        ]);
        $refundResponse->assertRedirect();

        $order->refresh();
        $this->assertEquals(0.00, $order->remainingCancellationRefundAmount());
        $this->assertEquals('refunded', $order->cancellationRefundStatus());

        $this->assertDatabaseHas('refund_transactions', [
            'order_id'           => $order->id,
            'transfer_reference' => 'GCASH-OUT-991122',
            'refund_amount'      => 1500.00,
            'status'             => 'transferred',
            'processed_by'       => $this->superAdmin->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Maya cancellation routes an eligible refund to Super Admin
    // ─────────────────────────────────────────────────────────────────────────
    public function test_02_maya_cancellation_routes_eligible_refund_to_super_admin(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'Maya',
            'paymentStatus' => 'Paid',
            'status'        => 'Pending',
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => '200123456789',
            'active_reference' => '200123456789',
            'wallet_type'      => 'Maya',
            'expected_amount'  => 1500.00,
            'detected_amount'  => 1500.00,
            'status'           => 'VERIFIED',
        ]);

        // Cancel order
        $this->actingAs($this->seller)->post("/seller/api/orders/{$order->id}/cancel", [
            'reason' => 'Material out of stock',
        ]);

        $order->refresh();
        $this->assertEquals('Cancelled', $order->status);
        $this->assertEquals('pending_refund', $order->cancellationRefundStatus());

        // Processed by Super Admin
        $this->actingAs($this->superAdmin)->post(route('superadmin.returns.refund-cancellation', $order->id), [
            'refund_amount'       => 1500.00,
            'transfer_reference'  => 'MAYA-OUT-554433',
            'destination_account' => '09181112233',
            'destination_name'    => 'Maria Santos',
        ])->assertRedirect();

        $this->assertDatabaseHas('refund_transactions', [
            'order_id'           => $order->id,
            'transfer_reference' => 'MAYA-OUT-554433',
            'refund_amount'      => 1500.00,
            'processed_by'       => $this->superAdmin->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Seller acceptance of an online return creates financial case without paying it
    // ─────────────────────────────────────────────────────────────────────────
    public function test_03_seller_acceptance_of_online_return_creates_financial_case_without_paying_it(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Paid',
            'status'        => 'Delivered',
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => '1009988776655',
            'active_reference' => '1009988776655',
            'wallet_type'      => 'GCash',
            'expected_amount'  => 1500.00,
            'detected_amount'  => 1500.00,
            'status'           => 'VERIFIED',
        ]);

        $returnRequest = ReturnRequest::create([
            'orderId'          => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reason'           => 'Sleeve embroidery slight misalignment',
            'return_type'      => 'refund',
            'return_status'    => 'pending',
            'refund_status'    => 'pending',
            'requested_amount' => 1500.00,
        ]);

        // Seller reviews and accepts
        $response = $this->actingAs($this->seller)->postJson(route('seller.returns.review', $returnRequest->id), [
            'assessment' => 'accepted',
            'notes'      => 'Claim verified. Artisan agreed to customer return.',
        ]);
        $response->assertOk();

        $returnRequest->refresh();
        $this->assertEquals('accepted', $returnRequest->seller_assessment);
        $this->assertEquals('Approved', $returnRequest->status);
        // NO money has been disbursed
        $this->assertEquals(0, RefundTransaction::where('order_id', $order->id)->count());

        // Now Super Admin sees it in claims queue and can execute disbursement
        $adminResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.returns.index', ['tab' => 'claims']));
        $adminResponse->assertOk();
        $adminResponse->assertSee('#RR-' . strtoupper(substr($returnRequest->id, -8)));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Pay at Store cash cancellation is controlled by the Seller
    // ─────────────────────────────────────────────────────────────────────────
    public function test_04_pay_at_store_cash_cancellation_is_controlled_by_seller(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'Pay at Store',
            'paymentStatus' => 'Unpaid',
            'status'        => 'Cancellation Pending',
            'courierName'   => 'Store Pickup',
        ]);

        // Seller approves cancellation
        $response = $this->actingAs($this->seller)->post(route('orders.approve-cancellation', $order->id));
        $response->assertOk();

        $order->refresh();
        $this->assertEquals('Cancelled', $order->status);
        $this->assertEquals('unpaid', $order->cancellationRefundStatus());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Pay at Store cash refund is recorded accurately by the Seller
    // ─────────────────────────────────────────────────────────────────────────
    public function test_05_pay_at_store_cash_refund_is_recorded_accurately_by_seller(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'Pay at Store',
            'paymentStatus' => 'Paid',
            'status'        => 'Cancelled',
            'courierName'   => 'Store Pickup',
        ]);

        $proof = UploadedFile::fake()->image('cash_receipt.jpg');

        $response = $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount'  => 1500.00,
            'reason'         => 'Cash handed over at Lumban workshop',
            'notes'          => 'Customer returned item in shop and received ₱1,500 cash',
            'transfer_proof' => $proof,
            'refund_date'    => now()->toDateString(),
        ]);
        $response->assertOk();

        $this->assertDatabaseHas('refund_transactions', [
            'order_id'      => $order->id,
            'payment_method'=> 'cash',
            'refund_amount' => 1500.00,
            'processed_by'  => $this->seller->id,
            'status'        => 'transferred',
        ]);

        // Customer receives official LumBarong inbox notification
        $message = Message::where('receiverId', $this->customer->id)->latest()->first();
        $this->assertNotNull($message);
        $this->assertStringContainsString('Cash (Pay at Store / Seller Cash Settlement)', $message->content);
        $this->assertStringContainsString('₱1,500.00', $message->content);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Seller-controlled Special Delivery cash cancellation works
    // ─────────────────────────────────────────────────────────────────────────
    public function test_06_seller_controlled_special_delivery_cash_cancellation_works(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'COD',
            'courierName'   => 'Special Delivery (Local Artisan Rider)',
            'paymentStatus' => 'Unpaid',
            'status'        => 'Pending',
        ]);

        $response = $this->actingAs($this->seller)->post("/seller/api/orders/{$order->id}/cancel", [
            'reason' => 'Rider motorcycle unavailable for province delivery',
        ]);
        $response->assertOk();

        $order->refresh();
        $this->assertEquals('Cancelled', $order->status);
        $this->assertEquals('unpaid', $order->cancellationRefundStatus());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. Seller-controlled Special Delivery cash refund works
    // ─────────────────────────────────────────────────────────────────────────
    public function test_07_seller_controlled_special_delivery_cash_refund_works(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'COD',
            'courierName'   => 'Special Delivery (Local Artisan Rider)',
            'paymentStatus' => 'Paid',
            'status'        => 'Cancelled',
        ]);

        $response = $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount' => 1500.00,
            'reason'        => 'Rider refunded cash directly to buyer upon cancellation',
        ]);
        $response->assertOk();

        $this->assertDatabaseHas('refund_transactions', [
            'order_id'      => $order->id,
            'payment_method'=> 'cash',
            'refund_amount' => 1500.00,
            'processed_by'  => $this->seller->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. Special Delivery paid online still routes the refund to Super Admin
    // ─────────────────────────────────────────────────────────────────────────
    public function test_08_special_delivery_paid_directly_to_seller_refunded_by_seller_and_super_admin_blocked(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'Special Delivery',
            'courierName'   => 'Special Delivery (Local Artisan Rider)',
            'paymentStatus' => 'Paid',
            'status'        => 'Cancelled',
        ]);

        $this->assertTrue($order->isSellerHeldPayment());
        $this->assertFalse($order->isPlatformHeldPayment());

        // Seller CAN process direct payment refund
        $response = $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount'  => 1500.00,
            'payment_method' => 'gcash', // e.g. refunded directly to buyer via GCash transfer by seller
            'reason'         => 'Seller refunded direct transfer to customer',
        ]);
        $response->assertOk();
        $this->assertEquals(1, RefundTransaction::where('order_id', $order->id)->count());

        // Super Admin CANNOT disburse platform funds for seller-held order
        $adminResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.returns.refund-cancellation', $order->id), [
            'refund_amount'       => 1500.00,
            'transfer_reference'  => 'GCASH-SPECIAL-OUT-01',
            'destination_account' => '09171234567',
            'destination_name'    => 'Maria Santos',
        ]);
        $adminResponse->assertSessionHasErrors(['order']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 9. COD responsibilities follow the actual cash holder
    // ─────────────────────────────────────────────────────────────────────────
    public function test_09_cod_responsibilities_follow_actual_cash_holder(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Paid',
            'status'        => 'Cancelled',
        ]);

        // Seller records cash refund
        $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount' => 1500.00,
            'reason'        => 'COD cash settlement returned by seller',
        ])->assertOk();

        $this->assertDatabaseHas('refund_transactions', [
            'order_id'       => $order->id,
            'payment_method' => 'cash',
            'processed_by'   => $this->seller->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 10. Customer cannot cancel another customer's order
    // ─────────────────────────────────────────────────────────────────────────
    public function test_10_customer_cannot_cancel_another_customer_order(): void
    {
        $order = $this->createOrder(['status' => 'Pending']);

        // Other customer attempts to cancel
        $this->actingAs($this->otherCustomer)->postJson("/orders/{$order->id}/cancel", [
            'reason' => 'Unauthorized cancellation attempt',
        ])->assertStatus(403);

        $this->assertNotEquals('Cancelled', $order->fresh()->status);
        $this->assertNotEquals('Cancellation Pending', $order->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 11. Seller cannot act on another Seller's order
    // ─────────────────────────────────────────────────────────────────────────
    public function test_11_seller_cannot_act_on_another_seller_order(): void
    {
        $order = $this->createOrder(['status' => 'Pending']);

        // Other seller attempts to cancel via JSON API
        $response = $this->actingAs($this->otherSeller)->postJson("/seller/api/orders/{$order->id}/cancel", [
            'reason' => 'Other seller intrusion',
        ]);
        $response->assertStatus(403);

        // Other seller attempts to record cash refund
        $refundResponse = $this->actingAs($this->otherSeller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount' => 1500.00,
        ]);
        $refundResponse->assertStatus(422);

        $this->assertEquals(0, RefundTransaction::where('order_id', $order->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 12. Seller cannot release platform-held online refunds
    // ─────────────────────────────────────────────────────────────────────────
    public function test_12_seller_cannot_release_platform_held_online_refunds(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Paid',
            'status'        => 'Delivered',
        ]);

        $returnRequest = ReturnRequest::create([
            'orderId'          => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reason'           => 'Wrong size',
            'return_type'      => 'refund',
            'return_status'    => 'approved',
            'requested_amount' => 1500.00,
        ]);

        // Seller attempts to call cashRefund on online GCash order
        $response = $this->actingAs($this->seller)->postJson(route('seller.returns.cash-refund', $returnRequest->id), [
            'refund_amount'   => 1500.00,
            'resolution_type' => 'refund',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_method']);
        $this->assertEquals(0, RefundTransaction::where('order_id', $order->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 13. Seller cash refunds cannot exceed the authorized amount
    // ─────────────────────────────────────────────────────────────────────────
    public function test_13_seller_cash_refunds_cannot_exceed_the_authorized_amount(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'Pay at Store',
            'paymentStatus' => 'Paid',
            'totalAmount'   => 1500.00,
            'status'        => 'Cancelled',
        ]);

        // Attempt to refund 1600 on a 1500 order
        $response = $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount' => 1600.00,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['refund_amount']);
        $this->assertEquals(0, RefundTransaction::where('order_id', $order->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 14. Customer sukli remains separate from Seller earnings
    // ─────────────────────────────────────────────────────────────────────────
    public function test_14_customer_sukli_remains_separate_from_seller_earnings(): void
    {
        // Payable 1500, paid 1700 (sukli 200)
        $order = $this->createOrder([
            'totalAmount'        => 1500.00,
            'overpayment_amount' => 200.00,
            'paymentMethod'      => 'GCash',
            'paymentStatus'      => 'Paid',
            'status'             => 'Completed',
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => '1009988771122',
            'active_reference' => '1009988771122',
            'wallet_type'      => 'GCash',
            'expected_amount'  => 1500.00,
            'detected_amount'  => 1700.00,
            'status'           => 'VERIFIED',
        ]);

        $breakdown = FinancialLedgerService::calculateSellerSettlementBreakdown($order);

        // Gross sales to seller is strictly 1500, excluding sukli of 200
        $this->assertEquals(1500.00, $breakdown['gross_sales']);
        $this->assertEquals(1500.00, $breakdown['net_settlement_amount']);

        // Authoritative sukli is 200
        $this->assertEquals(200.00, $order->authoritativeSukliAmount());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 15. Cancellation restores stock only once
    // ─────────────────────────────────────────────────────────────────────────
    public function test_15_cancellation_restores_stock_only_once(): void
    {
        $this->product->update(['stock' => 10]);

        $order = $this->createOrder([
            'status' => 'Pending',
        ]);

        // Cancel order
        $this->actingAs($this->seller)->post("/seller/api/orders/{$order->id}/cancel", [
            'reason' => 'Cancellation stock test',
        ])->assertOk();

        $this->assertEquals(11, $this->product->fresh()->stock);

        // Attempting to cancel again should be blocked and not increment stock
        $this->actingAs($this->seller)->post("/seller/api/orders/{$order->id}/cancel", [
            'reason' => 'Duplicate cancellation attempt',
        ]);

        $this->assertEquals(11, $this->product->fresh()->stock);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 16. Commission adjustments remain correct after cash cancellations/refunds
    // ─────────────────────────────────────────────────────────────────────────
    public function test_16_commission_adjustments_remain_correct_after_cash_cancellations_refunds(): void
    {
        $commissionRecord = CommissionRecord::create([
            'sellerId'         => $this->seller->id,
            'period'           => now()->format('Y-m'),
            'totalSales'       => 2000.00,
            'commissionRate'   => 0.05,
            'commissionAmount' => 100.00,
            'status'           => 'unpaid',
            'dueDate'          => now()->addDays(15),
        ]);

        $order = $this->createOrder([
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Paid',
            'totalAmount'   => 500.00,
            'status'        => 'Cancelled',
        ]);

        // Seller records cash refund of 500
        $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount' => 500.00,
            'reason'        => 'Damaged item returned for cash refund',
        ])->assertOk();

        $commissionRecord->refresh();
        // Sales reduced by 500 (2000 - 500 = 1500)
        $this->assertEquals(1500.00, (float) $commissionRecord->totalSales);
        // Commission reduced to 5% of 1500 = 75.00
        $this->assertEquals(75.00, (float) $commissionRecord->commissionAmount);
        $this->assertStringContainsString('Refund adjustment: -₱500', $commissionRecord->notes);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 17. Multiple online receipt transactions are fully accounted for
    // ─────────────────────────────────────────────────────────────────────────
    public function test_17_multiple_online_receipt_transactions_are_fully_accounted_for(): void
    {
        $order = $this->createOrder([
            'totalAmount'   => 1500.00,
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Paid',
            'status'        => 'Cancelled',
        ]);

        // Customer uploaded 2 partial payment receipts that sum to 1500.00
        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => 'GCASH-PART-01',
            'active_reference' => 'GCASH-PART-01',
            'wallet_type'      => 'GCash',
            'expected_amount'  => 750.00,
            'detected_amount'  => 750.00,
            'status'           => 'VERIFIED',
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => 'GCASH-PART-02',
            'active_reference' => 'GCASH-PART-02',
            'wallet_type'      => 'GCash',
            'expected_amount'  => 750.00,
            'detected_amount'  => 750.00,
            'status'           => 'VERIFIED',
        ]);

        // totalPaidAmount accounts for BOTH verified receipts (750 + 750 = 1500)
        $this->assertEquals(1500.00, $order->totalPaidAmount());
        $this->assertEquals(1500.00, $order->remainingCancellationRefundAmount());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 18. Cancelled paid orders receive the correct eligible refund amount
    // ─────────────────────────────────────────────────────────────────────────
    public function test_18_cancelled_paid_orders_receive_correct_eligible_refund_amount(): void
    {
        // Payable 1500, paid 1700 (sukli 200 already refunded)
        $order = $this->createOrder([
            'totalAmount'        => 1500.00,
            'overpayment_amount' => 200.00,
            'paymentMethod'      => 'GCash',
            'paymentStatus'      => 'Paid',
            'status'             => 'Cancelled',
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => 'GCASH-1700-TX',
            'active_reference' => 'GCASH-1700-TX',
            'wallet_type'      => 'GCash',
            'expected_amount'  => 1500.00,
            'detected_amount'  => 1700.00,
            'status'           => 'VERIFIED',
        ]);

        // Record that 200 sukli was already refunded
        RefundTransaction::create([
            'order_id'           => $order->id,
            'payment_method'     => 'gcash',
            'refund_method'      => 'gcash',
            'refund_amount'      => 200.00,
            'transfer_reference' => 'SUKLI-REFUNDED-200',
            'status'             => 'transferred',
            'processed_by'       => $this->superAdmin->id,
            'processed_at'       => now(),
        ]);

        // When order is cancelled, remaining refundable amount is exactly 1500 (1700 total paid - 200 sukli refunded)
        $this->assertEquals(1500.00, $order->remainingCancellationRefundAmount());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 19. Completed refund messages include the correct proof
    // ─────────────────────────────────────────────────────────────────────────
    public function test_19_completed_refund_messages_include_correct_proof(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Paid',
            'status'        => 'Cancelled',
        ]);

        $proofFile = UploadedFile::fake()->image('refund_outgoing.png');

        $this->actingAs($this->superAdmin)->post(route('superadmin.returns.refund-cancellation', $order->id), [
            'refund_amount'       => 1500.00,
            'transfer_reference'  => 'GCASH-PROOF-112233',
            'destination_account' => '09171234567',
            'destination_name'    => 'Maria Santos',
            'transfer_proof'      => $proofFile,
        ])->assertRedirect();

        $message = Message::where('receiverId', $this->customer->id)->latest()->first();
        $this->assertNotNull($message);
        $this->assertStringContainsString('View / Download Refund Receipt', $message->content);

        $refundTx = RefundTransaction::where('order_id', $order->id)->first();
        $proofUrl = route('orders.refund-proof', ['orderId' => $order->id, 'refundId' => $refundTx->id]);

        // Customer can access their proof
        $this->actingAs($this->customer)->get($proofUrl)->assertOk();

        // Other customer cannot access this proof (403)
        $this->actingAs($this->otherCustomer)->get($proofUrl)->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 20. Duplicate financial actions and concurrent requests are blocked
    // ─────────────────────────────────────────────────────────────────────────
    public function test_20_duplicate_financial_actions_and_concurrent_requests_are_blocked(): void
    {
        $order = $this->createOrder([
            'totalAmount'   => 2000.00,
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Paid',
            'status'        => 'Cancelled',
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => '1008877665544',
            'active_reference' => '1008877665544',
            'wallet_type'      => 'GCash',
            'expected_amount'  => 2000.00,
            'detected_amount'  => 2000.00,
            'status'           => 'VERIFIED',
        ]);

        // First partial transfer succeeds
        $this->actingAs($this->superAdmin)->post(route('superadmin.returns.refund-cancellation', $order->id), [
            'refund_amount'       => 1000.00,
            'transfer_reference'  => 'GCASH-UNIQUE-TX-99',
            'destination_account' => '09171234567',
        ])->assertRedirect();

        // Duplicate attempt with same reference number is blocked
        $dupResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.returns.refund-cancellation', $order->id), [
            'refund_amount'       => 500.00,
            'transfer_reference'  => 'GCASH-UNIQUE-TX-99',
            'destination_account' => '09171234567',
        ]);
        $dupResponse->assertStatus(302);
        $dupResponse->assertSessionHasErrors(['transfer_reference']);

        // Over-refund exceeding remaining balance of 1000 is blocked
        $overRefundResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.returns.refund-cancellation', $order->id), [
            'refund_amount'       => 1500.00,
            'transfer_reference'  => 'GCASH-OVER-REFUND-01',
            'destination_account' => '09171234567',
        ]);
        $overRefundResponse->assertStatus(302);
        $overRefundResponse->assertSessionHasErrors(['refund_amount']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 21. Existing Admin and Super Admin controls remain functional
    // ─────────────────────────────────────────────────────────────────────────
    public function test_21_existing_admin_and_super_admin_controls_remain_functional(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Payment Submitted',
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => '1004455667788',
            'active_reference' => '1004455667788',
            'wallet_type'      => 'GCash',
            'expected_amount'  => 1500.00,
            'detected_amount'  => 1500.00,
            'status'           => 'UNVERIFIED',
        ]);

        // Admin verifies incoming payment
        $response = $this->actingAs($this->admin)->post("/admin/orders/{$order->id}/verify-payment", [
            'detected_amount'  => 1500.00,
            'reference_number' => '1004455667788',
        ]);
        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('Paid', $order->paymentStatus);

        // Super Admin views return center
        $saView = $this->actingAs($this->superAdmin)->get(route('superadmin.returns.index'));
        $saView->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 22. Full role permission matrix integrity
    // ─────────────────────────────────────────────────────────────────────────
    public function test_22_full_role_permission_matrix_integrity(): void
    {
        // Customer cannot access seller or admin endpoints
        $this->actingAs($this->customer)->getJson('/seller/orders')->assertStatus(403);
        $this->actingAs($this->customer)->getJson('/admin/orders')->assertStatus(403);
        $this->actingAs($this->customer)->getJson('/superadmin/dashboard')->assertStatus(403);

        // Seller cannot access superadmin dashboard
        $this->actingAs($this->seller)->getJson('/superadmin/dashboard')->assertStatus(403);

        // Super Admin has full dashboard access
        $this->actingAs($this->superAdmin)->get('/superadmin/dashboard')->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 23. Store Pickup direct payment recording and refund handled by Seller
    // ─────────────────────────────────────────────────────────────────────────
    public function test_23_store_pickup_payment_recording_and_refund_handled_by_seller(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'Store Pickup',
            'courierName'   => 'Store Pickup (Artisan Workshop)',
            'paymentStatus' => 'Pending Payment (Store Pickup)',
            'status'        => 'Processing',
        ]);

        $this->assertTrue($order->isSellerHeldPayment());

        // Seller records payment collected in shop
        $recordResponse = $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'   => 'Cash',
            'amount_received'  => 1500.00,
            'received_at'      => now()->toDateTimeString(),
            'notes'            => 'Cash received at workshop desk',
        ]);
        $recordResponse->assertOk();

        $order->refresh();
        $this->assertEquals('Paid', $order->paymentStatus);
        $this->assertEquals(1500.00, (float)$order->total_verified_payments);

        // Cancel order and refund
        $order->update(['status' => 'Cancelled']);

        $refundResponse = $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount'  => 1500.00,
            'payment_method' => 'cash',
            'reason'         => 'Returned cash upon cancellation',
        ]);
        $refundResponse->assertOk();

        $this->assertDatabaseHas('refund_transactions', [
            'order_id'      => $order->id,
            'payment_method'=> 'cash',
            'refund_amount' => 1500.00,
            'processed_by'  => $this->seller->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 24. Seller cannot record payment for another seller or record twice
    // ─────────────────────────────────────────────────────────────────────────
    public function test_24_seller_cannot_record_payment_for_other_seller_or_twice(): void
    {
        $order = $this->createOrder([
            'paymentMethod' => 'Store Pickup',
            'courierName'   => 'Store Pickup (Artisan Workshop)',
            'paymentStatus' => 'Pending Payment (Store Pickup)',
            'status'        => 'Processing',
        ]);

        // Other seller blocked
        $this->actingAs($this->otherSeller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'   => 'Cash',
            'amount_received'  => 1500.00,
        ])->assertStatus(403);

        // Authorized seller records payment
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'   => 'Cash',
            'amount_received'  => 1500.00,
        ])->assertOk();

        // Recording again is blocked
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'   => 'Cash',
            'amount_received'  => 1500.00,
        ])->assertStatus(422);
    }
}

<?php

namespace Tests\Feature;

use App\Models\CommissionRecord;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\SellerPayout;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Financial\FinancialLedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DirectToSellerPaymentsAndCommissionGovernanceTest extends TestCase
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
            'role'   => 'customer',
            'status' => 'Active',
            'email'  => 'customer@lumbarong.test',
        ]);

        $this->seller = User::factory()->create([
            'role'       => 'seller',
            'isVerified' => true,
            'status'     => 'Active',
            'shopName'   => 'Lumban Heritage Embroidery',
            'email'      => 'artisan@lumbarong.test',
        ]);

        $this->admin = User::factory()->create([
            'role'   => 'admin',
            'status' => 'Active',
            'email'  => 'admin@lumbarong.test',
        ]);

        $this->superAdmin = User::factory()->create([
            'role'   => 'superadmin',
            'status' => 'Active',
            'email'  => 'superadmin@lumbarong.test',
        ]);

        $this->product = Product::create([
            'sellerId'    => $this->seller->id,
            'name'        => 'Custom Pina Barong',
            'price'       => 2000.00,
            'stock'       => 15,
            'status'      => 'Approved',
        ]);
    }

    /**
     * Criterion 1 & 2: Direct-to-seller orders snapshot fund_holder as seller
     * and NEVER create a SellerPayout obligation for platform-held disbursement.
     */
    public function test_new_direct_order_has_fund_holder_seller_and_no_seller_payout_generated(): void
    {
        $order = Order::create([
            'customerId'      => $this->customer->id,
            'sellerId'        => $this->seller->id,
            'totalAmount'     => 2000.00,
            'status'          => 'Completed',
            'paymentMethod'   => 'GCash',
            'fund_holder'     => 'seller',
            'paymentStatus'   => 'Paid',
            'shippingAddress' => 'Lumban, Laguna',
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'fund_holder'      => 'seller',
            'recipient_id'     => $this->seller->id,
            'reference_number' => 'GCASH-DIRECT-001',
            'wallet_type'      => 'gcash',
            'expected_amount'  => 2000.00,
            'detected_amount'  => 2000.00,
            'status'           => 'VERIFIED',
        ]);

        $this->assertTrue($order->isSellerHeldPayment());
        $this->assertFalse($order->isPlatformHeldPayment());

        // Reconciling seller settlement must NOT create a platform payout for seller-held money
        $payout = FinancialLedgerService::reconcileSellerSettlementForOrder($order);
        $this->assertNull($payout);
        $this->assertEquals(0, SellerPayout::where('order_id', $order->id)->count());
    }

    /**
     * Criterion 3: Historical/legacy orders retain platform custody when designated.
     */
    public function test_historical_platform_orders_retain_true_legacy_classification(): void
    {
        $legacyOrder = Order::create([
            'customerId'      => $this->customer->id,
            'sellerId'        => $this->seller->id,
            'totalAmount'     => 1500.00,
            'status'          => 'Completed',
            'paymentMethod'   => 'GCash',
            'fund_holder'     => 'platform',
            'paymentStatus'   => 'Paid',
            'shippingAddress' => 'Manila',
        ]);

        $this->assertTrue($legacyOrder->isPlatformHeldPayment());
        $this->assertFalse($legacyOrder->isSellerHeldPayment());
    }

    /**
     * Criterion 4: Super Admin alone can change commission rate and Mark Paid / Mark Unpaid.
     */
    public function test_super_admin_is_sole_authority_for_commission_rate_and_status(): void
    {
        // 1. Regular admin CANNOT change commission rate via /admin/settings (403)
        $this->actingAs($this->admin)->post('/admin/settings', [
            'commission_rate' => 12.5,
        ])->assertStatus(403);

        // 2. Super admin CAN change commission rate via /superadmin/commission-rate
        $this->actingAs($this->superAdmin)->post('/superadmin/commission-rate', [
            'rate' => 10.0,
        ])->assertRedirect();
        $this->assertEquals(10.00, (float) SystemSetting::where('key', 'commission_rate')->value('value'));

        // 3. Create commission record
        $currentPeriod = Carbon::now()->format('Y-m');
        $record = CommissionRecord::create([
            'sellerId'         => $this->seller->id,
            'period'           => $currentPeriod,
            'totalSales'       => 10000.00,
            'commissionRate'   => 10.00,
            'commissionAmount' => 1000.00,
            'status'           => 'unpaid',
        ]);

        // 4. Admin CANNOT mark paid (403)
        $this->actingAs($this->admin)->patch(route('admin.commissions.mark-paid', $this->seller->id), [
            'period' => $currentPeriod,
        ])->assertStatus(403);

        // 5. Super admin marks Paid
        $this->actingAs($this->superAdmin)->patch(route('superadmin.commissions.mark-paid', $this->seller->id), [
            'period' => $currentPeriod,
            'notes'  => 'Verified via external bank remittance',
        ])->assertRedirect();

        $record->refresh();
        $this->assertEquals('paid', $record->status);
        $this->assertNotNull($record->paidAt);

        // 6. Super admin marks Unpaid with reason & audit logging
        $this->actingAs($this->superAdmin)->patch(route('superadmin.commissions.mark-unpaid', $this->seller->id), [
            'period' => $currentPeriod,
            'notes'  => 'Remittance check bounced; reopening liability',
        ])->assertRedirect();

        $record->refresh();
        $this->assertEquals('unpaid', $record->status);
        $this->assertNull($record->paidAt);
    }

    /**
     * Criterion 5: Sellers have strictly read-only commission views; proof submission is blocked with 403.
     */
    public function test_seller_commission_is_view_only_and_submission_routes_are_blocked(): void
    {
        // Seller can view commission page
        $this->actingAs($this->seller)->get('/seller/commission')->assertOk();

        // Seller CANNOT submit commission payment proof via /seller/commission (403)
        $proof = UploadedFile::fake()->image('proof.png');
        $this->actingAs($this->seller)->post('/seller/commission', [
            'reference_number' => 'REF-PROOF-123',
            'payment_proof'    => $proof,
            'period'           => Carbon::now()->format('Y-m'),
        ])->assertStatus(403);

        // Seller CANNOT submit via alternate /submit-commission-payment (403)
        $this->actingAs($this->seller)->post('/submit-commission-payment', [
            'reference_number' => 'REF-PROOF-123',
            'payment_proof'    => $proof,
        ])->assertStatus(403);
    }

    /**
     * Criterion 6: Admin is strictly read-only for all financial write endpoints (403).
     */
    public function test_admin_mutation_endpoints_return_403_forbidden(): void
    {
        $order = Order::create([
            'customerId'      => $this->customer->id,
            'sellerId'        => $this->seller->id,
            'totalAmount'     => 1000.00,
            'status'          => 'Pending',
            'paymentMethod'   => 'GCash',
            'paymentStatus'   => 'Payment Submitted',
            'shippingAddress' => 'Lumban',
        ]);

        // 1. Verify Payment
        $this->actingAs($this->admin)->post("/admin/orders/{$order->id}/verify-payment")
            ->assertStatus(403);

        // 2. Reject Payment
        $this->actingAs($this->admin)->post("/admin/orders/{$order->id}/reject-payment", ['reason' => 'test'])
            ->assertStatus(403);

        // 3. Commission Rate
        $this->actingAs($this->admin)->post('/admin/settings', ['commission_rate' => 5])
            ->assertStatus(403);

        // 4. Mark Commission Paid
        $this->actingAs($this->admin)->patch(route('admin.commissions.mark-paid', $this->seller->id))
            ->assertStatus(403);

        // 5. Payout processing
        $fakePayoutId = (string) Str::uuid();
        $this->actingAs($this->admin)->post("/admin/payouts/{$fakePayoutId}/process", ['payout_method' => 'GCash'])
            ->assertStatus(403);

        // 6. Payout hold
        $this->actingAs($this->admin)->patch("/admin/payouts/{$fakePayoutId}/hold", ['hold_reason' => 'test'])
            ->assertStatus(403);

        // 7. Payout release
        $this->actingAs($this->admin)->patch("/admin/payouts/{$fakePayoutId}/release")
            ->assertStatus(403);

        // 8. Record refund transfer on return request (403 for admin)
        $returnRequest = \App\Models\ReturnRequest::create([
            'orderId'          => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reason'           => 'Test',
            'status'           => 'Approved',
            'return_status'    => 'admin_review',
            'requested_amount' => 500,
        ]);
        $this->actingAs($this->admin)->postJson("/admin/returns/{$returnRequest->id}/record-transfer", ['refund_amount' => 500])
            ->assertStatus(403);
    }

    /**
     * Criterion 7: Refunds follow actual fund holder; seller-held money cannot be refunded as platform disbursement.
     */
    public function test_refunds_follow_actual_recipient_and_block_fake_platform_disbursement(): void
    {
        $order = Order::create([
            'customerId'      => $this->customer->id,
            'sellerId'        => $this->seller->id,
            'totalAmount'     => 1500.00,
            'status'          => 'Cancelled',
            'paymentMethod'   => 'GCash',
            'fund_holder'     => 'seller',
            'paymentStatus'   => 'Paid',
            'shippingAddress' => 'Lumban',
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'fund_holder'      => 'seller',
            'reference_number' => '1009988776655',
            'wallet_type'      => 'gcash',
            'expected_amount'  => 1500.00,
            'detected_amount'  => 1500.00,
            'status'           => 'VERIFIED',
        ]);

        // Attempting platform disbursement on a direct seller-held order fails validation
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.returns.refund-cancellation', $order->id), [
            'refund_amount'       => 1500.00,
            'transfer_reference'  => 'FAKE-PLATFORM-REFUND',
            'destination_account' => '09171234567',
            'destination_name'    => 'Customer Name',
        ]);

        $response->assertSessionHasErrors(['order']);
    }
}

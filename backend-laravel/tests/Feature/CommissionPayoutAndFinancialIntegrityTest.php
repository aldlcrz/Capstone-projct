<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\SellerPayout;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Financial\FinancialLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommissionPayoutAndFinancialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $admin;
    protected User $seller;
    protected User $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        SystemSetting::updateOrCreate(['key' => 'commission_rate'], ['value' => '5']);

        $this->superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin@lumbarong.test',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@lumbarong.test',
            'status' => 'active',
        ]);

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'email' => 'seller@lumbarong.test',
            'status' => 'active',
            'isVerified' => true,
            'gcashNumber' => '09171234567',
            'mayaNumber' => '09181234567',
            'shopName' => 'Lumban Fine Embroidery',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email' => 'customer@lumbarong.test',
            'status' => 'active',
            'mobileNumber' => '09191234567',
        ]);

        $this->product = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Classic Hand-Embroidered Barong Tagalog',
            'description' => 'Fine piña organza fabric',
            'price' => 1000.00,
            'stock' => 50,
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function cash_sale_calculates_configured_commission_excluding_shipping()
    {
        $order = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1150.00, // 1000 product + 150 shipping
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Verified',
            'status' => 'Completed',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);

        OrderItem::create([
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'quantity' => 1,
            'price' => 1000.00,
        ]);

        // Base is 1000 product price, 5% rate = 50.00 commission
        $commissionable = FinancialLedgerService::calculateCommissionableSales($order);
        $this->assertEquals(1000.00, $commissionable);

        $breakdown = FinancialLedgerService::calculateSellerSettlementBreakdown($order);
        $this->assertEquals(50.00, $breakdown['commission_deducted']);
        $this->assertEquals(5.0, $breakdown['commission_rate']);
    }

    /** @test */
    public function online_gcash_and_maya_orders_incur_zero_platform_commission_deduction()
    {
        // 1. GCash Order
        $gcashOrder = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 2150.00, // 2000 product + 150 shipping
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Verified',
            'status' => 'Completed',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);

        OrderItem::create([
            'orderId' => $gcashOrder->id,
            'productId' => $this->product->id,
            'quantity' => 2,
            'price' => 1000.00,
        ]);

        $gcashBreakdown = FinancialLedgerService::calculateSellerSettlementBreakdown($gcashOrder);
        $this->assertEquals(2000.00, $gcashBreakdown['gross_sales']);
        $this->assertEquals(150.00, $gcashBreakdown['shipping_amount']);
        $this->assertEquals(0.00, $gcashBreakdown['commission_deducted']);
        $this->assertEquals(2150.00, $gcashBreakdown['net_settlement_amount']);

        // 2. Maya Order
        $mayaOrder = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1100.00, // 1000 product + 100 shipping
            'paymentMethod' => 'Maya',
            'paymentStatus' => 'Verified',
            'status' => 'Completed',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);

        OrderItem::create([
            'orderId' => $mayaOrder->id,
            'productId' => $this->product->id,
            'quantity' => 1,
            'price' => 1000.00,
        ]);

        $mayaBreakdown = FinancialLedgerService::calculateSellerSettlementBreakdown($mayaOrder);
        $this->assertEquals(0.00, $mayaBreakdown['commission_deducted']);
        $this->assertEquals(1100.00, $mayaBreakdown['net_settlement_amount']);
    }

    /** @test */
    public function completed_verified_online_order_creates_settlement_available_for_payout()
    {
        $order = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1150.00,
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Verified',
            'status' => 'Completed',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);

        OrderItem::create([
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'quantity' => 1,
            'price' => 1000.00,
        ]);

        $payout = FinancialLedgerService::reconcileSellerSettlementForOrder($order);

        $this->assertNotNull($payout);
        $this->assertEquals(SellerPayout::STATUS_AVAILABLE_FOR_PAYOUT, $payout->status);
        $this->assertEquals(1150.00, (float) $payout->net_settlement_amount);
        $this->assertEquals(0.00, (float) $payout->commission_deducted);
    }

    /** @test */
    public function unverified_or_cancelled_order_is_not_available_for_payout()
    {
        // Unverified order
        $pendingOrder = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1150.00,
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Pending',
            'status' => 'Pending',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);

        OrderItem::create([
            'orderId' => $pendingOrder->id,
            'productId' => $this->product->id,
            'quantity' => 1,
            'price' => 1000.00,
        ]);

        $pendingPayout = FinancialLedgerService::reconcileSellerSettlementForOrder($pendingOrder);
        $this->assertEquals(SellerPayout::STATUS_PENDING_ELIGIBILITY, $pendingPayout->status);

        // Cancelled order
        $cancelledOrder = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1150.00,
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Verified',
            'status' => 'Cancelled',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);

        $cancelledPayout = FinancialLedgerService::reconcileSellerSettlementForOrder($cancelledOrder);
        $this->assertEquals(SellerPayout::STATUS_ON_HOLD, $cancelledPayout->status);
    }

    /** @test */
    public function customer_sukli_is_strictly_excluded_from_seller_earnings_and_commission()
    {
        // Customer paid 1500 for a 1150 order (350 sukli)
        $order = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1150.00,
            'overpayment_amount' => 350.00,
            'sukli_phone' => '09191234567',
            'sukli_status' => 'pending_refund',
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Verified',
            'status' => 'Completed',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);

        OrderItem::create([
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'quantity' => 1,
            'price' => 1000.00,
        ]);

        PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
            'payment_method' => 'GCash',
            'wallet_type' => 'gcash',
            'expected_amount' => 1150.00,
            'detected_amount' => 1500.00,
            'amount_paid' => 1500.00,
            'status' => 'VERIFIED',
            'reference_number' => 'GCASH-99887766',
        ]);

        $breakdown = FinancialLedgerService::calculateSellerSettlementBreakdown($order);

        // Seller gets order grand total (1150), sukli (350) is excluded from seller settlement
        $this->assertEquals(1150.00, $breakdown['net_settlement_amount']);
        $this->assertEquals(0.00, $breakdown['commission_deducted']);
    }

    /** @test */
    public function admin_and_superadmin_can_process_manual_seller_payout_with_proof()
    {
        $order = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1150.00,
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Verified',
            'status' => 'Completed',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);

        OrderItem::create([
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'quantity' => 1,
            'price' => 1000.00,
        ]);

        $payout = FinancialLedgerService::reconcileSellerSettlementForOrder($order);

        $proofFile = UploadedFile::fake()->image('payout_receipt.png');

        // Admin processes payout
        $response = $this->actingAs($this->admin)->post(route('admin.payouts.process', $payout->id), [
            'payout_method' => 'GCash',
            'payout_destination' => '09171234567',
            'transfer_reference' => 'MANUAL-GCASH-123456',
            'notes' => 'Disbursed via GCash merchant portal.',
            'transfer_proof' => $proofFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payout->refresh();
        $this->assertEquals(SellerPayout::STATUS_PAID, $payout->status);
        $this->assertEquals('MANUAL-GCASH-123456', $payout->transaction_reference);
        $this->assertEquals($this->admin->id, $payout->processed_by);
        $this->assertNotNull($payout->paid_at);
        $this->assertNotNull($payout->transfer_proof_path);
    }

    /** @test */
    public function duplicate_payout_processing_is_prevented()
    {
        $order = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1150.00,
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Verified',
            'status' => 'Completed',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);

        OrderItem::create([
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'quantity' => 1,
            'price' => 1000.00,
        ]);

        $payout = FinancialLedgerService::reconcileSellerSettlementForOrder($order);

        // Process once
        FinancialLedgerService::processManualSellerPayout($payout, [
            'payout_method' => 'GCash',
            'payout_destination' => '09171234567',
            'transaction_reference' => 'REF-UNIQUE-001',
        ], $this->admin);

        $this->assertEquals(SellerPayout::STATUS_PAID, $payout->fresh()->status);

        // Attempting to process again must fail
        $response = $this->actingAs($this->superadmin)->post(route('superadmin.payouts.process', $payout->id), [
            'payout_method' => 'GCash',
            'payout_destination' => '09171234567',
            'transfer_reference' => 'REF-DUPLICATE-002',
        ]);

        $response->assertSessionHasErrors('payout');
    }

    /** @test */
    public function settlements_can_be_placed_on_hold_and_released_by_operators()
    {
        $order = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1150.00,
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Verified',
            'status' => 'Completed',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);

        OrderItem::create([
            'orderId' => $order->id,
            'productId' => $this->product->id,
            'quantity' => 1,
            'price' => 1000.00,
        ]);

        $payout = FinancialLedgerService::reconcileSellerSettlementForOrder($order);

        // Hold payout
        $responseHold = $this->actingAs($this->admin)->patch(route('admin.payouts.hold', $payout->id), [
            'reason' => 'Customer dispute pending investigation.',
        ]);
        $responseHold->assertRedirect();
        $this->assertEquals(SellerPayout::STATUS_ON_HOLD, $payout->fresh()->status);

        // Release payout
        $responseRelease = $this->actingAs($this->superadmin)->patch(route('superadmin.payouts.release', $payout->id));
        $responseRelease->assertRedirect();
        $this->assertEquals(SellerPayout::STATUS_AVAILABLE_FOR_PAYOUT, $payout->fresh()->status);
    }

    /** @test */
    public function unauthorized_users_cannot_access_or_alter_payouts()
    {
        $order = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1150.00,
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Verified',
            'status' => 'Completed',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);

        $payout = FinancialLedgerService::reconcileSellerSettlementForOrder($order);

        // Customer attempt is blocked by admin middleware
        $resCustomer = $this->actingAs($this->customer)->post(route('admin.payouts.process', $payout->id), [
            'payout_method' => 'GCash',
            'payout_destination' => '09171234567',
            'transaction_reference' => 'FORGED-123',
        ]);
        $this->assertTrue(in_array($resCustomer->status(), [302, 403], true));
        $this->assertNotEquals(SellerPayout::STATUS_PAID, $payout->fresh()->status);

        // Seller attempt is blocked by admin middleware
        $resSeller = $this->actingAs($this->seller)->post(route('admin.payouts.process', $payout->id), [
            'payout_method' => 'GCash',
            'payout_destination' => '09171234567',
            'transaction_reference' => 'FORGED-123',
        ]);
        $this->assertTrue(in_array($resSeller->status(), [302, 403], true));
        $this->assertNotEquals(SellerPayout::STATUS_PAID, $payout->fresh()->status);
    }

    /** @test */
    public function seller_portal_shows_accurate_financial_summary_and_earnings()
    {
        // 1. Create Cash order (COD)
        $cashOrder = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 1150.00, // 1000 product + 150 shipping
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Verified',
            'status' => 'Completed',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);
        OrderItem::create([
            'orderId' => $cashOrder->id,
            'productId' => $this->product->id,
            'quantity' => 1,
            'price' => 1000.00,
        ]);

        // 2. Create Online order (GCash)
        $gcashOrder = Order::create([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 2150.00, // 2000 product + 150 shipping
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Verified',
            'status' => 'Completed',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);
        OrderItem::create([
            'orderId' => $gcashOrder->id,
            'productId' => $this->product->id,
            'quantity' => 2,
            'price' => 1000.00,
        ]);

        FinancialLedgerService::reconcileSellerSettlementForOrder($gcashOrder);

        $response = $this->actingAs($this->seller)->get(route('seller.commission'));
        $response->assertOk();
        $response->assertViewHas('financialSummary');
        $response->assertViewHas('payouts');
        $response->assertSee('Cash Sales Platform Commission');
        $response->assertSee('Platform Commission Due');
    }
}

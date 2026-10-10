<?php

namespace Tests\Feature;

use App\Http\Controllers\AnalyticsController;
use App\Models\CommissionRecord;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\SellerPayout;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Financial\FinancialLedgerService;
use App\Services\Returns\RecordCashRefundService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class MasterCommissionCalculationAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $seller;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Master Super Admin',
            'email'    => 'superadmin@lumbarong.test',
            'password' => bcrypt('password123'),
            'role'     => 'superadmin',
        ]);

        $this->admin = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'System Admin',
            'email'    => 'admin@lumbarong.test',
            'password' => bcrypt('password123'),
            'role'     => 'admin',
        ]);

        $this->seller = User::create([
            'id'         => (string) Str::uuid(),
            'name'       => 'Artisan Seller',
            'email'      => 'artisan@lumbarong.test',
            'password'   => bcrypt('password123'),
            'role'       => 'seller',
            'shopName'   => 'Lumban Heritage Embroidery',
            'isVerified' => true,
            'status'     => 'active',
            'phone'      => '09171234567',
        ]);

        $this->customer = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Valued Customer',
            'email'    => 'customer@lumbarong.test',
            'password' => bcrypt('password123'),
            'role'     => 'customer',
        ]);
    }

    private function createProduct(float $price = 5505.50): Product
    {
        return Product::create([
            'id'          => (string) Str::uuid(),
            'sellerId'    => $this->seller->id,
            'name'        => 'Custom Barong Tagalog',
            'price'       => $price,
            'stock'       => 50,
            'status'      => 'active',
            'description' => 'Fine piña barong hand-embroidered in Lumban',
        ]);
    }

    private function createOrderWithItem(
        float $itemPrice,
        int $quantity = 1,
        string $paymentMethod = 'COD',
        string $status = 'delivered',
        string $paymentStatus = 'paid',
        float $shippingFee = 0.00
    ): Order {
        $product = $this->createProduct($itemPrice);
        $totalAmount = round(($itemPrice * $quantity) + $shippingFee, 2);

        $order = Order::create([
            'id'                      => (string) Str::uuid(),
            'customerId'              => $this->customer->id,
            'sellerId'                => $this->seller->id,
            'totalAmount'             => $totalAmount,
            'shipping_fee'            => $shippingFee,
            'status'                  => $status,
            'paymentMethod'           => $paymentMethod,
            'paymentStatus'           => $paymentStatus,
            'total_verified_payments' => $totalAmount,
            'shippingAddress'         => ['city' => 'Lumban', 'province' => 'Laguna'],
            'createdAt'               => now(),
        ]);

        OrderItem::create([
            'id'        => (string) Str::uuid(),
            'orderId'   => $order->id,
            'productId' => $product->id,
            'quantity'  => $quantity,
            'price'     => $itemPrice,
        ]);

        return $order->fresh(['items.product']);
    }

    /**
     * Test A — Configurable rate:
     * ₱5,505.50 in commissionable sales:
     * At 10%: Commission is ₱550.55, Net is ₱4,954.95.
     * Change Super Admin setting to 7%:
     * New eligible transaction: Commission is ₱385.39, Net is ₱5,120.11.
     * Confirm historical transaction retains previously recorded rate and amount.
     */
    public function test_a_configurable_rate_and_historical_rate_retention(): void
    {
        // 1. Super Admin configures 10%
        $response = $this->actingAs($this->superadmin)->post('/superadmin/commission-rate', [
            'rate' => 10.0,
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertEquals(10.0, FinancialLedgerService::getCommissionRate());

        // 2. Transaction 1 created under 10% rate
        $order1 = $this->createOrderWithItem(5505.50, 1, 'COD', 'delivered', 'paid');
        $order1->update([
            'commission_rate'   => 10.00,
            'commission_amount' => 550.55,
        ]);

        $breakdown1 = FinancialLedgerService::calculateSellerSettlementBreakdown($order1);
        $this->assertEquals(5505.50, $breakdown1['gross_sales']);
        $this->assertEquals(10.0, $breakdown1['commission_rate']);
        $this->assertEquals(550.55, $breakdown1['commission_deducted']);
        $this->assertEquals(4954.95, $breakdown1['net_settlement_amount']);

        // 3. Super Admin changes rate to 7%
        $response = $this->actingAs($this->superadmin)->post('/superadmin/commission-rate', [
            'rate' => 7.0,
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertEquals(7.0, FinancialLedgerService::getCommissionRate());

        // 4. Transaction 2 created under 7% rate
        $order2 = $this->createOrderWithItem(5505.50, 1, 'COD', 'delivered', 'paid');
        $order2->update([
            'commission_rate'   => 7.00,
            'commission_amount' => 385.39,
        ]);

        $breakdown2 = FinancialLedgerService::calculateSellerSettlementBreakdown($order2);
        $this->assertEquals(5505.50, $breakdown2['gross_sales']);
        $this->assertEquals(7.0, $breakdown2['commission_rate']);
        $this->assertEquals(385.39, $breakdown2['commission_deducted']);
        $this->assertEquals(5120.11, $breakdown2['net_settlement_amount']);

        // 5. Confirm Transaction 1 still retains 10% rate and ₱550.55 commission
        $historicalBreakdown = FinancialLedgerService::calculateSellerSettlementBreakdown($order1->fresh());
        $this->assertEquals(10.0, $historicalBreakdown['commission_rate']);
        $this->assertEquals(550.55, $historicalBreakdown['commission_deducted']);
        $this->assertEquals(4954.95, $historicalBreakdown['net_settlement_amount']);
    }

    /**
     * Test B — No inflated gross sales:
     * Verify that Gross Item Sales represents actual sales and is NEVER inflated by adding commission.
     */
    public function test_b_no_inflated_gross_sales(): void
    {
        SystemSetting::updateOrCreate(['key' => 'commission_rate'], ['value' => '10']);

        // Create order with ₱5,505.50
        $order = $this->createOrderWithItem(5505.50, 1, 'COD', 'completed', 'paid');

        $controller = app(AnalyticsController::class);
        $request = Request::create('/seller/analytics', 'GET', ['format' => 'json']);
        $request->setUserResolver(fn() => $this->seller);

        $response = $controller->sellerAnalytics($request);
        $data = $response->getData(true);

        $financials = $data['financialAnalytics'];
        $sales = $data['salesAnalytics'];

        // Gross sales must equal actual total item sales: ₱5,505.50, NOT ₱6,056.05
        $this->assertEquals(5505.50, $financials['grossSales']);
        $this->assertEquals(5505.50, $sales['grossSales']);
        $this->assertEquals(550.55, $financials['commissionFee']);
        $this->assertEquals(4954.95, $financials['sellerEarnings']);
        $this->assertNotEquals(6056.05, $financials['grossSales']);
    }

    /**
     * Test C — No double deduction:
     * Verify that commission is deducted exactly once when calculating seller earnings or net payout.
     */
    public function test_c_no_double_deduction(): void
    {
        SystemSetting::updateOrCreate(['key' => 'commission_rate'], ['value' => '10']);

        $order = $this->createOrderWithItem(5505.50, 1, 'COD', 'delivered', 'paid');

        $breakdown = FinancialLedgerService::calculateSellerSettlementBreakdown($order);
        // Formula: Net Payout = Gross Sales - Platform Commission
        // 5505.50 - 550.55 = 4954.95
        $expectedNet = round(5505.50 - 550.55, 2);
        $this->assertEquals($expectedNet, $breakdown['net_settlement_amount']);

        // Check Analytics calculation: Gross - Discounts - Refunds - Commission
        $controller = app(AnalyticsController::class);
        $request = Request::create('/seller/analytics', 'GET', ['format' => 'json']);
        $request->setUserResolver(fn() => $this->seller);

        $response = $controller->sellerAnalytics($request);
        $financials = $response->getData(true)['financialAnalytics'];

        $this->assertEquals(5505.50, $financials['grossSales']);
        $this->assertEquals(550.55, $financials['commissionFee']);
        $this->assertEquals(4954.95, $financials['sellerEarnings']);
        $this->assertEquals(
            round($financials['grossSales'] - $financials['commissionFee'] - $financials['discounts'] - $financials['refunds'], 2),
            $financials['sellerEarnings']
        );
    }

    /**
     * Test D — Rate-change consistency across services and controllers:
     * Verify that FinancialLedgerService, AnalyticsController, and SuperAdminController report identical rates.
     */
    public function test_d_rate_change_consistency_across_system(): void
    {
        $this->actingAs($this->superadmin)->post('/superadmin/commission-rate', [
            'rate' => 8.25,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(8.25, FinancialLedgerService::getCommissionRate());

        $controller = app(AnalyticsController::class);
        $request = Request::create('/seller/analytics', 'GET', ['format' => 'json']);
        $request->setUserResolver(fn() => $this->seller);

        $response = $controller->sellerAnalytics($request);
        $financials = $response->getData(true)['financialAnalytics'];
        $this->assertEquals(8.25, $financials['commissionRate']);

        // SuperAdmin Commissions View data consistency
        $summary = FinancialLedgerService::getSellerFinancialSummary($this->seller);
        $this->assertEquals(8.25, $summary['commission_rate']);
    }

    /**
     * Test E — Discounts and refund adjustments without duplicate deductions:
     * When cash refund occurs, commission records adjust proportionally using normalized rate multiplier.
     */
    public function test_e_discounts_and_refunds_commission_adjustment(): void
    {
        SystemSetting::updateOrCreate(['key' => 'commission_rate'], ['value' => '5.00']);

        // Period commission record: 10,000 sales at 5% = 500 commission
        $commissionRecord = CommissionRecord::create([
            'sellerId'         => $this->seller->id,
            'period'           => now()->format('Y-m'),
            'totalSales'       => 10000.00,
            'commissionRate'   => 5.00,
            'commissionAmount' => 500.00,
            'status'           => 'unpaid',
            'dueDate'          => now()->addDays(7),
        ]);

        $order = $this->createOrderWithItem(2000.00, 1, 'COD', 'delivered', 'paid');

        // Customer requested refund of 1,000
        $returnRequest = ReturnRequest::create([
            'orderId'        => $order->id,
            'customer_id'    => $this->customer->id,
            'seller_id'      => $this->seller->id,
            'refund_amount'  => 1000.00,
            'return_status'  => 'approved',
            'refund_status'  => 'approved',
            'reason'         => 'Slight alteration issue',
        ]);

        $refundService = app(RecordCashRefundService::class);
        $result = $refundService->recordCashRefund(
            $returnRequest,
            $this->seller,
            1000.00,
            'refund',
            'Cash refund completed at shop'
        );

        $this->assertInstanceOf(\App\Models\RefundTransaction::class, $result);

        // Commission record adjusted: totalSales 9,000, commission 450.00 (5% of 9,000)
        $commissionRecord->refresh();
        $this->assertEquals(9000.00, (float) $commissionRecord->totalSales);
        $this->assertEquals(450.00, (float) $commissionRecord->commissionAmount);
    }

    /**
     * Test F — Transaction eligibility & fund-handling rules:
     * Only eligible direct cash sales (COD, Store Pickup cash, Special Delivery cash) incur platform commission.
     * Online platform-held payments (GCash/Maya) do not incur platform commission.
     * Unpaid or cancelled orders do not incur platform commission.
     */
    public function test_f_transaction_eligibility_and_fund_handling(): void
    {
        SystemSetting::updateOrCreate(['key' => 'commission_rate'], ['value' => '10.00']);

        // 1. Online platform-held payment: 0% platform commission
        $onlineOrder = $this->createOrderWithItem(5000.00, 1, 'GCash', 'completed', 'paid');
        $this->assertEquals(0.00, FinancialLedgerService::calculateCommissionableSales($onlineOrder));

        // 2. Unpaid COD order: 0 commissionable sales
        $unpaidOrder = $this->createOrderWithItem(5000.00, 1, 'COD', 'pending', 'pending');
        $unpaidOrder->update(['total_verified_payments' => 0.00]);
        $this->assertEquals(0.00, FinancialLedgerService::calculateCommissionableSales($unpaidOrder));

        // 3. Cancelled order: 0 commissionable sales
        $cancelledOrder = $this->createOrderWithItem(5000.00, 1, 'COD', 'cancelled', 'paid');
        $this->assertEquals(0.00, FinancialLedgerService::calculateCommissionableSales($cancelledOrder));

        // 4. Paid COD / Store Pickup cash order: 5,000 commissionable sales
        $validCashOrder = $this->createOrderWithItem(5000.00, 1, 'COD', 'completed', 'paid');
        $this->assertEquals(5000.00, FinancialLedgerService::calculateCommissionableSales($validCashOrder));
    }

    /**
     * Test G — Historical accounting and past settlement immutability:
     * When Super Admin changes the current rate, historical periods retain their original rate and commission.
     */
    public function test_g_historical_accounting_preserves_past_records(): void
    {
        // Past period record recorded under 10%
        $pastRecord = CommissionRecord::create([
            'sellerId'         => $this->seller->id,
            'period'           => '2026-08',
            'totalSales'       => 20000.00,
            'commissionRate'   => 10.00,
            'commissionAmount' => 2000.00,
            'status'           => 'paid',
            'paidAt'           => Carbon::parse('2026-08-30'),
        ]);

        // Current system setting changed to 6.5%
        SystemSetting::updateOrCreate(['key' => 'commission_rate'], ['value' => '6.50']);

        // Summary for 2026-08 should preserve the historical 10% rate and 2,000 commission
        $summary = FinancialLedgerService::getSellerFinancialSummary($this->seller, '2026-08');
        $this->assertEquals(10.00, $summary['commission_rate']);
        $this->assertEquals(2000.00, $summary['commission_due']);

        // Fresh period uses the current 6.5% rate
        $freshSummary = FinancialLedgerService::getSellerFinancialSummary($this->seller, '2026-11');
        $this->assertEquals(6.50, $freshSummary['commission_rate']);
    }

    /**
     * Test H — Precision & monetary rounding:
     * Test values that produce fractional cents (e.g. ₱123.45 * 7% = ₱8.6415 -> ₱8.64).
     */
    public function test_h_fractional_cent_precision_and_rounding(): void
    {
        SystemSetting::updateOrCreate(['key' => 'commission_rate'], ['value' => '7.00']);

        $order = $this->createOrderWithItem(123.45, 1, 'COD', 'delivered', 'paid');
        $breakdown = FinancialLedgerService::calculateSellerSettlementBreakdown($order);

        // 123.45 * 0.07 = 8.6415 -> round to 8.64
        $this->assertEquals(8.64, $breakdown['commission_deducted']);
        // 123.45 - 8.64 = 114.81
        $this->assertEquals(114.81, $breakdown['net_settlement_amount']);
        $this->assertEquals(123.45, round($breakdown['net_settlement_amount'] + $breakdown['commission_deducted'], 2));
    }

    /**
     * Test I — Authorization and input validation:
     * Unauthorized roles (seller, customer, guest) cannot change commission rate.
     * Invalid rates (< 0, > 100, non-numeric) are rejected.
     */
    public function test_i_authorization_and_input_validation(): void
    {
        // 1. Seller cannot update commission rate via superadmin endpoint
        $this->actingAs($this->seller)->postJson('/superadmin/commission-rate', [
            'rate' => 12.0,
        ])->assertStatus(403);

        $this->actingAs($this->seller)->post('/superadmin/commission-rate', [
            'rate' => 12.0,
        ])->assertRedirect('/superadmin/login');

        // 2. Customer cannot update commission rate
        $this->actingAs($this->customer)->postJson('/superadmin/commission-rate', [
            'rate' => 12.0,
        ])->assertStatus(403);

        // 3. Super admin cannot submit negative rate
        $this->actingAs($this->superadmin)->post('/superadmin/commission-rate', [
            'rate' => -5.0,
        ])->assertSessionHasErrors(['rate']);

        // 4. Super admin cannot submit rate exceeding 100%
        $this->actingAs($this->superadmin)->post('/superadmin/commission-rate', [
            'rate' => 105.0,
        ])->assertSessionHasErrors(['rate']);

        // 5. Super admin cannot submit non-numeric rate
        $this->actingAs($this->superadmin)->post('/superadmin/commission-rate', [
            'rate' => 'invalid_string',
        ])->assertSessionHasErrors(['rate']);

        // 6. Admin Settings update validation
        $this->actingAs($this->admin)->post('/admin/settings', [
            'commission_rate' => -10,
        ])->assertSessionHasErrors(['commission_rate']);

        $this->actingAs($this->admin)->post('/admin/settings', [
            'commission_rate' => 150,
        ])->assertSessionHasErrors(['commission_rate']);
    }

    /**
     * Test J — API and View consistency:
     * Verify that the Seller Analytics Blade renders the dynamic commission rate.
     */
    public function test_j_api_and_user_interface_consistency(): void
    {
        SystemSetting::updateOrCreate(['key' => 'commission_rate'], ['value' => '7.5']);

        $this->createOrderWithItem(5505.50, 1, 'COD', 'completed', 'paid');

        // Check HTML View renders dynamic 7.5% rather than hardcoded 10% or 5%
        $response = $this->actingAs($this->seller)->get('/seller/analytics');
        $response->assertStatus(200);
        $response->assertSee('Platform Commission (7.5%)');
        $response->assertSee('LumBarong Marketplace Commission Fee (7.5%)');
        $response->assertDontSee('Platform Commission (10%)');

        // Check JSON API returns matching figures
        $jsonResponse = $this->actingAs($this->seller)->getJson('/seller/analytics?format=json');
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonPath('financialAnalytics.commissionRate', 7.5);
        $jsonResponse->assertJsonPath('financialAnalytics.grossSales', 5505.5);
        // 5505.50 * 0.075 = 412.9125 -> 412.91
        $jsonResponse->assertJsonPath('financialAnalytics.commissionFee', 412.91);
        // 5505.50 - 412.91 = 5092.59
        $jsonResponse->assertJsonPath('financialAnalytics.sellerEarnings', 5092.59);
    }
}

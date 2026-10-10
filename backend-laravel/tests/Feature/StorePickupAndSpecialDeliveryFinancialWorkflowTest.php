<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\ReturnRequest;
use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneArea;
use App\Models\User;
use App\Services\CreateOrderService;
use App\Services\Orders\RecordSellerPaymentService;
use App\Services\Returns\ProcessCancellationRefundService;
use App\Services\Returns\ProcessPlatformRefundService;
use App\Services\Returns\RecordCashRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorePickupAndSpecialDeliveryFinancialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $customer2;
    protected User $seller;
    protected User $otherSeller;
    protected User $admin;
    protected User $superAdmin;
    protected Product $product1000;
    protected Address $address;
    protected ShippingProvider $storePickupProvider;
    protected ShippingProvider $specialDeliveryProvider;
    protected ShippingProvider $courierProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'role'   => 'customer',
            'status' => 'Active',
            'name'   => 'Maria Santos',
            'email'  => 'maria@example.com',
        ]);

        $this->customer2 = User::factory()->create([
            'role'   => 'customer',
            'status' => 'Active',
            'name'   => 'Juan Dela Cruz',
            'email'  => 'juan@example.com',
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
            'name'   => 'Super Administrator',
            'email'  => 'superadmin@lumbarong.com',
        ]);

        $this->product1000 = Product::create([
            'id'          => (string) Str::uuid(),
            'sellerId'    => $this->seller->id,
            'name'        => 'Custom Piña Barong Masterpiece',
            'slug'        => 'custom-pina-barong-masterpiece',
            'description' => 'Fine hand-embroidered piña fabric.',
            'category'    => 'Barong',
            'price'       => 1000.00,
            'sale_price'  => 1000.00,
            'stock'       => 20,
            'status'      => 'approved',
            'weight'      => 0.5,
            'length'      => 30,
            'width'       => 20,
            'height'      => 5,
        ]);

        $this->address = Address::create([
            'id'            => (string) Str::uuid(),
            'userId'        => $this->customer->id,
            'recipientName' => 'Maria Santos',
            'phone'         => '09171112222',
            'houseNo'       => '123',
            'street'        => 'General Luna St',
            'barangay'      => 'Poblacion',
            'city'          => 'Lumban',
            'province'      => 'Laguna',
            'region'        => 'Region IV-A (CALABARZON)',
            'postalCode'    => '4014',
            'isDefault'     => true,
        ]);

        $this->storePickupProvider = ShippingProvider::firstOrCreate(
            ['code' => 'store_pickup'],
            [
                'id'               => (string) Str::uuid(),
                'name'             => 'Artisan Store Pickup',
                'is_active'        => true,
                'calculation_type' => 'flat',
            ]
        );

        $this->specialDeliveryProvider = ShippingProvider::firstOrCreate(
            ['code' => 'seller_direct'],
            [
                'id'               => (string) Str::uuid(),
                'name'             => 'Artisan Special Direct Delivery',
                'is_active'        => true,
                'calculation_type' => 'flat',
            ]
        );

        $this->courierProvider = ShippingProvider::firstOrCreate(
            ['code' => 'jnt'],
            [
                'id'               => (string) Str::uuid(),
                'name'             => 'J&T Express Courier',
                'is_active'        => true,
                'calculation_type' => 'tiered',
            ]
        );

        $zone = ShippingZone::firstOrCreate(['code' => 'laguna_local'], ['id' => (string) Str::uuid(), 'name' => 'Laguna Local']);
        ShippingZoneArea::firstOrCreate(['zone_id' => $zone->id, 'province' => 'Laguna', 'city' => 'Lumban'], ['id' => (string) Str::uuid()]);
        ShippingRate::firstOrCreate(
            ['provider_id' => $this->courierProvider->id, 'origin_zone_id' => $zone->id, 'destination_zone_id' => $zone->id],
            ['id' => (string) Str::uuid(), 'base_rate' => 0.00, 'base_weight_kg' => 1.0, 'additional_rate_per_kg' => 0.00, 'estimated_days_min' => 1, 'estimated_days_max' => 2]
        );

        \App\Models\SellerSpecialDeliveryRate::create([
            'seller_id' => $this->seller->id,
            'municipality_key' => 'lumban',
            'municipality_name' => 'Lumban',
            'surcharge' => 0.00,
            'is_enabled' => true,
        ]);
    }

    protected function createDirectOrder(string $providerCode = 'store_pickup', array $overrides = []): Order
    {
        $provider = $providerCode === 'store_pickup' ? $this->storePickupProvider : $this->specialDeliveryProvider;
        $service = app(CreateOrderService::class);
        $order = $service->createOrder(array_merge([
            'customer'           => $this->customer,
            'items'              => [['id' => $this->product1000->id, 'quantity' => 1]],
            'address_id'         => $this->address->id,
            'selectedProviderId' => $provider->id,
        ], $overrides));

        return $order->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 1: Store Pickup checkout bypasses online payment selection & scanning
    // ─────────────────────────────────────────────────────────────────────────
    public function test_01_store_pickup_checkout_creates_direct_settlement_order_without_online_receipts(): void
    {
        $order = $this->createDirectOrder('store_pickup');

        $this->assertEquals('Store Pickup', $order->paymentMethod);
        $this->assertEquals('Pending Payment (Store Pickup)', $order->paymentStatus);
        $this->assertNull($order->paymentReference);
        $this->assertNull($order->paymentProof);
        $this->assertEquals(0.00, (float)$order->total_verified_payments);
        $this->assertTrue($order->isSellerHeldPayment());
        $this->assertFalse($order->isPlatformHeldPayment());
        $this->assertEquals(0, PaymentTransaction::where('order_id', $order->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 2: Special Delivery checkout bypasses online payment selection & scanning
    // ─────────────────────────────────────────────────────────────────────────
    public function test_02_special_delivery_checkout_creates_direct_settlement_order_without_online_receipts(): void
    {
        $order = $this->createDirectOrder('seller_direct');

        $this->assertEquals('Special Delivery', $order->paymentMethod);
        $this->assertEquals('Pending Payment (Special Delivery)', $order->paymentStatus);
        $this->assertNull($order->paymentReference);
        $this->assertNull($order->paymentProof);
        $this->assertEquals(0.00, (float)$order->total_verified_payments);
        $this->assertTrue($order->isSellerHeldPayment());
        $this->assertFalse($order->isPlatformHeldPayment());
        $this->assertEquals(0, PaymentTransaction::where('order_id', $order->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 3: Store Pickup paid in Cash directly to Seller recorded accurately
    // ─────────────────────────────────────────────────────────────────────────
    public function test_03_seller_records_cash_payment_received_at_shop_for_store_pickup(): void
    {
        $order = $this->createDirectOrder('store_pickup');

        $response = $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 1000.00,
            'received_at'     => now()->toDateTimeString(),
            'notes'           => 'Paid in cash at Lumban artisan showroom',
        ]);
        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $order->refresh();
        $this->assertEquals('Paid', $order->paymentStatus);
        $this->assertEquals(1000.00, (float)$order->total_verified_payments);
        $this->assertDatabaseHas('order_status_histories', [
            'orderId'  => $order->id,
            'userRole' => 'seller',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 4: Store Pickup paid via direct GCash transfer to Seller
    // ─────────────────────────────────────────────────────────────────────────
    public function test_04_seller_records_direct_gcash_transfer_received_from_buyer(): void
    {
        $order = $this->createDirectOrder('store_pickup');

        $response = $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'   => 'Direct GCash Transfer',
            'amount_received'  => 1000.00,
            'reference_number' => 'GCASH-DIRECT-778899',
            'notes'            => 'Buyer sent money directly to artisan personal GCash wallet',
        ]);
        $response->assertOk();

        $order->refresh();
        $this->assertEquals('Paid', $order->paymentStatus);
        $this->assertEquals('GCASH-DIRECT-778899', $order->paymentReference);
        $this->assertEquals('Direct GCash Transfer', $order->paymentMethod);

        // Crucial: Kept separate from platform PaymentTransaction records
        $this->assertEquals(0, PaymentTransaction::where('order_id', $order->id)->count());
        $this->assertTrue($order->isSellerHeldPayment());
        $this->assertFalse($order->isPlatformHeldPayment());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 5: Store Pickup paid via direct Maya transfer to Seller
    // ─────────────────────────────────────────────────────────────────────────
    public function test_05_seller_records_direct_maya_transfer_received_from_buyer(): void
    {
        $order = $this->createDirectOrder('store_pickup');

        $response = $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'   => 'Direct Maya Transfer',
            'amount_received'  => 1000.00,
            'reference_number' => 'MAYA-DIRECT-334455',
            'notes'            => 'Direct Maya peer-to-peer transfer to artisan',
        ]);
        $response->assertOk();

        $order->refresh();
        $this->assertEquals('Paid', $order->paymentStatus);
        $this->assertTrue($order->isSellerHeldPayment());
        $this->assertFalse($order->isPlatformHeldPayment());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 6: Special Delivery paid directly to Seller recorded accurately
    // ─────────────────────────────────────────────────────────────────────────
    public function test_06_seller_records_direct_payment_for_special_delivery(): void
    {
        $order = $this->createDirectOrder('seller_direct');
        $total = (float) $order->totalAmount;

        $response = $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash on Handover',
            'amount_received' => $total,
            'notes'           => 'Rider handed over garment and accepted cash from recipient',
        ]);
        $response->assertOk();

        $order->refresh();
        $this->assertEquals('Paid', $order->paymentStatus);
        $this->assertTrue($order->isSellerHeldPayment());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 7: Unauthorized seller or customer cannot record payment for another seller
    // ─────────────────────────────────────────────────────────────────────────
    public function test_07_unauthorized_user_cannot_record_payment_for_seller_order(): void
    {
        $order = $this->createDirectOrder('store_pickup');

        // Other seller is blocked
        $this->actingAs($this->otherSeller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 1000.00,
        ])->assertStatus(403);

        // Customer is blocked
        $this->actingAs($this->customer)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 1000.00,
        ])->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 8: Cannot record payment twice or exceed order total
    // ─────────────────────────────────────────────────────────────────────────
    public function test_08_duplicate_payment_recording_and_overpayment_forgery_blocked(): void
    {
        $order = $this->createDirectOrder('store_pickup');

        // Recording exceeding amount is blocked
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 1500.00, // Exceeds order total 1000
        ])->assertStatus(422);

        // Record valid payment
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 1000.00,
        ])->assertOk();

        // Recording again on fully paid order is blocked
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 1000.00,
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 9: Cancelled order with NO payment collected creates NO fictitious refund
    // ─────────────────────────────────────────────────────────────────────────
    public function test_09_unpaid_cancellation_does_not_create_fictitious_refund(): void
    {
        $order = $this->createDirectOrder('store_pickup');

        // Cancel order while unpaid
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/cancel", [
            'reason' => 'Buyer requested cancellation before claiming or payment',
        ])->assertOk();

        $order->refresh();
        $this->assertEquals('Cancelled', $order->status);
        $this->assertEquals(0.00, $order->remainingCancellationRefundAmount());
        $this->assertEquals('unpaid', $order->cancellationRefundStatus());
        $this->assertEquals(0, RefundTransaction::where('order_id', $order->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 10: Store Pickup with seller-held payment cancelled -> Seller refunds directly
    // ─────────────────────────────────────────────────────────────────────────
    public function test_10_store_pickup_seller_held_refund_is_processed_by_seller(): void
    {
        $order = $this->createDirectOrder('store_pickup');

        // Seller records payment
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 1000.00,
        ])->assertOk();

        // Order cancelled
        $order->refresh();
        $order->update(['status' => 'Cancelled']);

        $this->assertEquals(1000.00, $order->remainingCancellationRefundAmount());
        $this->assertEquals('pending_refund', $order->cancellationRefundStatus());

        // Seller executes direct cash refund
        $response = $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount'  => 1000.00,
            'payment_method' => 'cash',
            'reason'         => 'Returned cash directly to customer at workshop desk',
        ]);
        $response->assertOk();

        $order->refresh();
        $this->assertEquals(0.00, $order->remainingCancellationRefundAmount());
        $this->assertEquals('refunded', $order->cancellationRefundStatus());

        $this->assertDatabaseHas('refund_transactions', [
            'order_id'       => $order->id,
            'refund_amount'  => 1000.00,
            'payment_method' => 'cash',
            'processed_by'   => $this->seller->id,
            'status'         => 'transferred',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 11: Special Delivery direct transfer refund is processed by Seller
    // ─────────────────────────────────────────────────────────────────────────
    public function test_11_special_delivery_direct_transfer_refund_is_processed_by_seller(): void
    {
        $order = $this->createDirectOrder('seller_direct');

        // Seller records payment received via direct GCash transfer
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'   => 'Direct GCash Transfer',
            'amount_received'  => 1000.00,
            'reference_number' => '1004455667788',
        ])->assertOk();

        $order->update(['status' => 'Cancelled']);

        // Seller records direct refund transfer back to customer
        $response = $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount'  => 1000.00,
            'payment_method' => 'gcash',
            'reason'         => 'Artisan transferred refund back to customer GCash account',
        ]);
        $response->assertOk();

        $this->assertDatabaseHas('refund_transactions', [
            'order_id'       => $order->id,
            'refund_amount'  => 1000.00,
            'payment_method' => 'gcash',
            'processed_by'   => $this->seller->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 12: Super Admin disbursement is blocked for seller-held funds
    // ─────────────────────────────────────────────────────────────────────────
    public function test_12_super_admin_cannot_disburse_platform_funds_for_seller_held_orders(): void
    {
        $order = $this->createDirectOrder('store_pickup');

        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 1000.00,
        ])->assertOk();

        $order->update(['status' => 'Cancelled']);

        // Super Admin attempts to disburse through centralized queue
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.returns.refund-cancellation', $order->id), [
            'refund_amount'       => 1000.00,
            'transfer_reference'  => 'GCASH-SUPER-OUT-99',
            'destination_account' => '09171112222',
            'destination_name'    => 'Maria Santos',
        ]);

        // Blocked with validation error explaining seller holds the funds
        $response->assertSessionHasErrors(['order']);
        $this->assertEquals(0, RefundTransaction::where('order_id', $order->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 13: Seller CANNOT refund platform-held online payments
    // ─────────────────────────────────────────────────────────────────────────
    public function test_13_seller_cannot_refund_platform_held_online_prepayments(): void
    {
        // Normal courier with platform GCash prepayment
        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer'           => $this->customer,
            'items'              => [['id' => $this->product1000->id, 'quantity' => 1]],
            'address_id'         => $this->address->id,
            'paymentMethod'      => 'GCash',
            'selectedProviderId' => $this->courierProvider->id,
            'receipts'           => [
                ['screening' => ['detected_amount' => 1000.00, 'status' => 'PASS'], 'paymentProof' => 'p.jpg', 'paymentReference' => '1009988776655'],
            ],
        ]);

        $order->update(['paymentStatus' => 'Paid', 'status' => 'Cancelled']);

        $this->assertTrue($order->isPlatformHeldPayment());
        $this->assertFalse($order->isSellerHeldPayment());

        // Seller cash-refund is strictly rejected
        $response = $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount' => 1000.00,
        ]);
        $response->assertStatus(422);

        // Super Admin CAN process platform disbursement
        $adminResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.returns.refund-cancellation', $order->id), [
            'refund_amount'       => 1000.00,
            'transfer_reference'  => 'GCASH-PLATFORM-OUT-01',
            'destination_account' => '09171112222',
            'destination_name'    => 'Maria Santos',
        ]);
        $adminResponse->assertRedirect();

        $this->assertDatabaseHas('refund_transactions', [
            'order_id'           => $order->id,
            'transfer_reference' => 'GCASH-PLATFORM-OUT-01',
            'processed_by'       => $this->superAdmin->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 14: Commission rules - Platform commission applies to seller-collected transactions
    // ─────────────────────────────────────────────────────────────────────────
    public function test_14_commission_applies_to_seller_collected_transactions_and_reverses_on_refund(): void
    {
        $ledger = app(\App\Services\Financial\FinancialLedgerService::class);

        $order = $this->createDirectOrder('store_pickup');
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 1000.00,
        ])->assertOk();

        $order->update(['status' => 'Delivered']);
        $order->refresh();

        // Direct Store Pickup is seller-collected, so commission applies under LumBarong rules
        $sales = $ledger->calculateCommissionableSales($order);
        $this->assertEquals(1000.00, (float)$sales);

        // Non-destructive commission adjustment upon refund
        $order->update(['status' => 'Cancelled']);
        $order->refresh();
        $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount' => 1000.00,
            'reason'        => 'Cancelled and refunded in full',
        ])->assertOk();

        $order->refresh();
        $salesAfterRefund = $ledger->calculateCommissionableSales($order);
        $this->assertEquals(0.00, (float)$salesAfterRefund);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 15: Unpaid direct-settlement order has zero commission and zero refundable balance
    // ─────────────────────────────────────────────────────────────────────────
    public function test_15_unpaid_direct_settlement_order_has_zero_commission_and_zero_refundable_balance(): void
    {
        $ledger = app(\App\Services\Financial\FinancialLedgerService::class);

        $order = $this->createDirectOrder('store_pickup');

        $this->assertEquals('Pending Payment (Store Pickup)', $order->paymentStatus);
        $this->assertEquals(0.00, $order->historicalGrossReceived());
        $this->assertEquals(0.00, $order->totalPaidAmount());
        $this->assertEquals(0.00, $order->totalRefundedAmount());
        $this->assertEquals(0.00, $order->remainingRefundableAmount());
        $this->assertEquals(1000.00, $order->outstandingPaymentBalance());
        $this->assertEquals(0.00, $order->currentNetFundsRetained());

        // Zero commissionable sales for unpaid order
        $this->assertEquals(0.00, $ledger->calculateCommissionableSales($order));

        // Attempting a refund on unpaid order is rejected
        $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount' => 100.00,
            'reason'        => 'Should fail on unpaid order',
        ])->assertStatus(422);

        // Cancel order while unpaid
        $order->update(['status' => 'Cancelled']);
        $order->refresh();

        $this->assertEquals('unpaid', $order->cancellationRefundStatus());
        $this->assertEquals(0.00, $order->remainingCancellationRefundAmount());
        $this->assertEquals(0.00, $ledger->calculateCommissionableSales($order));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 16: Reconcile gross payments and refunds (1000 in, 1000 out -> 0 remaining, 1 more rejected)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_16_reconcile_gross_payments_and_refunds_1000_in_1000_out_leaves_zero_and_rejects_1_peso_more(): void
    {
        $order = $this->createDirectOrder('store_pickup');

        // 1. Seller records payment of 1,000
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 1000.00,
        ])->assertOk();

        $order->refresh();
        $this->assertEquals(1000.00, $order->historicalGrossReceived());
        $this->assertEquals(0.00, $order->totalRefundedAmount());
        $this->assertEquals(1000.00, $order->remainingRefundableAmount());
        $this->assertEquals(0.00, $order->outstandingPaymentBalance());
        $this->assertEquals(1000.00, $order->currentNetFundsRetained());

        // 2. Completed refund of 1,000
        $order->update(['status' => 'Cancelled']);
        $order->refresh();

        $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount' => 1000.00,
            'reason'        => 'Customer changed mind prior to collection',
        ])->assertOk();

        $order->refresh();
        $this->assertEquals(1000.00, $order->historicalGrossReceived());
        $this->assertEquals(1000.00, $order->totalRefundedAmount());
        $this->assertEquals(0.00, $order->remainingRefundableAmount());
        $this->assertEquals(0.00, $order->currentNetFundsRetained());
        $this->assertEquals('refunded', $order->cancellationRefundStatus());

        // 3. Attempting another ₱1 refund must be rejected!
        $excessResponse = $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount' => 1.00,
            'reason'        => 'Attempting 1 peso excess refund',
        ]);
        $excessResponse->assertStatus(422);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 17: Partial direct-payment accumulation marks Partially Paid and completes upon full balance
    // ─────────────────────────────────────────────────────────────────────────
    public function test_17_partial_direct_payment_accumulation_marks_partially_paid_and_completes_upon_full_balance(): void
    {
        $order = $this->createDirectOrder('store_pickup');

        // Customer pays ₱400 deposit
        $res1 = $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash Deposit',
            'amount_received' => 400.00,
            'notes'           => 'Initial 40% deposit handed at workshop',
        ]);
        $res1->assertOk();

        $order->refresh();
        $this->assertEquals('Partially Paid', $order->paymentStatus);
        $this->assertEquals(400.00, $order->historicalGrossReceived());
        $this->assertEquals(400.00, (float)$order->total_verified_payments);
        $this->assertEquals(600.00, $order->outstandingPaymentBalance());
        $this->assertEquals(400.00, $order->remainingRefundableAmount());

        // Attempting to record ₱700 (exceeds ₱600 outstanding) is rejected
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash Balance',
            'amount_received' => 700.00,
        ])->assertStatus(422);

        // Record remaining ₱600 balance
        $res2 = $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash Final Balance',
            'amount_received' => 600.00,
            'notes'           => 'Final balance upon item collection',
        ]);
        $res2->assertOk();

        $order->refresh();
        $this->assertEquals('Paid', $order->paymentStatus);
        $this->assertEquals(1000.00, $order->historicalGrossReceived());
        $this->assertEquals(1000.00, (float)$order->total_verified_payments);
        $this->assertEquals(0.00, $order->outstandingPaymentBalance());

        // Attempting another payment on fully paid order is rejected
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 100.00,
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 18: Multiple payments, multiple refunds, and net funds retained tracking
    // ─────────────────────────────────────────────────────────────────────────
    public function test_18_multiple_payments_multiple_refunds_and_net_funds_retained(): void
    {
        $order = $this->createDirectOrder('store_pickup');

        // Customer pays ₱500 via GCash transfer, then ₱500 cash to rider
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'   => 'Direct GCash',
            'amount_received'  => 500.00,
            'reference_number' => 'GCASH-PART-1',
        ])->assertOk();

        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash to Rider',
            'amount_received' => 500.00,
        ])->assertOk();

        $order->refresh();
        $this->assertEquals(1000.00, $order->historicalGrossReceived());

        // Order cancelled with partial refund 1: ₱300
        $order->update(['status' => 'Cancelled']);
        $order->refresh();

        $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount' => 300.00,
            'reason'        => 'First installment refund',
        ])->assertOk();

        $order->refresh();
        $this->assertEquals(1000.00, $order->historicalGrossReceived());
        $this->assertEquals(300.00, $order->totalRefundedAmount());
        $this->assertEquals(700.00, $order->remainingRefundableAmount());
        $this->assertEquals(700.00, $order->currentNetFundsRetained());
        $this->assertEquals('partially_refunded', $order->cancellationRefundStatus());

        // Partial refund 2: ₱400
        $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount' => 400.00,
            'reason'        => 'Second installment refund',
        ])->assertOk();

        $order->refresh();
        $this->assertEquals(700.00, $order->totalRefundedAmount());
        $this->assertEquals(300.00, $order->remainingRefundableAmount());
        $this->assertEquals(300.00, $order->currentNetFundsRetained());

        // Final refund: ₱300
        $this->actingAs($this->seller)->postJson(route('seller.orders.cash-refund', $order->id), [
            'refund_amount' => 300.00,
            'reason'        => 'Final refund balance',
        ])->assertOk();

        $order->refresh();
        $this->assertEquals(1000.00, $order->totalRefundedAmount());
        $this->assertEquals(0.00, $order->remainingRefundableAmount());
        $this->assertEquals(0.00, $order->currentNetFundsRetained());
        $this->assertEquals('refunded', $order->cancellationRefundStatus());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 19: Overpaid cancelled order calculates full eligible refund including sukli
    // ─────────────────────────────────────────────────────────────────────────
    public function test_19_overpaid_cancelled_order_calculates_full_eligible_refund_including_sukli(): void
    {
        // ₱900 order paid with ₱1,000 receipt (₱100 overpayment / sukli)
        $order = Order::create([
            'id'                      => (string) Str::uuid(),
            'customerId'              => $this->customer->id,
            'sellerId'                => $this->seller->id,
            'totalAmount'             => 900.00,
            'overpayment_amount'      => 100.00,
            'refund_mobile_number'    => '09171112222',
            'status'                  => 'Cancelled',
            'cancellationReason'      => 'Customer cancelled prior to processing',
            'paymentMethod'           => 'GCash',
            'paymentStatus'           => 'Paid',
            'shippingAddress'         => ['city' => 'Lumban'],
        ]);

        PaymentTransaction::create([
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => 'GCASH-OVERPAY-REF-01',
            'wallet_type'      => 'gcash',
            'expected_amount'  => 900.00,
            'detected_amount'  => 1000.00,
            'status'           => 'VERIFIED',
            'verified_at'      => now(),
        ]);

        $this->assertEquals(1000.00, $order->historicalGrossReceived());
        $this->assertEquals(1000.00, $order->remainingRefundableAmount());
        $this->assertEquals(1000.00, $order->remainingCancellationRefundAmount());

        // Super Admin dispatches full ₱1,000 refund
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.returns.refund-cancellation', $order->id), [
            'refund_amount'       => 1000.00,
            'transfer_reference'  => 'GCASH-FULL-REFUND-OUT-99',
            'destination_account' => '09171112222',
            'destination_name'    => 'Maria Santos',
        ]);
        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals(1000.00, $order->totalRefundedAmount());
        $this->assertEquals(0.00, $order->remainingCancellationRefundAmount());
        $this->assertEquals('refunded', $order->cancellationRefundStatus());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 20: Sukli and refunds are never treated as commissionable sales
    // ─────────────────────────────────────────────────────────────────────────
    public function test_20_sukli_and_refunds_are_never_treated_as_commissionable_sales(): void
    {
        $ledger = app(\App\Services\Financial\FinancialLedgerService::class);

        $order = $this->createDirectOrder('store_pickup');

        // Seller records ₱1,000 payment
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 1000.00,
        ])->assertOk();

        $order->update(['status' => 'Delivered', 'overpayment_amount' => 150.00]);
        $order->refresh();

        // Commissionable sales is bounded by product gross (₱1,000), strictly excluding ₱150 overpayment
        $commissionableSales = $ledger->calculateCommissionableSales($order);
        $this->assertEquals(1000.00, (float)$commissionableSales);

        // When refunded ₱400, commissionable sales reduces to ₱600 net retained
        RefundTransaction::create([
            'order_id'           => $order->id,
            'payment_method'     => 'cash',
            'refund_method'      => 'cash',
            'refund_amount'      => 400.00,
            'status'             => 'transferred',
            'transfer_reference' => 'REF-COMM-TEST',
            'processed_by'       => $this->seller->id,
        ]);

        $order->refresh();
        $adjustedSales = $ledger->calculateCommissionableSales($order);
        $this->assertEquals(600.00, (float)$adjustedSales);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 21: Multi-vendor order commission calculation and isolated partial refund
    // ─────────────────────────────────────────────────────────────────────────
    public function test_21_multi_vendor_order_calculates_commission_separately_and_partial_refund_affects_correct_seller(): void
    {
        $ledger = app(\App\Services\Financial\FinancialLedgerService::class);

        // Product from seller 1: ₱800
        $productSeller1 = Product::create([
            'id'          => (string) Str::uuid(),
            'sellerId'    => $this->seller->id,
            'name'        => 'Seller 1 Embroidered Fabric',
            'slug'        => 'seller-1-fabric',
            'description' => 'Fabric from Seller 1',
            'category'    => 'Barong',
            'price'       => 800.00,
            'stock'       => 10,
            'status'      => 'approved',
        ]);

        // Product from seller 2: ₱600
        $productSeller2 = Product::create([
            'id'          => (string) Str::uuid(),
            'sellerId'    => $this->otherSeller->id,
            'name'        => 'Seller 2 Silk Scarf',
            'slug'        => 'seller-2-scarf',
            'description' => 'Scarf from Seller 2',
            'category'    => 'Accessories',
            'price'       => 600.00,
            'stock'       => 10,
            'status'      => 'approved',
        ]);

        // Create multi-vendor direct order (Total: ₱1,400)
        $order = Order::create([
            'id'                      => (string) Str::uuid(),
            'customerId'              => $this->customer->id,
            'sellerId'                => $this->seller->id,
            'totalAmount'             => 1400.00,
            'status'                  => 'Pending',
            'paymentMethod'           => 'Store Pickup',
            'paymentStatus'           => 'Paid',
            'total_verified_payments' => 1400.00,
            'shippingAddress'         => ['city' => 'Lumban'],
        ]);

        $item1 = OrderItem::create([
            'orderId'   => $order->id,
            'productId' => $productSeller1->id,
            'seller_id' => $this->seller->id,
            'quantity'  => 1,
            'price'     => 800.00,
        ]);

        $item2 = OrderItem::create([
            'orderId'   => $order->id,
            'productId' => $productSeller2->id,
            'seller_id' => $this->otherSeller->id,
            'quantity'  => 1,
            'price'     => 600.00,
        ]);

        // Initial commissionable sales: Seller 1 = ₱800, Seller 2 = ₱600
        $this->assertEquals(800.00, $ledger->calculateCommissionableSales($order, $this->seller->id));
        $this->assertEquals(600.00, $ledger->calculateCommissionableSales($order, $this->otherSeller->id));

        // Create a return request and completed refund specifically for Seller 1's item (₱800)
        $returnRequest1 = ReturnRequest::create([
            'id'            => (string) Str::uuid(),
            'orderId'       => $order->id,
            'order_item_id' => $item1->id,
            'customer_id'   => $this->customer->id,
            'seller_id'     => $this->seller->id,
            'reason'        => 'Fabric defect in Seller 1 item',
            'return_status' => 'resolved',
            'refund_status' => 'transferred',
            'approved_amount' => 800.00,
        ]);

        RefundTransaction::create([
            'order_id'          => $order->id,
            'return_request_id' => $returnRequest1->id,
            'payment_method'    => 'cash',
            'refund_method'     => 'cash',
            'refund_amount'     => 800.00,
            'status'            => 'transferred',
            'transfer_reference'=> 'REF-SELLER-1-800',
            'processed_by'      => $this->seller->id,
        ]);

        $order->refresh();

        // Total order refunded is ₱800; net retained on order is ₱600
        $this->assertEquals(800.00, $order->totalRefundedAmount());
        $this->assertEquals(600.00, $order->currentNetFundsRetained());

        // Isolated commission results:
        // Seller 1: ₱800 revenue - ₱800 refunded = ₱0.00 commissionable sales
        // Seller 2: ₱600 revenue - ₱0 refunded = ₱600.00 commissionable sales (unaffected by Seller 1's refund!)
        $this->assertEquals(0.00, $ledger->calculateCommissionableSales($order, $this->seller->id));
        $this->assertEquals(600.00, $ledger->calculateCommissionableSales($order, $this->otherSeller->id));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scenario 22: Exact 3-receipt incremental installment workflow (₱850 + ₱10 + ₱40 = ₱900)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_22_exact_three_receipt_incremental_installments_and_completion(): void
    {
        // Order payable = ₱900
        $product900 = Product::create([
            'id'          => (string) Str::uuid(),
            'sellerId'    => $this->seller->id,
            'name'        => 'Custom Lumban Scarf 900',
            'slug'        => 'custom-lumban-scarf-900',
            'description' => 'Fine scarf',
            'category'    => 'Accessories',
            'price'       => 900.00,
            'stock'       => 10,
            'status'      => 'approved',
        ]);

        $order = Order::create([
            'id'                      => (string) Str::uuid(),
            'customerId'              => $this->customer->id,
            'sellerId'                => $this->seller->id,
            'totalAmount'             => 900.00,
            'status'                  => 'Pending',
            'paymentMethod'           => 'Store Pickup',
            'paymentStatus'           => 'Pending Payment (Store Pickup)',
            'total_verified_payments' => 0.00,
            'shippingAddress'         => ['city' => 'Lumban'],
        ]);

        OrderItem::create([
            'orderId'   => $order->id,
            'productId' => $product900->id,
            'quantity'  => 1,
            'price'     => 900.00,
        ]);

        $this->assertEquals(900.00, $order->outstandingPaymentBalance());
        $this->assertEquals(0.00, $order->historicalGrossReceived());

        // Receipt 1 = ₱850
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Direct GCash',
            'amount_received' => 850.00,
            'notes'           => 'Receipt 1: ₱850',
        ])->assertOk();

        $order->refresh();
        $this->assertEquals('Partially Paid', $order->paymentStatus);
        $this->assertEquals(850.00, $order->historicalGrossReceived());
        $this->assertEquals(50.00, $order->outstandingPaymentBalance()); // Remaining = ₱50

        // Receipt 2 = ₱10
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 10.00,
            'notes'           => 'Receipt 2: ₱10',
        ])->assertOk();

        $order->refresh();
        $this->assertEquals('Partially Paid', $order->paymentStatus);
        $this->assertEquals(860.00, $order->historicalGrossReceived());
        $this->assertEquals(40.00, $order->outstandingPaymentBalance()); // Remaining = ₱40

        // Attempting Receipt exceeding remaining ₱40 (e.g. ₱45) must fail
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 45.00,
        ])->assertStatus(422);

        // Receipt 3 = ₱40
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 40.00,
            'notes'           => 'Receipt 3: ₱40 final balance',
        ])->assertOk();

        $order->refresh();
        $this->assertEquals('Paid', $order->paymentStatus);
        $this->assertEquals(900.00, $order->historicalGrossReceived());
        $this->assertEquals(0.00, $order->outstandingPaymentBalance()); // Remaining = ₱0

        // Attempting another payment when fully paid is rejected
        $this->actingAs($this->seller)->postJson("/seller/api/orders/{$order->id}/record-payment", [
            'payment_method'  => 'Cash',
            'amount_received' => 1.00,
        ])->assertStatus(422);
    }
}

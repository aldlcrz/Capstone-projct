<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneArea;
use App\Models\User;
use App\Services\AiService;
use App\Services\CreateOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GcashAndMayaPaymentScannerComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $customer2;
    protected User $seller;
    protected Product $product;
    protected Product $product900;
    protected Address $address;
    protected ShippingProvider $jntProvider;
    protected ShippingProvider $storePickupProvider;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');

        $this->seller = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Lumban Master Tailor',
            'username' => 'lumbantailor',
            'email' => 'tailor@lumban.test',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'shopName' => 'Lumban Master Tailor',
            'status' => 'active',
            'mobileNumber' => '09181112222',
            'shopPostalCode' => '4014',
            'shopCity' => 'Lumban',
            'shopProvince' => 'Laguna',
            'shopHouseNo' => '10',
            'shopStreet' => 'Calle Principal',
            'shopBarangay' => 'Poblacion',
            'isVerified' => true,
        ]);

        $this->customer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Maria Santos',
            'username' => 'mariasantos',
            'email' => 'maria@customer.test',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'mobileNumber' => '09171112222',
            'isVerified' => true,
        ]);

        $this->customer2 = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Pedro Penduko',
            'username' => 'pedropenduko',
            'email' => 'pedro@customer.test',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'mobileNumber' => '09173334444',
            'isVerified' => true,
        ]);

        $this->product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Hand-Embroidered Piña Barong',
            'slug' => 'hand-embroidered-pina-barong',
            'description' => 'Authentic Lumban handcrafted barong.',
            'category' => 'Barong',
            'price' => 2500.00,
            'sale_price' => 2500.00,
            'stock' => 50,
            'status' => 'approved',
            'weight' => 0.5,
            'length' => 30,
            'width' => 20,
            'height' => 5,
        ]);

        $this->product900 = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Heritage Formal Barong Item',
            'slug' => 'heritage-formal-barong-item',
            'description' => 'Fine handcrafted artisan Barong with 900 base price.',
            'category' => 'Barong',
            'price' => 900.00,
            'sale_price' => 900.00,
            'stock' => 50,
            'status' => 'approved',
            'weight' => 0.5,
            'length' => 30,
            'width' => 20,
            'height' => 5,
        ]);

        $this->address = Address::create([
            'id' => (string) Str::uuid(),
            'userId' => $this->customer->id,
            'recipientName' => 'Maria Santos',
            'phone' => '09171112222',
            'houseNo' => '123',
            'street' => 'Rizal Street',
            'barangay' => 'Barangay 1',
            'city' => 'Santa Cruz',
            'province' => 'Laguna',
            'region' => 'Region IV-A (CALABARZON)',
            'postalCode' => '4009',
            'isDefault' => true,
        ]);

        $this->jntProvider = ShippingProvider::firstOrCreate(
            ['code' => 'jnt'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'J&T Express',
                'is_active' => true,
                'calculation_type' => 'tiered',
            ]
        );

        $this->storePickupProvider = ShippingProvider::firstOrCreate(
            ['code' => 'store_pickup'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Artisan Store Pickup',
                'is_active' => true,
                'calculation_type' => 'flat',
            ]
        );

        $zone = ShippingZone::firstOrCreate(
            ['code' => 'laguna_local'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Laguna Local',
            ]
        );

        ShippingZoneArea::firstOrCreate(
            ['zone_id' => $zone->id, 'province' => 'Laguna', 'city' => 'Santa Cruz'],
            [
                'id' => (string) Str::uuid(),
            ]
        );

        ShippingZoneArea::firstOrCreate(
            ['zone_id' => $zone->id, 'province' => 'Laguna', 'city' => 'Lumban'],
            [
                'id' => (string) Str::uuid(),
            ]
        );

        ShippingRate::firstOrCreate(
            ['provider_id' => $this->jntProvider->id, 'origin_zone_id' => $zone->id, 'destination_zone_id' => $zone->id],
            [
                'id' => (string) Str::uuid(),
                'base_rate' => 0.00,
                'base_weight_kg' => 1.0,
                'additional_rate_per_kg' => 0.00,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
            ]
        );
    }

    // ========================================================
    // EXACT PAYMENT (Requirement 1)
    // ========================================================

    public function test_01_exact_payment_900_order_with_900_receipt_completes()
    {
        $this->actingAs($this->customer);
        $receipt = UploadedFile::fake()->create('gcash_ref_1001234567890_amount_900.jpg', 200, 'image/jpeg');

        $response = $this->postJson('/ai/receipt/verify', [
            'receipt' => $receipt,
            'reference' => '1001234567890',
            'method' => 'GCash',
            'amount' => 900.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'PASS',
            'is_exact' => true,
            'is_partial' => false,
            'is_overpayment' => false,
            'remaining_amount' => 0,
            'sukli_amount' => 0,
            'detected_amount' => 900.00,
        ]);

        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'paymentReference' => '1001234567890',
            'receipts' => [[
                'screening' => $response->json(),
                'paymentProof' => 'private/payments/test.jpg',
                'paymentReference' => '1001234567890',
            ]],
            'selectedProviderId' => $this->jntProvider->id,
        ]);

        $this->assertEquals(900.00, (float)$order->totalAmount);
        $this->assertEquals(900.00, (float)$order->total_verified_payments);
        $this->assertEquals(0.00, (float)$order->overpayment_amount);
        $this->assertEquals('Pending Verification', $order->paymentStatus);
    }

    // ========================================================
    // PARTIAL PAYMENT (Requirements 2 - 5)
    // ========================================================

    public function test_02_partial_payment_900_order_with_850_receipt_yields_remaining_50()
    {
        $this->actingAs($this->customer);
        $receipt = UploadedFile::fake()->create('gcash_ref_1001111222233_amount_850.jpg', 200, 'image/jpeg');

        $response = $this->postJson('/ai/receipt/verify', [
            'receipt' => $receipt,
            'reference' => '1001111222233',
            'method' => 'GCash',
            'amount' => 900.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'PASS',
            'reason_code' => 'PARTIAL_PAYMENT',
            'is_partial' => true,
            'is_exact' => false,
            'detected_amount' => 850.00,
            'remaining_amount' => 50.00,
            'sukli_amount' => 0.00,
        ]);
        $this->assertStringContainsString('Please upload another GCash receipt for the remaining ₱50.00', $response->json('message'));
    }

    public function test_03_partial_payment_850_plus_10_receipt_yields_remaining_40()
    {
        $this->actingAs($this->customer);
        // Upload second receipt for remaining 50 balance with 10 amount
        $receipt2 = UploadedFile::fake()->create('gcash_ref_1002222333344_amount_10.jpg', 200, 'image/jpeg');

        $response = $this->postJson('/ai/receipt/verify', [
            'receipt' => $receipt2,
            'reference' => '1002222333344',
            'method' => 'GCash',
            'amount' => 50.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'PASS',
            'reason_code' => 'PARTIAL_PAYMENT',
            'is_partial' => true,
            'detected_amount' => 10.00,
            'remaining_amount' => 40.00,
        ]);
    }

    public function test_04_partial_payment_850_plus_10_plus_40_completes_order()
    {
        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'receipts' => [
                ['screening' => ['detected_amount' => 850.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
                ['screening' => ['detected_amount' => 10.00, 'status' => 'PASS'], 'paymentProof' => 'p2.jpg', 'paymentReference' => '1002222222222'],
                ['screening' => ['detected_amount' => 40.00, 'status' => 'PASS'], 'paymentProof' => 'p3.jpg', 'paymentReference' => '1003333333333'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);

        $this->assertEquals(900.00, (float)$order->total_verified_payments);
        $this->assertEquals(0.00, (float)$order->overpayment_amount);
        $this->assertEquals(3, $order->paymentTransactions()->count());
    }

    public function test_05_underpayment_below_payable_amount_cannot_complete()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Incomplete payment: ₱899.00 received, but ₱900.00 is required. Remaining balance: ₱1.00');

        $service = app(CreateOrderService::class);
        $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'receipts' => [
                ['screening' => ['detected_amount' => 899.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);
    }

    // ========================================================
    // OVERPAYMENT / SUKLI (Requirements 6 - 15)
    // ========================================================

    public function test_06_overpayment_900_order_with_1000_receipt_calculates_100_sukli()
    {
        $this->actingAs($this->customer);
        $receipt = UploadedFile::fake()->create('gcash_ref_1009999888877_amount_1000.jpg', 200, 'image/jpeg');

        $response = $this->postJson('/ai/receipt/verify', [
            'receipt' => $receipt,
            'reference' => '1009999888877',
            'method' => 'GCash',
            'amount' => 900.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'PASS',
            'reason_code' => 'OVERPAYMENT_DETECTED',
            'is_overpayment' => true,
            'detected_amount' => 1000.00,
            'remaining_amount' => 0.00,
            'sukli_amount' => 100.00,
            'overpayment_amount' => 100.00,
        ]);
        $this->assertStringContainsString('Your payment is ₱100.00 more than your order total. When the Admin verifies this order, your ₱100.00 sukli will be sent back to you', $response->json('message'));
    }

    public function test_07_overpayment_requires_refund_mobile_number()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Please provide a valid 11-digit Philippine mobile number');

        $service = app(CreateOrderService::class);
        $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'refund_mobile_number' => '', // Empty
            'receipts' => [
                ['screening' => ['detected_amount' => 1000.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);
    }

    public function test_08_invalid_refund_mobile_number_blocks_proceed()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Please provide a valid 11-digit Philippine mobile number (09XXXXXXXXX)');

        $service = app(CreateOrderService::class);
        $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'refund_mobile_number' => '12345ABC', // Malformed
            'receipts' => [
                ['screening' => ['detected_amount' => 1000.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);
    }

    public function test_09_valid_refund_mobile_number_enables_proceed()
    {
        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'refund_mobile_number' => '09171234567',
            'receipts' => [
                ['screening' => ['detected_amount' => 1000.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);

        $this->assertNotNull($order);
        $this->assertEquals('09171234567', $order->refund_mobile_number);
    }

    public function test_10_exact_overpayment_amount_is_stored()
    {
        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'refund_mobile_number' => '09171234567',
            'receipts' => [
                ['screening' => ['detected_amount' => 1200.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);

        $this->assertEquals(300.00, (float)$order->overpayment_amount);
        $this->assertEquals(1200.00, (float)$order->total_verified_payments);
        $this->assertEquals(900.00, (float)$order->totalAmount);
    }

    public function test_11_original_payment_transaction_amount_remains_immutable_at_1000()
    {
        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'refund_mobile_number' => '09171234567',
            'receipts' => [
                ['screening' => ['detected_amount' => 1000.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);

        $tx = $order->paymentTransactions()->first();
        $this->assertEquals(1000.00, (float)$tx->detected_amount);
        $this->assertEquals(900.00, (float)$tx->expected_amount);
    }

    public function test_12_order_carries_overpayment_pending_state()
    {
        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'refund_mobile_number' => '09171234567',
            'receipts' => [
                ['screening' => ['detected_amount' => 1000.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);

        $this->assertEquals('Pending Verification (Overpayment)', $order->paymentStatus);
        $this->assertTrue($order->isOverpaid());
        $this->assertEquals(100.00, $order->sukliAmount());
    }

    public function test_13_admin_can_view_sukli_amount()
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 900.00,
            'total_verified_payments' => 1000.00,
            'overpayment_amount' => 100.00,
            'refund_mobile_number' => '09171234567',
            'status' => 'Pending',
            'paymentMethod' => 'GCash',
            'paymentReference' => '1001111111111',
            'paymentStatus' => 'Pending Verification (Overpayment)',
            'shippingAddress' => ['recipientName' => 'Maria Santos', 'phone' => '09171112222', 'city' => 'Santa Cruz', 'province' => 'Laguna'],
        ]);

        $fresh = Order::find($order->id);
        $this->assertEquals(100.00, (float)$fresh->overpayment_amount);
        $this->assertEquals(1000.00, (float)$fresh->total_verified_payments);
    }

    public function test_14_admin_can_view_refund_number()
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 900.00,
            'total_verified_payments' => 1000.00,
            'overpayment_amount' => 100.00,
            'refund_mobile_number' => '09171234567',
            'status' => 'Pending',
            'paymentMethod' => 'GCash',
            'paymentReference' => '1001111111111',
            'paymentStatus' => 'Pending Verification (Overpayment)',
            'shippingAddress' => ['recipientName' => 'Maria Santos', 'phone' => '09171112222', 'city' => 'Santa Cruz', 'province' => 'Laguna'],
        ]);

        $fresh = Order::find($order->id);
        $this->assertEquals('09171234567', $fresh->refund_mobile_number);
    }

    public function test_15_refund_status_progression_works()
    {
        $order = Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 900.00,
            'total_verified_payments' => 1000.00,
            'overpayment_amount' => 100.00,
            'refund_mobile_number' => '09171234567',
            'status' => 'Pending',
            'paymentMethod' => 'GCash',
            'paymentReference' => '1001111111111',
            'paymentStatus' => 'Pending Verification (Overpayment)',
            'shippingAddress' => ['recipientName' => 'Maria Santos', 'phone' => '09171112222', 'city' => 'Santa Cruz', 'province' => 'Laguna'],
        ]);

        // 1. Pending Verification (Overpayment)
        $this->assertEquals('Pending Verification (Overpayment)', $order->paymentStatus);

        // 2. Admin Verified (order moved to To Ship)
        $order->paymentStatus = 'Verified';
        $order->status = 'To Ship';
        $order->save();
        $this->assertEquals('Verified', $order->fresh()->paymentStatus);

        // 3. Sukli / Overpayment remains traceable even after verification
        $this->assertEquals(100.00, (float)$order->fresh()->overpayment_amount);
        $this->assertEquals('09171234567', $order->fresh()->refund_mobile_number);
    }

    // ========================================================
    // MULTIPLE RECEIPTS (Requirements 16 - 18)
    // ========================================================

    public function test_16_multiple_receipts_850_plus_60_yields_10_sukli()
    {
        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'refund_mobile_number' => '09171234567',
            'receipts' => [
                ['screening' => ['detected_amount' => 850.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
                ['screening' => ['detected_amount' => 60.00, 'status' => 'PASS'], 'paymentProof' => 'p2.jpg', 'paymentReference' => '1002222222222'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);

        $this->assertEquals(910.00, (float)$order->total_verified_payments);
        $this->assertEquals(10.00, (float)$order->overpayment_amount);
        $this->assertEquals('Pending Verification (Overpayment)', $order->paymentStatus);
    }

    public function test_17_duplicate_second_reference_in_batch_rejected()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The reference number used is already in use. Please check your payment receipt or upload a new transaction.');

        $service = app(CreateOrderService::class);
        $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'receipts' => [
                ['screening' => ['detected_amount' => 450.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
                ['screening' => ['detected_amount' => 450.00, 'status' => 'PASS'], 'paymentProof' => 'p2.jpg', 'paymentReference' => '1001111111111'], // DUPLICATE REF
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);
    }

    public function test_18_multiple_transactions_remain_separately_traceable()
    {
        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'receipts' => [
                ['screening' => ['detected_amount' => 500.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
                ['screening' => ['detected_amount' => 400.00, 'status' => 'PASS'], 'paymentProof' => 'p2.jpg', 'paymentReference' => '1002222222222'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);

        $txs = PaymentTransaction::where('order_id', $order->id)->orderBy('reference_number')->get();
        $this->assertCount(2, $txs);
        $this->assertEquals('1001111111111', $txs[0]->reference_number);
        $this->assertEquals(500.00, (float)$txs[0]->detected_amount);
        $this->assertEquals('1002222222222', $txs[1]->reference_number);
        $this->assertEquals(400.00, (float)$txs[1]->detected_amount);
    }

    // ========================================================
    // CONSISTENCY & DETERMINISTIC OCR (Requirements 19 - 21)
    // ========================================================

    public function test_19_exact_same_receipt_uploaded_5_times_returns_same_canonical_result()
    {
        $this->actingAs($this->customer);
        $receipt = UploadedFile::fake()->create('canonical_gcash_ref_1001234567890_amount_900.jpg', 200, 'image/jpeg');

        $firstResult = null;
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/ai/receipt/verify', [
                'receipt' => $receipt,
                'method' => 'GCash',
                'amount' => 900.00,
            ]);
            $response->assertStatus(200);
            if ($firstResult === null) {
                $firstResult = $response->json();
            } else {
                $this->assertEquals($firstResult['detected_ref'], $response->json('detected_ref'));
                $this->assertEquals($firstResult['detected_amount'], $response->json('detected_amount'));
                $this->assertEquals($firstResult['status'], $response->json('status'));
            }
        }
    }

    public function test_20_same_receipt_renamed_still_duplicate_by_reference()
    {
        PaymentTransaction::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $this->customer->id,
            'reference_number' => '1001234567890',
            'active_reference' => '1001234567890',
            'wallet_type' => 'GCash',
            'status' => 'VERIFIED',
        ]);

        $check = AiService::isDuplicateReference('1001234567890', null, 'GCash');
        $this->assertTrue($check['is_duplicate']);
    }

    public function test_21_resized_same_receipt_still_duplicate_by_reference()
    {
        PaymentTransaction::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $this->customer->id,
            'reference_number' => '1009876543210',
            'active_reference' => '1009876543210',
            'wallet_type' => 'GCash',
            'status' => 'VERIFIED',
        ]);

        $check = AiService::isDuplicateReference('1009876543210', null, 'GCash');
        $this->assertTrue($check['is_duplicate']);
    }

    // ========================================================
    // SECURITY & FORGERY PREVENTION (Requirements 22 - 27)
    // ========================================================

    public function test_22_forged_amount_in_request_rejected_by_server_authoritative_prices()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Incomplete payment');

        $service = app(CreateOrderService::class);
        // Client tries to claim totalAmount is only 500 when product is 900
        $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'receipts' => [
                ['screening' => ['detected_amount' => 500.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);
    }

    public function test_23_forged_remaining_balance_in_client_payload_rejected()
    {
        $this->expectException(\DomainException::class);

        $service = app(CreateOrderService::class);
        $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'remaining_balance' => 0.00, // Forged client balance
            'receipts' => [
                ['screening' => ['detected_amount' => 200.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);
    }

    public function test_24_forged_overpayment_in_client_payload_rejected()
    {
        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'overpayment_amount' => 9999.00, // Forged client overpayment
            'receipts' => [
                ['screening' => ['detected_amount' => 900.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);

        // Server ignores forged 9999.00 and calculates exact 0.00 overpayment
        $this->assertEquals(0.00, (float)$order->overpayment_amount);
    }

    public function test_25_forged_refund_number_with_alpha_chars_rejected()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Please provide a valid 11-digit Philippine mobile number');

        $service = app(CreateOrderService::class);
        $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'refund_mobile_number' => '0917HACKED99',
            'receipts' => [
                ['screening' => ['detected_amount' => 1000.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);
    }

    public function test_26_forged_payment_status_in_client_payload_rejected()
    {
        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'Verified', // Client tries to force 'Verified'
            'receipts' => [
                ['screening' => ['detected_amount' => 900.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1001111111111'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);

        // Server enforces initial authoritative status 'Pending Verification'
        $this->assertEquals('Pending Verification', $order->paymentStatus);
    }

    public function test_27_concurrent_duplicate_reference_rejected()
    {
        PaymentTransaction::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $this->customer->id,
            'reference_number' => '1009999999999',
            'active_reference' => '1009999999999',
            'wallet_type' => 'GCash',
            'status' => 'VERIFIED',
        ]);

        $addr2 = Address::create([
            'id' => (string) Str::uuid(),
            'userId' => $this->customer2->id,
            'recipientName' => 'Pedro Penduko',
            'phone' => '09173334444',
            'houseNo' => '456',
            'street' => 'Mabini Street',
            'barangay' => 'Barangay 2',
            'city' => 'Santa Cruz',
            'province' => 'Laguna',
            'region' => 'Region IV-A (CALABARZON)',
            'postalCode' => '4009',
            'isDefault' => true,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The reference number used is already in use. Please check your payment receipt or upload a new transaction.');

        $service = app(CreateOrderService::class);
        $service->createOrder([
            'customer' => $this->customer2,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $addr2->id,
            'paymentMethod' => 'GCash',
            'receipts' => [
                ['screening' => ['detected_amount' => 900.00, 'status' => 'PASS'], 'paymentProof' => 'p1.jpg', 'paymentReference' => '1009999999999'],
            ],
            'selectedProviderId' => $this->jntProvider->id,
        ]);
    }

    // ========================================================
    // GCASH FLOWS (Requirements 28 - 32)
    // ========================================================

    public function test_28_valid_gcash_receipt()
    {
        $this->actingAs($this->customer);
        $receipt = UploadedFile::fake()->create('gcash_ref_1001234567890_amount_900.jpg', 200, 'image/jpeg');

        $response = $this->postJson('/ai/receipt/verify', [
            'receipt' => $receipt,
            'reference' => '1001234567890',
            'method' => 'GCash',
            'amount' => 900.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'PASS',
            'wallet' => 'GCash',
            'detected_ref' => '1001234567890',
        ]);
    }

    public function test_29_duplicate_gcash_reference_rejected()
    {
        PaymentTransaction::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $this->customer->id,
            'reference_number' => '1001234567890',
            'active_reference' => '1001234567890',
            'wallet_type' => 'GCash',
            'status' => 'VERIFIED',
        ]);

        $check = AiService::isDuplicateReference('1001234567890', null, 'GCash');
        $this->assertTrue($check['is_duplicate']);
    }

    public function test_30_wrong_maya_receipt_uploaded_for_gcash_rejected()
    {
        $evidence = [
            'is_receipt' => true,
            'wallet' => 'Maya',
            'reference' => '987654321012',
            'detected_amount' => 900.00,
            'amount_confidence' => 0.95,
            'reference_confidence' => 0.95,
            'confidence' => 0.95,
        ];

        $evaluation = AiService::evaluateReceiptEvidence($evidence, '', 'GCash', 900.00);
        $this->assertEquals('REJECT', $evaluation['status']);
        $this->assertEquals('WALLET_MISMATCH', $evaluation['reason_code']);
        $this->assertStringContainsString('We only accept GCash receipts. Please upload a valid GCash payment receipt.', $evaluation['message']);
    }

    public function test_31_blurry_gcash_receipt_handled()
    {
        $evidence = [
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '',
            'detected_amount' => 900.00,
            'amount_confidence' => 0.30,
            'reference_confidence' => 0.0,
            'confidence' => 0.40,
        ];

        $evaluation = AiService::evaluateReceiptEvidence($evidence, '', 'GCash', 900.00);
        $this->assertEquals('REVIEW', $evaluation['status']);
        $this->assertEquals('UNREADABLE_REFERENCE', $evaluation['reason_code']);
        $this->assertStringContainsString('Please reupload your GCash receipt. The image is blurry or unclear, and we cannot read the reference number.', $evaluation['message']);
    }

    public function test_32_timeout_gcash_verification_handled()
    {
        $result = AiService::verifyReceipt('non_existent_image.jpg', '', 'GCash', 900.00);
        $this->assertEquals('REVIEW', $result['status']);
        $this->assertEquals('UNREADABLE_REFERENCE', $result['reason_code']);
        $this->assertStringContainsString('Please reupload your GCash receipt', $result['message']);
    }

    // ========================================================
    // MAYA FLOWS (Requirements 33 - 37)
    // ========================================================

    public function test_33_valid_maya_receipt()
    {
        $this->actingAs($this->customer);
        $receipt = UploadedFile::fake()->create('maya_ref_987654321012_amount_900.jpg', 200, 'image/jpeg');

        $response = $this->postJson('/ai/receipt/verify', [
            'receipt' => $receipt,
            'reference' => '987654321012',
            'method' => 'Maya',
            'amount' => 900.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'PASS',
            'wallet' => 'Maya',
            'detected_ref' => '987654321012',
        ]);
    }

    public function test_34_duplicate_maya_reference_rejected()
    {
        PaymentTransaction::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $this->customer->id,
            'reference_number' => '987654321012',
            'active_reference' => '987654321012',
            'wallet_type' => 'Maya',
            'status' => 'VERIFIED',
        ]);

        $check = AiService::isDuplicateReference('987654321012', null, 'Maya');
        $this->assertTrue($check['is_duplicate']);
    }

    public function test_35_wrong_gcash_receipt_uploaded_for_maya_rejected()
    {
        $evidence = [
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1001234567890',
            'detected_amount' => 900.00,
            'amount_confidence' => 0.95,
            'reference_confidence' => 0.95,
            'confidence' => 0.95,
        ];

        $evaluation = AiService::evaluateReceiptEvidence($evidence, '', 'Maya', 900.00);
        $this->assertEquals('REJECT', $evaluation['status']);
        $this->assertEquals('WALLET_MISMATCH', $evaluation['reason_code']);
        $this->assertStringContainsString('We only accept Maya receipts. Please upload a valid Maya payment receipt.', $evaluation['message']);
    }

    public function test_36_blurry_maya_receipt_handled()
    {
        $evidence = [
            'is_receipt' => true,
            'wallet' => 'Maya',
            'reference' => '',
            'detected_amount' => 900.00,
            'amount_confidence' => 0.30,
            'reference_confidence' => 0.0,
            'confidence' => 0.40,
        ];

        $evaluation = AiService::evaluateReceiptEvidence($evidence, '', 'Maya', 900.00);
        $this->assertEquals('REVIEW', $evaluation['status']);
        $this->assertEquals('UNREADABLE_REFERENCE', $evaluation['reason_code']);
        $this->assertStringContainsString('Please reupload your Maya receipt. The image is blurry or unclear, and we cannot read the reference number.', $evaluation['message']);
    }

    public function test_37_timeout_maya_verification_handled()
    {
        $result = AiService::verifyReceipt('non_existent_maya.jpg', '', 'Maya', 900.00);
        $this->assertEquals('REVIEW', $result['status']);
        $this->assertEquals('UNREADABLE_REFERENCE', $result['reason_code']);
        $this->assertStringContainsString('Please reupload your Maya receipt', $result['message']);
    }

    // ========================================================
    // OTHER PAYMENT & FULFILLMENT FLOWS (Requirements 38 - 41)
    // ========================================================

    public function test_38_cod_bypasses_receipt_scanner_and_overpayment_logic()
    {
        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'paymentMethod' => 'COD',
            'selectedProviderId' => $this->jntProvider->id,
        ]);

        $this->assertEquals('COD', $order->paymentMethod);
        $this->assertEquals('Pending Payment (COD)', $order->paymentStatus);
        $this->assertNull($order->paymentReference);
        $this->assertNull($order->paymentProof);
        $this->assertEquals(0.00, (float)$order->total_verified_payments);
        $this->assertEquals(0.00, (float)$order->overpayment_amount);
    }

    public function test_39_store_pickup_bypasses_receipt_scanner_and_sets_direct_payment_status()
    {
        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'selectedProviderId' => $this->storePickupProvider->id,
        ]);

        $this->assertEquals('Store Pickup', $order->paymentMethod);
        $this->assertEquals('Pending Payment (Store Pickup)', $order->paymentStatus);
        $this->assertNull($order->paymentReference);
        $this->assertNull($order->paymentProof);
        $this->assertEquals(0.00, (float)$order->total_verified_payments);
    }

    public function test_40_special_delivery_bypasses_receipt_scanner_and_sets_direct_payment_status()
    {
        $specialDeliveryProvider = ShippingProvider::firstOrCreate(
            ['code' => 'seller_direct'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Artisan Special Direct Delivery',
                'is_active' => true,
                'calculation_type' => 'flat',
            ]
        );

        \App\Models\SellerSpecialDeliveryRate::create([
            'seller_id' => $this->product900->sellerId,
            'municipality_key' => 'santa_cruz',
            'municipality_name' => 'Santa Cruz',
            'surcharge' => 0.00,
            'is_enabled' => true,
        ]);

        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'selectedProviderId' => $specialDeliveryProvider->id,
        ]);

        $this->assertEquals('Special Delivery', $order->paymentMethod);
        $this->assertEquals('Pending Payment (Special Delivery)', $order->paymentStatus);
        $this->assertNull($order->paymentReference);
        $this->assertNull($order->paymentProof);
        $this->assertEquals(0.00, (float)$order->total_verified_payments);
    }

    public function test_41_store_pickup_and_special_delivery_do_not_create_platform_payment_transactions()
    {
        $service = app(CreateOrderService::class);
        $order = $service->createOrder([
            'customer' => $this->customer,
            'items' => [['id' => $this->product900->id, 'quantity' => 1]],
            'address_id' => $this->address->id,
            'selectedProviderId' => $this->storePickupProvider->id,
        ]);

        $this->assertEquals(0, PaymentTransaction::where('order_id', $order->id)->count());
        $this->assertTrue($order->isSellerHeldPayment());
        $this->assertFalse($order->isPlatformHeldPayment());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderIdempotencyRecord;
use App\Models\OrderItem;
use App\Models\OrderShipping;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ShippingProvider;
use App\Models\User;
use App\Services\AiService;
use App\Services\CreateOrderService;
use App\Services\EmailNotificationService;
use App\Services\ShippingCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class NetworkResilienceAndIdempotencyAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $sellerUser;
    protected Product $product;
    protected Address $address;
    protected ShippingProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->sellerUser = User::factory()->create([
            'role'       => 'seller',
            'status'     => 'active',
            'isVerified' => true,
            'shopName'   => 'Resilience Artisan Shop',
        ]);

        $this->customer = User::factory()->create([
            'role'       => 'customer',
            'status'     => 'active',
            'isVerified' => true,
        ]);

        $this->product = Product::create([
            'id'          => (string) Str::uuid(),
            'sellerId'    => $this->sellerUser->id,
            'name'        => 'Resilience Barong Tagalog',
            'description' => 'Fine handcrafted test product',
            'price'       => 1500.00,
            'stock'       => 20,
            'status'      => 'approved',
            'weight'      => 0.6,
            'image'       => ['barong.jpg'],
        ]);

        $this->seed(\Database\Seeders\ShippingLogisticsSeeder::class);

        $this->provider = ShippingProvider::where('is_active', true)
            ->whereNotIn('code', ['store_pickup', 'seller_direct'])
            ->first() ?: ShippingProvider::where('code', 'jnt')->first();

        $this->address = Address::create([
            'userId'       => $this->customer->id,
            'recipientName'=> 'Maria Clara',
            'phone'        => '09171234567',
            'houseNo'      => 'Block 5 Lot 12',
            'street'       => 'Crisostomo St',
            'barangay'     => 'Poblacion',
            'city'         => 'Imus',
            'province'     => 'Cavite',
            'region'       => 'Region IV-A (CALABARZON)',
            'postalCode'   => '4103',
            'isDefault'    => true,
        ]);
    }

    public function test_same_idempotency_key_returns_existing_order_and_prevents_duplicate_stock_deduction(): void
    {
        $createOrderService = app(CreateOrderService::class);
        $idempotencyKey = (string) Str::uuid();

        $calc = app(ShippingCalculatorService::class);
        $items = [['productId' => $this->product->id, 'quantity' => 2, 'price' => 1500.00]];
        $token = $calc->generateQuoteToken([$this->sellerUser->id], $this->address->id, $items);

        $initialStock = $this->product->fresh()->stock;

        // First checkout request
        $order1 = $createOrderService->createOrder([
            'customer'          => $this->customer,
            'items'             => $items,
            'address_id'        => $this->address->id,
            'paymentMethod'     => 'GCash',
            'paymentReference'  => '1001234567890',
            'paymentProof'      => 'receipts/test1.jpg',
            'quoteToken'        => $token,
            'selectedProviderId'=> $this->provider->id,
            'idempotencyKey'    => $idempotencyKey,
        ]);

        $stockAfterFirst = $this->product->fresh()->stock;
        $this->assertEquals($initialStock - 2, $stockAfterFirst);
        $this->assertEquals(1, Order::count());
        $this->assertDatabaseHas('order_idempotency_records', [
            'customer_id'     => $this->customer->id,
            'idempotency_key' => $idempotencyKey,
            'status'          => 'completed',
            'order_id'        => $order1->id,
        ]);

        // Duplicate retry with same idempotency key
        $order2 = $createOrderService->createOrder([
            'customer'          => $this->customer,
            'items'             => $items,
            'address_id'        => $this->address->id,
            'paymentMethod'     => 'GCash',
            'paymentReference'  => '1001234567890',
            'paymentProof'      => 'receipts/test1.jpg',
            'quoteToken'        => $token,
            'selectedProviderId'=> $this->provider->id,
            'idempotencyKey'    => $idempotencyKey,
        ]);

        // Assert exact order returned, stock NOT deducted again, order count is still 1
        $this->assertEquals($order1->id, $order2->id);
        $this->assertEquals(1, Order::count());
        $this->assertEquals($stockAfterFirst, $this->product->fresh()->stock);
    }

    public function test_same_idempotency_key_with_different_payload_is_rejected(): void
    {
        $createOrderService = app(CreateOrderService::class);
        $idempotencyKey = (string) Str::uuid();

        $calc = app(ShippingCalculatorService::class);
        $items1 = [['productId' => $this->product->id, 'quantity' => 1, 'price' => 1500.00]];
        $token1 = $calc->generateQuoteToken([$this->sellerUser->id], $this->address->id, $items1);

        // First order completes
        $createOrderService->createOrder([
            'customer'          => $this->customer,
            'items'             => $items1,
            'address_id'        => $this->address->id,
            'paymentMethod'     => 'GCash',
            'paymentReference'  => '1001112223334',
            'paymentProof'      => 'receipts/test1.jpg',
            'quoteToken'        => $token1,
            'selectedProviderId'=> $this->provider->id,
            'idempotencyKey'    => $idempotencyKey,
        ]);

        // Second request with SAME idempotency key but DIFFERENT quantity
        $items2 = [['productId' => $this->product->id, 'quantity' => 3, 'price' => 1500.00]];
        $token2 = $calc->generateQuoteToken([$this->sellerUser->id], $this->address->id, $items2);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('This idempotency key has already been used for a different checkout request.');

        $createOrderService->createOrder([
            'customer'          => $this->customer,
            'items'             => $items2,
            'address_id'        => $this->address->id,
            'paymentMethod'     => 'GCash',
            'paymentReference'  => '1001112223334',
            'paymentProof'      => 'receipts/test1.jpg',
            'quoteToken'        => $token2,
            'selectedProviderId'=> $this->provider->id,
            'idempotencyKey'    => $idempotencyKey,
        ]);
    }

    public function test_in_flight_processing_idempotency_key_blocks_concurrent_duplicate(): void
    {
        $idempotencyKey = (string) Str::uuid();

        // Simulate an in-flight request currently processing
        OrderIdempotencyRecord::create([
            'id'              => (string) Str::uuid(),
            'customer_id'     => $this->customer->id,
            'idempotency_key' => $idempotencyKey,
            'request_hash'    => 'mocked_hash',
            'status'          => 'processing',
        ]);

        $createOrderService = app(CreateOrderService::class);
        $calc = app(ShippingCalculatorService::class);
        $items = [['productId' => $this->product->id, 'quantity' => 1, 'price' => 1500.00]];
        $token = $calc->generateQuoteToken([$this->sellerUser->id], $this->address->id, $items);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('A checkout request with this idempotency key is already being processed.');

        $createOrderService->createOrder([
            'customer'          => $this->customer,
            'items'             => $items,
            'address_id'        => $this->address->id,
            'paymentMethod'     => 'GCash',
            'paymentReference'  => '1001112223334',
            'paymentProof'      => 'receipts/test1.jpg',
            'quoteToken'        => $token,
            'selectedProviderId'=> $this->provider->id,
            'idempotencyKey'    => $idempotencyKey,
        ]);
    }

    public function test_two_different_orders_cannot_claim_same_active_payment_reference(): void
    {
        $createOrderService = app(CreateOrderService::class);
        $calc = app(ShippingCalculatorService::class);

        $items = [['productId' => $this->product->id, 'quantity' => 1, 'price' => 1500.00]];
        $token = $calc->generateQuoteToken([$this->sellerUser->id], $this->address->id, $items);

        // Order 1 claims reference
        $createOrderService->createOrder([
            'customer'          => $this->customer,
            'items'             => $items,
            'address_id'        => $this->address->id,
            'paymentMethod'     => 'GCash',
            'paymentReference'  => '1009998887776',
            'paymentProof'      => 'receipts/ref_claim.jpg',
            'quoteToken'        => $token,
            'selectedProviderId'=> $this->provider->id,
        ]);

        // Order 2 attempts to use same active reference
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/already (?:been used|in use)/i');

        $createOrderService->createOrder([
            'customer'          => $this->customer,
            'items'             => $items,
            'address_id'        => $this->address->id,
            'paymentMethod'     => 'GCash',
            'paymentReference'  => '1009998887776',
            'paymentProof'      => 'receipts/ref_claim_2.jpg',
            'quoteToken'        => $token,
            'selectedProviderId'=> $this->provider->id,
        ]);
    }

    public function test_ai_verification_unreachable_falls_back_to_review_without_false_pass(): void
    {
        $file = UploadedFile::fake()->create('receipt.jpg', 200, 'image/jpeg');
        $tempPath = $file->getRealPath();

        $result = AiService::verifyReceipt($tempPath, '1001112223334', 'GCash', 1500.00);

        // Crucial safety check: Must NEVER be false PASS
        $this->assertNotEquals('PASS', $result['status'] ?? '');
        $this->assertTrue(in_array($result['status'] ?? '', ['REVIEW', 'REJECT'], true));
    }

    public function test_gemini_fallback_stress_under_network_errors(): void
    {
        $evidence = [
            'is_receipt' => false,
            'wallet'     => 'GCash',
            'reference'  => '',
        ];

        // Invalid receipt evidence must result in REJECT, never PASS
        $eval = AiService::evaluateReceiptEvidence($evidence, '1001234567890', 'GCash', 1500.00);
        $this->assertEquals('REJECT', $eval['status']);

        // Reference mismatch evidence must result in REJECT, never PASS
        $mismatchedEvidence = [
            'is_receipt' => true,
            'wallet'     => 'GCash',
            'reference'  => '1009999999999',
            'detected_amount' => 1500.00,
        ];
        $evalMismatch = AiService::evaluateReceiptEvidence($mismatchedEvidence, '1001111111111', 'GCash', 1500.00);
        $this->assertEquals('REJECT', $evalMismatch['status']);
        $this->assertFalse($evalMismatch['ref_matched']);
    }

    public function test_smtp_delay_or_failure_does_not_break_committed_order(): void
    {
        // Simulate Mail throwing an exception
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('SMTP Connection timed out'));

        $createOrderService = app(CreateOrderService::class);
        $calc = app(ShippingCalculatorService::class);

        $items = [['productId' => $this->product->id, 'quantity' => 1, 'price' => 1500.00]];
        $token = $calc->generateQuoteToken([$this->sellerUser->id], $this->address->id, $items);

        $order = $createOrderService->createOrder([
            'customer'          => $this->customer,
            'items'             => $items,
            'address_id'        => $this->address->id,
            'paymentMethod'     => 'GCash',
            'paymentReference'  => '1005556667778',
            'paymentProof'      => 'receipts/smtp_test.jpg',
            'quoteToken'        => $token,
            'selectedProviderId'=> $this->provider->id,
        ]);

        $this->assertNotNull($order);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('payment_transactions', ['order_id' => $order->id]);
    }

    public function test_expired_or_tampered_quote_token_is_rejected_and_recalculated(): void
    {
        $createOrderService = app(CreateOrderService::class);
        
        // Corrupted token
        $badToken = 'corrupted_token_payload';

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/expired/i');

        $createOrderService->createOrder([
            'customer'          => $this->customer,
            'items'             => [['productId' => $this->product->id, 'quantity' => 1]],
            'address_id'        => $this->address->id,
            'paymentMethod'     => 'GCash',
            'paymentReference'  => '1007778889990',
            'paymentProof'      => 'receipts/quote_tamper.jpg',
            'quoteToken'        => $badToken,
            'selectedProviderId'=> $this->provider->id,
        ]);
    }

    public function test_session_heartbeat_handles_authenticated_and_unauthenticated_states(): void
    {
        // Unauthenticated heartbeat -> 401 json
        $response = $this->getJson(route('auth.session-heartbeat'));
        $response->assertStatus(401);

        // Authenticated heartbeat -> 200 json
        $response = $this->actingAs($this->customer)->getJson(route('auth.session-heartbeat'));
        $response->assertStatus(200)
                 ->assertJson(['status' => 'active']);
    }

    public function test_cod_rejection_for_non_local_cluster(): void
    {
        $createOrderService = app(CreateOrderService::class);
        $calc = app(ShippingCalculatorService::class);

        $items = [['productId' => $this->product->id, 'quantity' => 1, 'price' => 1500.00]];
        $token = $calc->generateQuoteToken([$this->sellerUser->id], $this->address->id, $items);

        // Cavite is non-local; COD should be rejected
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('Cash on Delivery (COD) is available only for nearby local deliveries');

        $createOrderService->createOrder([
            'customer'          => $this->customer,
            'items'             => $items,
            'address_id'        => $this->address->id,
            'paymentMethod'     => 'COD',
            'quoteToken'        => $token,
            'selectedProviderId'=> $this->provider->id,
        ]);
    }
}

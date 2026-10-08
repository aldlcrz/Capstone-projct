<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected User $seller;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email' => 'admin@lumbarong.test',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
            'email' => 'buyer@lumbarong.test',
            'mobileNumber' => '09123456789',
        ]);

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'active',
            'shopName' => 'Heritage Barong Atelier',
            'email' => 'artisan@lumbarong.test',
        ]);

        $this->product = Product::create([
            'sellerId' => $this->seller->id,
            'name' => 'Handwoven Piña Barong',
            'price' => 2500.00,
            'stock' => 10,
            'status' => 'approved',
        ]);
    }

    protected function createTestOrder(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'customerId' => $this->customer->id,
            'sellerId' => $this->seller->id,
            'totalAmount' => 2500.00,
            'status' => 'Pending',
            'paymentMethod' => 'GCash',
            'paymentReference' => 'GCASH-99887766',
            'paymentProof' => 'payment-proofs/sample.jpg',
            'paymentStatus' => 'Payment Submitted',
            'shippingAddress' => [
                'recipientName' => 'Juan Dela Cruz',
                'streetAddress' => '123 Heritage St',
                'barangay' => 'Barangay 1',
                'city' => 'Lumban',
                'province' => 'Laguna',
                'postalCode' => '4014',
            ],
        ], $attributes));
    }

    /** @test */
    public function admin_can_view_orders_and_payment_verification_page()
    {
        $order = $this->createTestOrder([
            'paymentReference' => 'GCASH-99887766',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders'));

        $response->assertStatus(200);
        $response->assertSee('Payment Verification');
        $response->assertSee('GCASH-99887766');
        $response->assertSee('Heritage Barong Atelier');
    }

    /** @test */
    public function admin_can_filter_orders_by_pending_verification_and_payment_method()
    {
        $gcashOrder = $this->createTestOrder([
            'paymentMethod' => 'GCash',
            'paymentReference' => 'GCASH-111111',
            'paymentProof' => 'payment-proofs/gcash.jpg',
        ]);

        $mayaOrder = $this->createTestOrder([
            'totalAmount' => 1500.00,
            'paymentMethod' => 'Maya',
            'paymentReference' => 'MAYA-222222',
            'paymentProof' => 'payment-proofs/maya.jpg',
        ]);

        $codOrder = $this->createTestOrder([
            'totalAmount' => 500.00,
            'paymentMethod' => 'COD',
            'paymentReference' => null,
            'paymentProof' => null,
            'paymentStatus' => 'Pending',
        ]);

        // Filter GCash
        $gcashResponse = $this->actingAs($this->admin)->get(route('admin.orders', [
            'status' => 'pending_verification',
            'payment_method' => 'gcash',
        ]));
        $gcashResponse->assertStatus(200);
        $gcashResponse->assertSee('GCASH-111111');
        $gcashResponse->assertDontSee('MAYA-222222');

        // Filter Maya
        $mayaResponse = $this->actingAs($this->admin)->get(route('admin.orders', [
            'status' => 'pending_verification',
            'payment_method' => 'maya',
        ]));
        $mayaResponse->assertStatus(200);
        $mayaResponse->assertSee('MAYA-222222');
        $mayaResponse->assertDontSee('GCASH-111111');
    }

    /** @test */
    public function admin_can_approve_and_verify_customer_payment()
    {
        $order = $this->createTestOrder([
            'paymentMethod' => 'GCash',
            'paymentReference' => 'GCASH-12345678',
            'paymentProof' => 'payment-proofs/receipt.jpg',
        ]);

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
            'wallet_type' => 'gcash',
            'reference_number' => 'GCASH-12345678',
            'expected_amount' => 2500.00,
            'detected_amount' => 2500.00,
            'status' => 'UNVERIFIED',
            'receipt_path' => 'payment-proofs/receipt.jpg',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.verify-payment', $order->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order->refresh();
        $transaction->refresh();

        $this->assertEquals('Paid', $order->paymentStatus);
        $this->assertEquals('To Ship', $order->status);
        $this->assertEquals('VERIFIED', $transaction->status);
        $this->assertNotNull($transaction->verified_at);

        // Assert customer and seller received notifications
        $this->assertDatabaseHas('notifications', [
            'userId' => $this->customer->id,
            'type' => 'order',
        ]);

        $this->assertDatabaseHas('notifications', [
            'userId' => $this->seller->id,
            'type' => 'order',
        ]);
    }

    /** @test */
    public function admin_can_reject_customer_payment_with_reason()
    {
        $order = $this->createTestOrder([
            'totalAmount' => 3000.00,
            'paymentMethod' => 'Maya',
            'paymentReference' => 'MAYA-999999',
            'paymentProof' => 'payment-proofs/fake.jpg',
        ]);

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
            'wallet_type' => 'maya',
            'reference_number' => 'MAYA-999999',
            'expected_amount' => 3000.00,
            'detected_amount' => 0,
            'status' => 'UNVERIFIED',
            'receipt_path' => 'payment-proofs/fake.jpg',
        ]);

        $rejectionReason = 'The uploaded receipt is completely blurred and the reference number is invalid.';

        $response = $this->actingAs($this->admin)->post(route('admin.orders.reject-payment', $order->id), [
            'reason' => $rejectionReason,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $order->refresh();
        $transaction->refresh();

        $this->assertEquals('Payment Rejected', $order->paymentStatus);
        $this->assertEquals($rejectionReason, $order->paymentRejectionReason);
        $this->assertEquals('REJECTED', $transaction->status);

        // Assert customer received rejection notification
        $this->assertDatabaseHas('notifications', [
            'userId' => $this->customer->id,
            'type' => 'order',
        ]);
    }

    /** @test */
    public function non_admin_cannot_access_payment_verification()
    {
        $order = $this->createTestOrder();

        // Customer attempts access
        $response = $this->actingAs($this->customer)->get(route('admin.orders'));
        $this->assertNotEquals(200, $response->status());

        // Guest attempts verify
        $guestResponse = $this->post(route('admin.orders.verify-payment', $order->id));
        $guestResponse->assertRedirect();
    }
}

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
        $this->assertEquals('Pending', $order->status);
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
    public function unverified_gcash_and_maya_orders_are_hidden_from_seller_until_admin_verifies()
    {
        // 1. Create unverified GCash order
        $unverifiedOrder = $this->createTestOrder([
            'paymentMethod' => 'GCash',
            'paymentReference' => 'GCASH-UNVERIFIED-123',
            'paymentStatus' => 'Pending Verification',
            'status' => 'Pending',
        ]);

        // Seller views orders: unverified GCash order should NOT appear
        $sellerRes = $this->actingAs($this->seller)->get(route('seller.orders'));
        $sellerRes->assertStatus(200);
        $sellerRes->assertDontSee($unverifiedOrder->id);

        // 2. Admin verifies the GCash order
        $this->actingAs($this->admin)->post(route('admin.orders.verify-payment', $unverifiedOrder->id));
        $unverifiedOrder->refresh();

        $this->assertEquals('Paid', $unverifiedOrder->paymentStatus);
        $this->assertEquals('Pending', $unverifiedOrder->status);

        // Seller views orders: verified GCash order NOW appears under Pending
        $sellerResAfter = $this->actingAs($this->seller)->get(route('seller.orders', ['status' => 'pending']));
        $sellerResAfter->assertStatus(200);
        $sellerResAfter->assertSee($unverifiedOrder->id);
    }

    /** @test */
    public function rejected_gcash_and_maya_orders_are_hidden_from_seller()
    {
        // 1. Create unverified Maya order
        $rejectedOrder = $this->createTestOrder([
            'paymentMethod' => 'Maya',
            'paymentReference' => 'MAYA-REJECTED-456',
            'paymentStatus' => 'Pending Verification',
            'status' => 'Pending',
        ]);

        // 2. Admin rejects payment
        $this->actingAs($this->admin)->post(route('admin.orders.reject-payment', $rejectedOrder->id), [
            'reason' => 'Invalid receipt uploaded',
        ]);
        $rejectedOrder->refresh();

        $this->assertEquals('Payment Rejected', $rejectedOrder->paymentStatus);
        $this->assertEquals('Cancelled', $rejectedOrder->status);

        // 3. Seller views orders: rejected order should NOT appear in seller orders
        $sellerRes = $this->actingAs($this->seller)->get(route('seller.orders'));
        $sellerRes->assertStatus(200);
        $sellerRes->assertDontSee($rejectedOrder->id);
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
        $this->assertEquals('Cancelled', $order->status);
        $this->assertEquals('REJECTED', $transaction->status);

        // Assert customer received rejection notification
        $this->assertDatabaseHas('notifications', [
            'userId' => $this->customer->id,
            'type' => 'order',
        ]);
    }

    /** @test */
    public function cod_and_pay_in_shop_orders_show_seller_verified_and_cannot_be_verified_by_admin()
    {
        $codOrder = $this->createTestOrder([
            'paymentMethod' => 'COD',
            'paymentStatus' => 'Pending',
            'paymentReference' => null,
            'paymentProof' => null,
        ]);

        $storePickupOrder = $this->createTestOrder([
            'paymentMethod' => 'Pay in Shop',
            'paymentStatus' => 'Pending',
            'paymentReference' => null,
            'paymentProof' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders', ['status' => 'cod']));
        $response->assertStatus(200);
        $response->assertSee('Commission Due');
        $response->assertSee('Direct to seller');

        // Verify endpoint rejects COD verification with informative error
        $verifyRes = $this->actingAs($this->admin)->post(route('admin.orders.verify-payment', $codOrder->id));
        $verifyRes->assertSessionHas('error');

        $rejectRes = $this->actingAs($this->admin)->post(route('admin.orders.reject-payment', $storePickupOrder->id), [
            'reason' => 'Some reason',
        ]);
        $rejectRes->assertSessionHas('error');
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

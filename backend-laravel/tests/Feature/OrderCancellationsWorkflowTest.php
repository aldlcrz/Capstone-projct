<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\RefundTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderCancellationsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $customer;
    protected User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'id'             => (string) Str::uuid(),
            'name'           => 'Super Admin',
            'email'          => 'superadmin@lumbarong.com',
            'role'           => 'superadmin',
            'status'         => 'active',
            'isVerified'     => true,
            'hasPasswordSet' => true,
            'password'       => bcrypt('password123'),
        ]);

        $this->customer = User::create([
            'id'             => (string) Str::uuid(),
            'name'           => 'Maria Clara',
            'email'          => 'maria@example.com',
            'role'           => 'customer',
            'status'         => 'active',
            'phone'          => '09171234567',
            'isVerified'     => true,
            'hasPasswordSet' => true,
            'password'       => bcrypt('password123'),
        ]);

        $this->seller = User::create([
            'id'             => (string) Str::uuid(),
            'name'           => 'Barong Artisan',
            'email'          => 'artisan@example.com',
            'shopName'       => 'Lumban Heritage Embroidery',
            'role'           => 'seller',
            'status'         => 'active',
            'isVerified'     => true,
            'hasPasswordSet' => true,
            'password'       => bcrypt('password123'),
        ]);
    }

    /** @test */
    public function order_cancellations_tab_displays_authoritative_cancelled_orders_and_refund_status()
    {
        // 1. Unpaid COD cancelled order
        Order::create([
            'id'                 => (string) Str::uuid(),
            'customerId'         => $this->customer->id,
            'sellerId'           => $this->seller->id,
            'totalAmount'        => 500.00,
            'paymentMethod'      => 'cod',
            'paymentStatus'      => 'unpaid',
            'status'             => 'cancelled',
            'cancellationReason' => 'Customer changed mind',
            'cancelledBy'        => 'customer',
            'shippingAddress'    => '123 Rizal St, Lumban, Laguna',
        ]);

        // 2. Paid Overpaid Cancelled Order (Ordered ₱900, Paid ₱1,000)
        $paidOrder = Order::create([
            'id'                   => (string) Str::uuid(),
            'customerId'           => $this->customer->id,
            'sellerId'             => $this->seller->id,
            'totalAmount'          => 900.00,
            'paymentMethod'        => 'gcash',
            'paymentStatus'        => 'paid',
            'status'               => 'cancelled',
            'cancellationReason'   => 'Out of stock fabric variant',
            'cancelledBy'          => 'seller',
            'refund_mobile_number' => Crypt::encryptString('09171234567'),
            'shippingAddress'      => '123 Rizal St, Lumban, Laguna',
        ]);

        PaymentTransaction::create([
            'id'               => (string) Str::uuid(),
            'order_id'         => $paidOrder->id,
            'customer_id'      => $this->customer->id,
            'method'           => 'gcash',
            'status'           => 'VERIFIED',
            'expected_amount'  => 900.00,
            'detected_amount'  => 1000.00,
            'reference_number' => 'GCASH-TXN-PAID-CANCEL',
            'verified_at'      => now(),
        ]);

        // Verify authoritative refund calculation: Paid ₱1,000 for ₱900 order -> eligible refund is full ₱1,000
        $this->assertEquals(1000.00, $paidOrder->totalPaidAmount());
        $this->assertEquals(1000.00, $paidOrder->remainingCancellationRefundAmount());
        $this->assertEquals('pending_refund', $paidOrder->cancellationRefundStatus());

        // Access the Return & Refund Center Cancellations Tab
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.returns.index', ['tab' => 'cancellations']));
        $response->assertStatus(200);
        $response->assertSee('Order Cancellations');
        $response->assertSee('Out of stock fabric variant');
        $response->assertSee('₱1,000.00');
    }

    /** @test */
    public function superadmin_can_disburse_full_cancellation_refund_with_proof_and_customer_inbox_notification()
    {
        Storage::fake('public');

        $paidOrder = Order::create([
            'id'                   => (string) Str::uuid(),
            'customerId'           => $this->customer->id,
            'sellerId'             => $this->seller->id,
            'totalAmount'          => 900.00,
            'paymentMethod'        => 'gcash',
            'paymentStatus'        => 'paid',
            'status'               => 'cancelled',
            'cancellationReason'   => 'Customer request before shipping',
            'cancelledBy'          => 'customer',
            'refund_mobile_number' => Crypt::encryptString('09171234567'),
            'shippingAddress'      => '123 Rizal St, Lumban, Laguna',
        ]);

        PaymentTransaction::create([
            'id'               => (string) Str::uuid(),
            'order_id'         => $paidOrder->id,
            'customer_id'      => $this->customer->id,
            'method'           => 'gcash',
            'status'           => 'VERIFIED',
            'expected_amount'  => 900.00,
            'detected_amount'  => 1000.00,
            'reference_number' => 'GCASH-ORIGINAL-PAYMENT',
            'verified_at'      => now(),
        ]);

        $proofFile = UploadedFile::fake()->image('refund_receipt.png');

        $response = $this->actingAs($this->superAdmin)->post(
            route('superadmin.returns.refund-cancellation', ['order' => $paidOrder->id]),
            [
                'refund_amount'       => 1000.00,
                'transfer_reference'  => 'GCASH-REFUND-FULL-1000',
                'destination_account' => '09171234567',
                'destination_name'    => 'Maria Clara',
                'transfer_proof'      => $proofFile,
                'notes'               => 'Full ₱1,000 refund disbursed for cancelled order.',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check RefundTransaction record
        $refundTx = RefundTransaction::where('transfer_reference', 'GCASH-REFUND-FULL-1000')->first();
        $this->assertNotNull($refundTx);
        $this->assertEquals(1000.00, (float) $refundTx->refund_amount);
        $this->assertEquals('transferred', $refundTx->status);
        $this->assertNotNull($refundTx->transfer_proof_path);

        // Check Order state
        $paidOrder->refresh();
        $this->assertEquals(0.00, $paidOrder->remainingCancellationRefundAmount());
        $this->assertEquals('refunded', $paidOrder->cancellationRefundStatus());

        // Check Customer Inbox message
        $inboxMsg = Message::where('receiverId', $this->customer->id)->latest()->first();
        $this->assertNotNull($inboxMsg);
        $this->assertStringContainsString('The refund for your cancelled Order', $inboxMsg->content);
        $this->assertStringContainsString('₱1,000.00', $inboxMsg->content);
        $this->assertStringContainsString('GCASH-REFUND-FULL-1000', $inboxMsg->content);
    }
}

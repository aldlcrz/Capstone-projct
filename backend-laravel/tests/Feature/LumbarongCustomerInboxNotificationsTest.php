<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\User;
use App\Services\Messaging\LumbarongSystemMessageService;
use App\Services\Returns\ProcessSukliRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LumbarongCustomerInboxNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $seller;
    protected User $customer;
    protected User $otherCustomer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');

        $this->superAdmin = User::factory()->create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Super Administrator',
            'email'    => 'superadmin@lumbarong.test',
            'role'     => 'superadmin',
            'status'   => 'active',
        ]);

        $this->admin = User::factory()->create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Platform Administrator',
            'email'    => 'admin@lumbarong.test',
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        $this->seller = User::factory()->create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Mang Juan Artisan',
            'shopName' => 'Juan Lumban Crafts',
            'email'    => 'artisan@lumbarong.test',
            'role'     => 'seller',
            'status'   => 'active',
        ]);

        $this->customer = User::factory()->create([
            'id'           => (string) Str::uuid(),
            'name'         => 'Maria Clara',
            'email'        => 'maria@customer.test',
            'role'         => 'customer',
            'status'       => 'active',
            'mobileNumber' => '09123456789',
        ]);

        $this->otherCustomer = User::factory()->create([
            'id'           => (string) Str::uuid(),
            'name'         => 'Crisostomo Ibarra',
            'email'        => 'ibarra@customer.test',
            'role'         => 'customer',
            'status'       => 'active',
            'mobileNumber' => '09987654321',
        ]);

        $this->product = Product::create([
            'id'          => (string) Str::uuid(),
            'sellerId'    => $this->seller->id,
            'name'        => 'Barong Tagalog Classic',
            'description' => 'Fine Lumban Barong Tagalog',
            'price'       => 1000.00,
            'stock'       => 10,
        ]);
    }

    private function createOverpaidOrder(float $payable = 900.00, float $paid = 1000.00, string $method = 'GCash'): Order
    {
        $order = Order::create([
            'id'                   => (string) Str::uuid(),
            'customerId'           => $this->customer->id,
            'sellerId'             => $this->seller->id,
            'shippingAddress'      => '123 Lumban St, Laguna',
            'totalAmount'          => $payable,
            'status'               => 'Pending',
            'paymentStatus'        => 'Paid',
            'paymentMethod'        => $method,
            'paymentReference'     => 'TXN-INCOMING-12345',
            'refund_mobile_number' => Crypt::encryptString('09123456789'),
            'createdAt'            => now(),
        ]);

        PaymentTransaction::create([
            'id'               => (string) Str::uuid(),
            'order_id'         => $order->id,
            'customer_id'      => $this->customer->id,
            'seller_id'        => $this->seller->id,
            'reference_number' => 'TXN-INCOMING-12345',
            'wallet_type'      => strtolower($method),
            'expected_amount'  => $payable,
            'detected_amount'  => $paid,
            'status'           => 'VERIFIED',
            'verified_by'      => $this->admin->id,
            'verified_at'      => now(),
        ]);

        OrderItem::create([
            'id'        => (string) Str::uuid(),
            'orderId'   => $order->id,
            'productId' => $this->product->id,
            'quantity'  => 1,
            'price'     => $payable,
        ]);

        return $order;
    }

    /** @test */
    public function successful_gcash_sukli_refund_creates_one_official_lumbarong_inbox_message_with_proof()
    {
        $order = $this->createOverpaidOrder(900.00, 1000.00, 'GCash');
        $proofFile = UploadedFile::fake()->image('outgoing_sukli_receipt.png', 600, 800);

        $service = app(ProcessSukliRefundService::class);
        $refundTx = $service->processSukliRefund(
            $order->id,
            $this->superAdmin,
            100.00,
            'GCASH-SUKLI-REF-9988',
            $proofFile,
            '09123456789',
            'Maria Clara',
            'Processed exact overpayment refund'
        );

        $this->assertNotNull($refundTx);
        $this->assertEquals('transferred', $refundTx->status);
        $this->assertNotNull($refundTx->transfer_proof_path);

        // Verify Message was sent to Customer Inbox
        $messages = Message::where('receiverId', $this->customer->id)->get();
        $this->assertCount(1, $messages);

        $message = $messages->first();
        $this->assertStringContainsString('Hello! This is LumBarong.', $message->content);
        $this->assertStringContainsString('Your sukli refund for Order #LB-', $message->content);
        $this->assertStringContainsString('Order Total: ₱900.00', $message->content);
        $this->assertStringContainsString('Original Payment: ₱1,000.00', $message->content);
        $this->assertStringContainsString('Sukli Refunded: ₱100.00', $message->content);
        $this->assertStringContainsString('Refund Method: GCash', $message->content);
        $this->assertStringContainsString('0912****789', $message->content);
        $this->assertStringContainsString('GCASH-SUKLI-REF-9988', $message->content);
        $this->assertStringContainsString('The refund proof/receipt is attached to this message', $message->content);
        $this->assertStringContainsString(route('orders.refund-proof', ['orderId' => $order->id, 'refundId' => $refundTx->id]), $message->content);
    }

    /** @test */
    public function successful_maya_sukli_refund_formats_method_and_details_correctly()
    {
        $order = $this->createOverpaidOrder(1500.00, 2000.00, 'Maya');
        $proofFile = UploadedFile::fake()->image('maya_receipt.jpg', 600, 800);

        $service = app(ProcessSukliRefundService::class);
        $refundTx = $service->processSukliRefund(
            $order->id,
            $this->admin,
            500.00,
            'MAYA-SUKLI-REF-7766',
            $proofFile,
            '09187654321',
            'Maria Clara',
            'Maya sukli refund'
        );

        $message = Message::where('receiverId', $this->customer->id)->latest('createdAt')->first();
        $this->assertNotNull($message);
        $this->assertStringContainsString('Refund Method: Maya', $message->content);
        $this->assertStringContainsString('Sukli Refunded: ₱500.00', $message->content);
        $this->assertStringContainsString('MAYA-SUKLI-REF-7766', $message->content);
    }

    /** @test */
    public function customer_can_securely_view_own_refund_proof_but_other_customer_is_forbidden()
    {
        $order = $this->createOverpaidOrder(900.00, 1000.00, 'GCash');
        $proofFile = UploadedFile::fake()->image('outgoing_receipt.png', 400, 400);

        $service = app(ProcessSukliRefundService::class);
        $refundTx = $service->processSukliRefund(
            $order->id,
            $this->superAdmin,
            100.00,
            'TXN-PROOF-AUTH-TEST',
            $proofFile,
            '09123456789'
        );

        // 1. Customer who owns the order can access the proof
        $response = $this->actingAs($this->customer)
            ->get(route('orders.refund-proof', ['orderId' => $order->id, 'refundId' => $refundTx->id]));
        $response->assertStatus(200);

        // 2. Unrelated customer is forbidden (403)
        $unrelatedResponse = $this->actingAs($this->otherCustomer)
            ->get(route('orders.refund-proof', ['orderId' => $order->id, 'refundId' => $refundTx->id]));
        $unrelatedResponse->assertStatus(403);

        // 3. Admin and SuperAdmin can access
        $adminResponse = $this->actingAs($this->admin)
            ->get(route('orders.refund-proof', ['orderId' => $order->id, 'refundId' => $refundTx->id]));
        $adminResponse->assertStatus(200);
    }

    /** @test */
    public function sukli_refund_message_idempotency_prevents_duplicate_messages_on_retry()
    {
        $order = $this->createOverpaidOrder(900.00, 1000.00, 'GCash');
        $proofFile = UploadedFile::fake()->image('receipt.png', 400, 400);

        $service = app(ProcessSukliRefundService::class);
        $refundTx = $service->processSukliRefund(
            $order->id,
            $this->admin,
            100.00,
            'IDEMPOTENCY-TEST-REF',
            $proofFile,
            '09123456789'
        );

        $this->assertEquals(1, Message::where('receiverId', $this->customer->id)->count());

        // Attempting to dispatch the same message again directly
        $secondAttempt = LumbarongSystemMessageService::sendSukliRefundCompletedMessage($order, $refundTx, $this->admin);

        // Total messages count must still be strictly 1
        $this->assertEquals(1, Message::where('receiverId', $this->customer->id)->count());
        $this->assertNotNull($secondAttempt);
    }

    /** @test */
    public function seller_order_cancellation_creates_official_lumbarong_message()
    {
        $order = Order::create([
            'id'                   => (string) Str::uuid(),
            'customerId'           => $this->customer->id,
            'sellerId'             => $this->seller->id,
            'shippingAddress'      => '123 Lumban St, Laguna',
            'totalAmount'          => 1200.00,
            'status'               => 'Pending',
            'paymentStatus'        => 'Unpaid',
            'paymentMethod'        => 'COD',
            'createdAt'            => now(),
        ]);

        OrderItem::create([
            'id'        => (string) Str::uuid(),
            'orderId'   => $order->id,
            'productId' => $this->product->id,
            'quantity'  => 1,
            'price'     => 1200.00,
        ]);

        $response = $this->actingAs($this->seller)
            ->post(route('orders.seller-cancel', ['id' => $order->id]), [
                'reason' => 'Out of requested fabric variant',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('Cancelled', $order->fresh()->status);

        // Verify LumBarong Inbox Message
        $message = Message::where('receiverId', $this->customer->id)->latest('createdAt')->first();
        $this->assertNotNull($message);
        $this->assertStringContainsString('Hello! This is LumBarong.', $message->content);
        $this->assertStringContainsString('We would like to inform you that Order #LB-', $message->content);
        $this->assertStringContainsString('Cancellation Reason: Out of requested fabric variant', $message->content);
        $this->assertStringContainsString('Cancelled By: Juan Lumban Crafts', $message->content);
        $this->assertStringContainsString('No payment deduction was captured for this order.', $message->content);
    }

    /** @test */
    public function admin_payment_rejection_cancellation_creates_official_lumbarong_message_with_refund_note()
    {
        $order = Order::create([
            'id'                   => (string) Str::uuid(),
            'customerId'           => $this->customer->id,
            'sellerId'             => $this->seller->id,
            'shippingAddress'      => '123 Lumban St, Laguna',
            'totalAmount'          => 1500.00,
            'status'               => 'Pending',
            'paymentStatus'        => 'Unverified',
            'paymentMethod'        => 'GCash',
            'paymentProof'         => 'payments/test_receipt.jpg',
            'createdAt'            => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.orders.reject-payment', ['id' => $order->id]), [
                'reason' => 'Receipt image is blurry and reference does not match GCash ledger',
            ]);

        $response->assertRedirect();
        $this->assertEquals('Cancelled', $order->fresh()->status);

        $message = Message::where('receiverId', $this->customer->id)->latest('createdAt')->first();
        $this->assertNotNull($message);
        $this->assertStringContainsString('Hello! This is LumBarong.', $message->content);
        $this->assertStringContainsString('Cancellation Reason: Payment verification rejected by Administrator', $message->content);
        $this->assertStringContainsString('Cancelled By: LumBarong Administration', $message->content);
    }

    /** @test */
    public function cancellation_idempotency_prevents_duplicate_cancellation_messages()
    {
        $order = Order::create([
            'id'                   => (string) Str::uuid(),
            'customerId'           => $this->customer->id,
            'sellerId'             => $this->seller->id,
            'shippingAddress'      => '123 Lumban St, Laguna',
            'totalAmount'          => 800.00,
            'status'               => 'Cancelled',
            'cancellationReason'   => 'Customer changed mind',
            'createdAt'            => now(),
        ]);

        // First message
        LumbarongSystemMessageService::sendOrderCancelledMessage($order, 'Customer changed mind', 'Customer');
        $this->assertEquals(1, Message::where('receiverId', $this->customer->id)->count());

        // Repeated invocation
        LumbarongSystemMessageService::sendOrderCancelledMessage($order, 'Customer changed mind', 'Customer');
        $this->assertEquals(1, Message::where('receiverId', $this->customer->id)->count());
    }

    /** @test */
    public function chat_conversations_endpoint_returns_lumbarong_system_branding_for_admin_messages()
    {
        $systemUser = LumbarongSystemMessageService::getSystemUser();

        Message::create([
            'id'         => (string) Str::uuid(),
            'senderId'   => $systemUser->id,
            'receiverId' => $this->customer->id,
            'content'    => "Hello! This is LumBarong.\nYour order has been updated.",
            'read'       => false,
        ]);

        $response = $this->actingAs($this->customer)->get('/chat/conversations');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertNotEmpty($data);

        $lumbarongConv = collect($data)->firstWhere('otherUser.name', 'LumBarong');
        $this->assertNotNull($lumbarongConv);
        $this->assertEquals('system', $lumbarongConv['otherUser']['role']);
        $this->assertTrue($lumbarongConv['otherUser']['isSystem']);
        $this->assertEquals(1, $lumbarongConv['unreadCount']);
    }
}

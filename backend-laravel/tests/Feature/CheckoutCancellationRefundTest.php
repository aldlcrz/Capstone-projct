<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutCancellationRefundTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $seller;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->seller = User::factory()->create([
            'role' => 'artisan',
            'gcashNumber' => '09123456789',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'Active',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Jane Buyer',
            'mobileNumber' => '09987654321',
        ]);

        $this->product = Product::create([
            'sellerId'    => $this->seller->id,
            'name'        => 'Custom Silk Barong',
            'description' => 'Test description',
            'price'       => 1250.00,
            'stock'       => 10,
            'status'      => 'Approved',
        ]);
    }

    public function test_customer_can_cancel_checkout_when_not_paid_yet_and_redirects_to_cart()
    {
        $response = $this->actingAs($this->customer)
            ->withSession([
                'cart' => [
                    $this->product->id => [
                        'id' => $this->product->id,
                        'name' => $this->product->name,
                        'price' => 1250.00,
                        'quantity' => 1,
                        'sellerId' => $this->seller->id,
                    ]
                ]
            ])
            ->postJson(route('checkout.cancel_refund'), [
                'already_paid' => false,
                'mode' => 'cart',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'redirect' => route('cart.index'),
        ]);

        // No cancelled order or refund should be created
        $this->assertEquals(0, Order::count());
        $this->assertEquals(0, ReturnRequest::count());
        $this->assertEquals(0, RefundTransaction::count());
    }

    public function test_cancelling_paid_checkout_validates_philippine_mobile_number()
    {
        $response = $this->actingAs($this->customer)
            ->postJson(route('checkout.cancel_refund'), [
                'already_paid' => true,
                'refund_method' => 'GCash',
                'refund_account_name' => 'Jane Buyer',
                'refund_mobile_number' => '123456', // Invalid format
                'refund_amount' => 1250.00,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['refund_mobile_number']);
    }

    public function test_customer_can_submit_refund_request_with_gcash_and_notifies_admin()
    {
        $file = UploadedFile::fake()->image('payment_receipt.jpg');

        $response = $this->actingAs($this->customer)
            ->withSession([
                'cart' => [
                    $this->product->id => [
                        'id' => $this->product->id,
                        'name' => $this->product->name,
                        'price' => 1250.00,
                        'quantity' => 1,
                        'sellerId' => $this->seller->id,
                    ]
                ]
            ])
            ->postJson(route('checkout.cancel_refund'), [
                'already_paid' => true,
                'refund_method' => 'GCash',
                'refund_account_name' => 'Jane Buyer GCash',
                'refund_mobile_number' => '09987654321',
                'refund_amount' => 1250.00,
                'refund_reference' => '900234123567',
                'reason' => 'Wrong order quantity selected',
                'payment_screenshot' => $file,
                'mode' => 'cart',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'redirect' => route('orders'),
        ]);

        // Verify Order creation
        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('Refund Requested', $order->paymentStatus);
        $this->assertEquals('GCash', $order->paymentMethod);
        $this->assertEquals('09987654321', $order->refund_mobile_number);
        $this->assertEquals(1250.00, (float) $order->totalAmount);

        // Verify ReturnRequest creation
        $returnReq = ReturnRequest::where('orderId', $order->id)->first();
        $this->assertNotNull($returnReq);
        $this->assertEquals('pending', $returnReq->status);
        $this->assertEquals(1250.00, (float) $returnReq->requested_amount);
        $this->assertStringContainsString('09987654321', $returnReq->reason);
        $this->assertStringContainsString('Jane Buyer GCash', $returnReq->reason);

        // Verify RefundTransaction creation
        $refundTx = RefundTransaction::where('order_id', $order->id)->first();
        $this->assertNotNull($refundTx);
        $this->assertEquals('GCash', $refundTx->refund_method);
        $this->assertEquals('Jane Buyer GCash', $refundTx->destination_account_name);
        $this->assertEquals('09987654321', $refundTx->destination_account_encrypted);
        $this->assertEquals('0998***321', $refundTx->destination_account_masked);
        $this->assertEquals('pending', $refundTx->status);
        $this->assertEquals(1250.00, (float) $refundTx->refund_amount);

        // Verify Admin notification
        $adminNotif = Notification::where('targetRole', 'admin')
            ->where('type', 'refund_requested')
            ->first();
        $this->assertNotNull($adminNotif);
        $this->assertStringContainsString('09987654321', $adminNotif->message);
        $this->assertStringContainsString('Jane Buyer GCash', $adminNotif->message);

        // Verify Customer notification
        $custNotif = Notification::where('userId', $this->customer->id)
            ->where('type', 'refund_requested')
            ->first();
        $this->assertNotNull($custNotif);
        $this->assertStringContainsString('09987654321', $custNotif->message);
    }

    public function test_customer_can_submit_refund_request_with_maya()
    {
        $response = $this->actingAs($this->customer)
            ->postJson(route('checkout.cancel_refund'), [
                'already_paid' => true,
                'refund_method' => 'Maya',
                'refund_account_name' => 'Jane Maya',
                'refund_mobile_number' => '09171234567',
                'refund_amount' => 500.00,
                'refund_reference' => 'MYA-12345678',
                'reason' => 'Decided to cancel',
            ]);

        $response->assertStatus(200);

        $refundTx = RefundTransaction::where('payment_method', 'Maya')->first();
        $this->assertNotNull($refundTx);
        $this->assertEquals('Maya', $refundTx->refund_method);
        $this->assertEquals('Jane Maya', $refundTx->destination_account_name);
        $this->assertEquals('09171234567', $refundTx->destination_account_encrypted);
        $this->assertEquals(500.00, (float) $refundTx->refund_amount);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReturnNotificationClickTest extends TestCase
{
    use RefreshDatabase;

    public function test_clicking_seller_return_notification_with_or_without_link_does_not_500(): void
    {
        /** @var User $seller */
        $seller = User::factory()->create(['role' => 'seller', 'status' => 'active', 'is_onboarded' => true]);
        /** @var User $customer */
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);

        $order = Order::create([
            'customerId'      => $customer->id,
            'sellerId'        => $seller->id,
            'totalAmount'     => 1500,
            'status'          => 'Delivered',
            'paymentMethod'   => 'gcash',
            'paymentStatus'   => 'paid',
            'shippingAddress' => '123 Lumban St, Laguna',
        ]);

        // 1. Customer submits return, generating a notification to the seller
        $photoProof = UploadedFile::fake()->image('defect.jpg');
        $this->actingAs($customer)->postJson("/orders/{$order->id}/returns", [
            'orderId'         => $order->id,
            'reason'          => 'Damaged Item',
            'resolution_type' => 'refund',
            'proof_files'     => [$photoProof],
        ]);

        $notification = Notification::where('userId', $seller->id)->latest('createdAt')->first();
        $this->assertNotNull($notification);

        // Seller clicks the fresh notification
        $response = $this->actingAs($seller)->get("/notifications/{$notification->id}/read");
        $this->assertNotEquals(500, $response->getStatusCode(), 'Clicking return notification caused a 500 server error!');
        $response->assertRedirect();
        $this->assertStringContainsString('/seller/orders', $response->headers->get('Location'));

        // 2. Test legacy notification with null link (regression test for route not found 500)
        $legacyNotif = Notification::create([
            'userId'     => $seller->id,
            'title'      => 'New Return/Refund Request',
            'message'    => "A customer requested a return/refund for Order #LB-OR-" . strtoupper(substr($order->id, -8)),
            'targetRole' => 'seller',
            'link'       => null,
        ]);

        $legacyResponse = $this->actingAs($seller)->get("/notifications/{$legacyNotif->id}/read");
        $this->assertNotEquals(500, $legacyResponse->getStatusCode(), 'Clicking legacy return notification caused 500!');
        $legacyResponse->assertRedirect();
        $this->assertStringContainsString('/seller/orders', $legacyResponse->headers->get('Location'));
    }

    public function test_clicking_admin_and_customer_return_notifications_redirects_safely(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        /** @var User $customer */
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);

        // Admin notification without link
        $adminNotif = Notification::create([
            'userId'     => $admin->id,
            'title'      => 'Return Case Disputed',
            'message'    => 'Customer escalated a rejected return for Order #LB-OR-12345678',
            'targetRole' => 'admin',
            'link'       => null,
        ]);

        $adminResponse = $this->actingAs($admin)->get("/notifications/{$adminNotif->id}/read");
        $this->assertNotEquals(500, $adminResponse->getStatusCode());
        $adminResponse->assertRedirect(route('admin.returns.index'));

        // Customer notification without link
        $customerNotif = Notification::create([
            'userId'     => $customer->id,
            'title'      => 'Return Request Approved',
            'message'    => 'Your return request for order #LB-12345678 has been approved.',
            'targetRole' => 'customer',
            'link'       => null,
        ]);

        $customerResponse = $this->actingAs($customer)->get("/notifications/{$customerNotif->id}/read");
        $this->assertNotEquals(500, $customerResponse->getStatusCode());
        $customerResponse->assertRedirect();
    }
}

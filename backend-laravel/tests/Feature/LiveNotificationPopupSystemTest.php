<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveNotificationPopupSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected User $admin;
    protected User $superadmin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'active',
            'isVerified' => true,
            'email_verified_at' => now(),
            'shopName' => 'Artisan Test Shop',
            'is_onboarded' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);
    }

    public function test_seller_api_notifications_returns_unread_notifications(): void
    {
        // 1. Create a notification for seller
        $notif = Notification::create([
            'userId' => $this->seller->id,
            'title' => 'New Order Received',
            'message' => 'A customer placed a new order (#LB-12345678) in your shop.',
            'type' => 'order',
            'link' => '/seller/orders?order_id=test-123',
            'targetRole' => 'seller',
            'isRead' => false,
        ]);

        // 2. Fetch as seller
        $response = $this->actingAs($this->seller)
            ->getJson('/api/notifications?role=seller');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $notif->id,
            'title' => 'New Order Received',
            'message' => 'A customer placed a new order (#LB-12345678) in your shop.',
            'isRead' => false,
            'targetRole' => 'seller',
        ]);
    }

    public function test_seller_dashboard_view_renders_notification_popup_component(): void
    {
        $response = $this->actingAs($this->seller)
            ->get('/seller/dashboard');

        $response->assertStatus(200);
        $response->assertSee('sellerNotificationPopup', false);
        $response->assertSee('/api/notifications?role=seller', false);
        $response->assertSee('x-show="popupNotif"', false);
        $response->assertSee('popupNotif?.title', false);
        $response->assertSee('top-24 right-4', false);
    }

    public function test_admin_api_notifications_returns_admin_and_superadmin_notifs(): void
    {
        $notif = Notification::create([
            'userId' => $this->admin->id,
            'title' => 'New Seller Application',
            'message' => 'A new artisan shop has submitted verification documents.',
            'type' => 'system',
            'link' => '/admin/sellers',
            'targetRole' => 'admin',
            'isRead' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/notifications?role=admin');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $notif->id,
            'title' => 'New Seller Application',
            'isRead' => false,
        ]);
    }

    public function test_admin_dashboard_view_renders_notification_popup_component(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('adminNotificationPopup', false);
        $response->assertSee('/api/notifications?role=', false);
        $response->assertSee('x-show="popupNotif"', false);
        $response->assertSee('popupNotif?.title', false);
    }

    public function test_customer_api_notifications_returns_customer_notifs(): void
    {
        $notif = Notification::create([
            'userId' => $this->customer->id,
            'title' => 'Order Shipped',
            'message' => 'Your order is on the way!',
            'type' => 'order',
            'link' => '/orders/test-order-id',
            'targetRole' => 'customer',
            'isRead' => false,
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/notifications?role=customer');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $notif->id,
            'title' => 'Order Shipped',
            'isRead' => false,
        ]);
    }

    public function test_customer_home_view_renders_notification_popup_component(): void
    {
        $response = $this->actingAs($this->customer)
            ->get('/');

        $response->assertStatus(200);
        $response->assertSee('customerNotificationPopup', false);
        $response->assertSee('/api/notifications?role=customer', false);
        $response->assertSee('x-show="popupNotif"', false);
    }

    public function test_notification_read_and_redirect_marks_as_read_and_redirects(): void
    {
        $notif = Notification::create([
            'userId' => $this->seller->id,
            'title' => 'New Order Received',
            'message' => 'Check order',
            'type' => 'order',
            'link' => '/seller/orders',
            'targetRole' => 'seller',
            'isRead' => false,
        ]);

        $response = $this->actingAs($this->seller)
            ->get('/notifications/' . $notif->id . '/read');

        $response->assertRedirect('/seller/orders');
        $this->assertDatabaseHas('notifications', [
            'id' => $notif->id,
            'isRead' => true,
        ]);
    }

    public function test_unread_count_api_endpoint(): void
    {
        Notification::create([
            'userId' => $this->seller->id,
            'title' => 'Unread 1',
            'message' => 'Message 1',
            'targetRole' => 'seller',
            'isRead' => false,
        ]);

        Notification::create([
            'userId' => $this->seller->id,
            'title' => 'Unread 2',
            'message' => 'Message 2',
            'targetRole' => 'seller',
            'isRead' => false,
        ]);

        Notification::create([
            'userId' => $this->seller->id,
            'title' => 'Read Notif',
            'message' => 'Message 3',
            'targetRole' => 'seller',
            'isRead' => true,
        ]);

        $response = $this->actingAs($this->seller)
            ->getJson('/api/notifications/unread-count?role=seller');

        $response->assertStatus(200);
        $response->assertJson(['unreadCount' => 2]);
    }

    public function test_dismiss_notification_marks_as_dismissed_and_excludes_from_api(): void
    {
        $notif1 = Notification::create([
            'userId' => $this->seller->id,
            'title' => 'First Order',
            'message' => 'First notification',
            'targetRole' => 'seller',
            'isRead' => false,
            'is_dismissed' => false,
        ]);

        $notif2 = Notification::create([
            'userId' => $this->seller->id,
            'title' => 'Second Order',
            'message' => 'Second notification',
            'targetRole' => 'seller',
            'isRead' => false,
            'is_dismissed' => false,
        ]);

        // Dismiss notif1
        $dismissResponse = $this->actingAs($this->seller)
            ->postJson('/api/notifications/' . $notif1->id . '/dismiss');

        $dismissResponse->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => $notif1->id,
            'is_dismissed' => true,
        ]);

        // Calling /api/notifications should now only return notif2
        $apiResponse = $this->actingAs($this->seller)
            ->getJson('/api/notifications?role=seller');

        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonCount(1);
        $apiResponse->assertJsonFragment(['id' => $notif2->id]);
        $apiResponse->assertJsonMissing(['id' => $notif1->id]);

        // However, notif1 is still unread for bell count purposes
        $countResponse = $this->actingAs($this->seller)
            ->getJson('/api/notifications/unread-count?role=seller');
        $countResponse->assertStatus(200);
        $countResponse->assertJson(['unreadCount' => 2]);
    }

    public function test_all_four_roles_support_dismiss_notification(): void
    {
        $roles = [
            'seller' => $this->seller,
            'admin' => $this->admin,
            'superadmin' => $this->superadmin,
            'customer' => $this->customer,
        ];

        foreach ($roles as $roleKey => $user) {
            $notif = Notification::create([
                'userId' => $user->id,
                'title' => ucfirst($roleKey) . ' Alert',
                'message' => 'Test alert for ' . $roleKey,
                'targetRole' => $roleKey,
                'isRead' => false,
                'is_dismissed' => false,
            ]);

            $response = $this->actingAs($user)
                ->postJson('/api/notifications/' . $notif->id . '/dismiss');

            $response->assertStatus(200);
            $this->assertDatabaseHas('notifications', [
                'id' => $notif->id,
                'is_dismissed' => true,
            ]);

            // Excluded from popup query
            $listResponse = $this->actingAs($user)
                ->getJson('/api/notifications?role=' . $roleKey);
            $listResponse->assertStatus(200);
            $listResponse->assertJsonMissing(['id' => $notif->id]);
        }
    }
}

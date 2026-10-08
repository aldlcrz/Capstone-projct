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
        $response->assertSee('fetch(\'/api/notifications?role=seller\'', false);
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
        $response->assertSee('fetch(\'/api/notifications?role=admin\'', false);
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
        $response->assertSee('fetch(\'/api/notifications?role=customer\'', false);
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
}

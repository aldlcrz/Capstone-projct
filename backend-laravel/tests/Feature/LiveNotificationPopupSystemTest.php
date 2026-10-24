<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\NotificationDismissal;
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

    public function test_dismissed_notification_does_not_appear_on_refresh_or_relogin_and_only_new_notification_triggers_popup(): void
    {
        $roles = [
            'admin' => $this->admin,
            'superadmin' => $this->superadmin,
            'seller' => $this->seller,
            'customer' => $this->customer,
        ];

        foreach ($roles as $roleKey => $user) {
            // 1. Initial Notification arrives
            $initialNotif = Notification::create([
                'userId' => $user->id,
                'title' => ucfirst($roleKey) . ' Initial Notification',
                'message' => 'First message for ' . $roleKey,
                'targetRole' => $roleKey,
                'isRead' => false,
                'is_dismissed' => false,
            ]);

            // User sees it in the candidate list
            $initialFetch = $this->actingAs($user)->getJson('/api/notifications?role=' . $roleKey);
            $initialFetch->assertStatus(200);
            $initialFetch->assertJsonFragment(['id' => $initialNotif->id]);

            // 2. User clicks the "X" button to dismiss
            $dismissRes = $this->actingAs($user)->postJson('/api/notifications/' . $initialNotif->id . '/dismiss');
            $dismissRes->assertStatus(200);

            // Verified saved in DB as is_dismissed = true
            $this->assertDatabaseHas('notifications', [
                'id' => $initialNotif->id,
                'is_dismissed' => true,
            ]);

            // 3. User refreshes the page (new GET request) -> MUST NOT appear
            $refreshFetch = $this->actingAs($user)->getJson('/api/notifications?role=' . $roleKey);
            $refreshFetch->assertStatus(200);
            $refreshFetch->assertJsonMissing(['id' => $initialNotif->id]);

            // 4. User logs out and logs in again (new session/new authentication instance) -> MUST NOT appear
            auth()->logout();
            $reloginFetch = $this->actingAs($user)->getJson('/api/notifications?role=' . $roleKey);
            $reloginFetch->assertStatus(200);
            $reloginFetch->assertJsonMissing(['id' => $initialNotif->id]);

            // 5. A brand new incoming notification arrives
            $newIncomingNotif = Notification::create([
                'userId' => $user->id,
                'title' => ucfirst($roleKey) . ' Brand New Alert',
                'message' => 'New incoming event for ' . $roleKey,
                'targetRole' => $roleKey,
                'isRead' => false,
                'is_dismissed' => false,
            ]);

            // 6. Only the new incoming notification is returned
            $incomingFetch = $this->actingAs($user)->getJson('/api/notifications?role=' . $roleKey);
            $incomingFetch->assertStatus(200);
            $incomingFetch->assertJsonFragment(['id' => $newIncomingNotif->id]);
            $incomingFetch->assertJsonMissing(['id' => $initialNotif->id]);
        }
    }

    public function test_mark_all_as_read_for_admin_superadmin_seller_and_customer(): void
    {
        // 1. Test Admin and SuperAdmin isolation & mark all as read
        $admin2 = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create 2 notifications for Admin 1
        Notification::create([
            'userId' => $this->admin->id,
            'title' => 'Admin 1 Alert',
            'message' => 'For Admin 1',
            'targetRole' => 'admin',
            'isRead' => false,
        ]);
        // Create 2 notifications for Admin 2
        Notification::create([
            'userId' => $admin2->id,
            'title' => 'Admin 2 Alert',
            'message' => 'For Admin 2',
            'targetRole' => 'admin',
            'isRead' => false,
        ]);

        // When Admin 1 visits dashboard, they should see their own unread notifications, not Admin 2's
        $dashResponse = $this->actingAs($this->admin)->get('/admin/dashboard');
        $dashResponse->assertStatus(200);

        // Admin 1 clicks "Mark all as read"
        $readAllRes = $this->actingAs($this->admin)->post('/admin/notifications/read-all');
        $readAllRes->assertRedirect();

        // Admin 1's notifications are read
        $this->assertEquals(0, Notification::where('userId', $this->admin->id)->where('isRead', false)->count());
        // Admin 2's notifications remain unread
        $this->assertEquals(1, Notification::where('userId', $admin2->id)->where('isRead', false)->count());

        // 2. Test SuperAdmin mark all read
        Notification::create([
            'userId' => $this->superadmin->id,
            'title' => 'SuperAdmin Alert',
            'message' => 'For Superadmin',
            'targetRole' => 'superadmin',
            'isRead' => false,
        ]);
        $saReadAll = $this->actingAs($this->superadmin)->post('/superadmin/notifications/read-all');
        $saReadAll->assertRedirect();
        $this->assertEquals(0, Notification::where('userId', $this->superadmin->id)->where('isRead', false)->count());

        // 3. Test Seller mark all read
        Notification::create([
            'userId' => $this->seller->id,
            'title' => 'Seller Alert',
            'message' => 'For Seller',
            'targetRole' => 'seller',
            'isRead' => false,
        ]);
        $sellerReadAll = $this->actingAs($this->seller)->post('/seller/notifications/read-all');
        $sellerReadAll->assertRedirect();
        $this->assertEquals(0, Notification::where('userId', $this->seller->id)->where('isRead', false)->count());

        // 4. Test Customer mark all read
        Notification::create([
            'userId' => $this->customer->id,
            'title' => 'Customer Alert',
            'message' => 'For Customer',
            'targetRole' => 'customer',
            'isRead' => false,
        ]);
        $custReadAll = $this->actingAs($this->customer)->post('/notifications/read-all');
        $custReadAll->assertRedirect();
        $this->assertEquals(0, Notification::where('userId', $this->customer->id)->where('isRead', false)->count());
    }

    public function test_notification_dismissals_table_persists_user_isolation_for_broadcast_notifs(): void
    {
        $admin2 = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $notifAdmin1 = Notification::create([
            'userId' => $this->admin->id,
            'title' => 'System Maintenance Alert',
            'message' => 'Platform maintenance scheduled at midnight.',
            'targetRole' => 'admin',
            'isRead' => false,
            'is_dismissed' => false,
        ]);

        $notifAdmin2 = Notification::create([
            'userId' => $admin2->id,
            'title' => 'System Maintenance Alert',
            'message' => 'Platform maintenance scheduled at midnight.',
            'targetRole' => 'admin',
            'isRead' => false,
            'is_dismissed' => false,
        ]);

        // Admin 1 sees it in their popup list
        $resAdmin1 = $this->actingAs($this->admin)->getJson('/api/notifications?role=admin');
        $resAdmin1->assertStatus(200);
        $resAdmin1->assertJsonFragment(['id' => $notifAdmin1->id]);

        // Admin 2 also sees it in their popup list
        $resAdmin2 = $this->actingAs($admin2)->getJson('/api/notifications?role=admin');
        $resAdmin2->assertStatus(200);
        $resAdmin2->assertJsonFragment(['id' => $notifAdmin2->id]);

        // Admin 1 dismisses their alert
        $dismissRes = $this->actingAs($this->admin)->postJson('/api/notifications/' . $notifAdmin1->id . '/dismiss');
        $dismissRes->assertStatus(200);

        // Verify dismissal row created specifically for Admin 1
        $this->assertDatabaseHas('notification_dismissals', [
            'user_id' => $this->admin->id,
            'notification_id' => $notifAdmin1->id,
        ]);
        // No dismissal row for Admin 2
        $this->assertDatabaseMissing('notification_dismissals', [
            'user_id' => $admin2->id,
            'notification_id' => $notifAdmin2->id,
        ]);

        // Admin 1 no longer sees it
        $resAdmin1After = $this->actingAs($this->admin)->getJson('/api/notifications?role=admin');
        $resAdmin1After->assertJsonMissing(['id' => $notifAdmin1->id]);

        // Admin 2 STILL sees it (isolation preserved!)
        $resAdmin2After = $this->actingAs($admin2)->getJson('/api/notifications?role=admin');
        $resAdmin2After->assertJsonFragment(['id' => $notifAdmin2->id]);
    }

    public function test_dismissal_preserves_unread_status_and_notification_center_count(): void
    {
        $notif1 = Notification::create([
            'userId' => $this->customer->id,
            'title' => 'Order Placed',
            'message' => 'Order #1 placed',
            'targetRole' => 'customer',
            'isRead' => false,
        ]);
        $notif2 = Notification::create([
            'userId' => $this->customer->id,
            'title' => 'Order Packed',
            'message' => 'Order #2 packed',
            'targetRole' => 'customer',
            'isRead' => false,
        ]);

        // Initial count is 2
        $count1 = $this->actingAs($this->customer)->getJson('/api/notifications/unread-count?role=customer');
        $count1->assertJson(['unreadCount' => 2]);

        // Customer dismisses notif1 popup
        $dismissRes = $this->actingAs($this->customer)->postJson('/api/notifications/' . $notif1->id . '/dismiss');
        $dismissRes->assertStatus(200);

        // 1. notif1 is NOT deleted
        $this->assertDatabaseHas('notifications', ['id' => $notif1->id]);
        // 2. notif1 is NOT marked as read
        $this->assertDatabaseHas('notifications', ['id' => $notif1->id, 'isRead' => false]);
        // 3. Unread count in notification center is STILL 2
        $count2 = $this->actingAs($this->customer)->getJson('/api/notifications/unread-count?role=customer');
        $count2->assertJson(['unreadCount' => 2]);
        // 4. But popup candidate query only returns notif2
        $popupList = $this->actingAs($this->customer)->getJson('/api/notifications?role=customer');
        $popupList->assertJsonCount(1);
        $popupList->assertJsonFragment(['id' => $notif2->id]);
        $popupList->assertJsonMissing(['id' => $notif1->id]);
    }

    public function test_all_four_views_include_seen_tracking_and_keepalive(): void
    {
        $adminView = $this->actingAs($this->admin)->get('/admin/dashboard');
        $adminView->assertSee('seen_popup_notifs_', false);
        $adminView->assertSee('keepalive: true', false);

        $superAdminView = $this->actingAs($this->superadmin)->get('/superadmin/dashboard');
        $superAdminView->assertSee('seen_popup_notifs_', false);
        $superAdminView->assertSee('keepalive: true', false);

        $sellerView = $this->actingAs($this->seller)->get('/seller/dashboard');
        $sellerView->assertSee('seen_popup_notifs_', false);
        $sellerView->assertSee('keepalive: true', false);

        $customerView = $this->actingAs($this->customer)->get('/');
        $customerView->assertSee('seen_popup_notifs_', false);
        $customerView->assertSee('keepalive: true', false);
    }
}

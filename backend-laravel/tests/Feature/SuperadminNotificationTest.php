<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_unread_count_decreases_when_clicking_notification(): void
    {
        $superadmin = User::factory()->create([
            'email' => 'superadmin_click@example.com',
            'role' => 'superadmin',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $notif1 = Notification::create([
            'userId' => $superadmin->id,
            'title' => 'New Product Listed',
            'message' => 'Artisan Test Seller has listed a new product: "sabi ni mel" for review.',
            'type' => 'system',
            'link' => '/admin/products',
            'targetRole' => 'admin',
            'isRead' => false,
        ]);

        $notif2 = Notification::create([
            'userId' => $superadmin->id,
            'title' => 'New Trust & Safety Report Filed',
            'message' => 'A customer reported a product listing (Rams) for "Counterfeit / Fake Item".',
            'type' => 'warning',
            'link' => '/admin/reports',
            'targetRole' => 'admin',
            'isRead' => false,
        ]);

        // Broadcast notification addressed to superadmin
        $notif3 = Notification::create([
            'userId' => $superadmin->id,
            'title' => 'System Wide Notice',
            'message' => 'Platform maintenance announcement.',
            'type' => 'system',
            'link' => '/admin/settings',
            'targetRole' => 'admin',
            'isRead' => false,
        ]);

        // Verify initial page sees 3 notifications
        $response = $this->actingAs($superadmin)->get('/admin/reports');
        $response->assertStatus(200);
        $response->assertSee('3'); // unread badge

        // Superadmin clicks notif1 via read-and-redirect
        $redirectResponse = $this->actingAs($superadmin)->get(route('notifications.read-and-redirect', $notif1->id));
        $redirectResponse->assertRedirect('/admin/products');

        // Notification 1 must now be read
        $this->assertTrue($notif1->fresh()->isRead);

        // Next page view must now see 2 unread notifications (decreased from 3 to 2)
        $responseAfterClick = $this->actingAs($superadmin)->get('/admin/reports');
        $responseAfterClick->assertStatus(200);
        $responseAfterClick->assertSee('2');

        // Test Mark All Read via superadmin.notifications.read-all
        $markAllResponse = $this->actingAs($superadmin)->post(route('superadmin.notifications.read-all'));
        $markAllResponse->assertRedirect();

        $this->assertTrue($notif2->fresh()->isRead);
        $this->assertTrue($notif3->fresh()->isRead);

        // Next page view must have 0 unread
        $responseAfterMarkAll = $this->actingAs($superadmin)->get('/admin/reports');
        $responseAfterMarkAll->assertStatus(200);
        $responseAfterMarkAll->assertDontSee('min-w-4.5 h-4.5 px-1 bg-red-500');
    }

    public function test_superadmin_notification_bell_renders_on_superadmin_dashboard(): void
    {
        $superadmin = User::factory()->create([
            'email' => 'superadmin_dash@example.com',
            'role' => 'superadmin',
            'status' => 'active',
            'isVerified' => true,
        ]);

        Notification::create([
            'userId' => $superadmin->id,
            'title' => 'New Product Listed',
            'message' => 'Product awaiting review',
            'type' => 'system',
            'link' => '/admin/products',
            'targetRole' => 'admin',
            'isRead' => false,
        ]);

        // Superadmin dashboard extends layouts.superadmin
        $response = $this->actingAs($superadmin)->get('/superadmin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Super Admin Notifications');
        $response->assertSee('New Product Listed');
    }
}

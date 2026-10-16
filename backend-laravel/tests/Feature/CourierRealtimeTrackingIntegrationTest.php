<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use App\Services\CourierTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourierRealtimeTrackingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $seller;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'id'           => (string) Str::uuid(),
            'name'         => 'Juan Dela Cruz',
            'username'     => 'juandelacruz',
            'email'        => 'juan@example.com',
            'password'     => Hash::make('Password123!'),
            'role'         => 'customer',
            'status'       => 'active',
            'mobileNumber' => '09171234567',
            'isVerified'   => true,
        ]);

        $this->seller = User::create([
            'id'           => (string) Str::uuid(),
            'name'         => 'Lumban Heritage Workshop',
            'username'     => 'lumbanheritage',
            'email'        => 'seller@example.com',
            'password'     => Hash::make('Password123!'),
            'role'         => 'seller',
            'shopName'     => 'Lumban Heritage Barong',
            'status'       => 'active',
            'mobileNumber' => '09181234567',
            'isVerified'   => true,
        ]);

        $this->product = Product::create([
            'id'          => (string) Str::uuid(),
            'sellerId'    => $this->seller->id,
            'name'        => 'Heritage Piña Barong',
            'description' => 'Authentic hand-embroidered barong',
            'price'       => 1200.00,
            'sale_price'  => 1200.00,
            'stock'       => 10,
            'status'      => 'active',
            'category'    => 'Barong Tagalog',
        ]);
    }

    public function test_carrier_code_mapping_identifies_philippine_couriers()
    {
        $service = new CourierTrackingService();

        $this->assertEquals('jtexpress-ph', $service->getCarrierCode('J&T Express'));
        $this->assertEquals('jtexpress-ph', $service->getCarrierCode('J&T'));
        $this->assertEquals('jtexpress-ph', $service->getCarrierCode('JNT Express'));

        $this->assertEquals('lbc', $service->getCarrierCode('LBC Express'));
        $this->assertEquals('lbc', $service->getCarrierCode('LBC'));

        $this->assertEquals('flashexpress-ph', $service->getCarrierCode('Flash Express'));
        $this->assertEquals('flashexpress-ph', $service->getCarrierCode('Flash'));

        $this->assertEquals('ninjavan-ph', $service->getCarrierCode('Ninja Van'));
        $this->assertEquals('2go', $service->getCarrierCode('2GO Express'));
        $this->assertEquals('jrs-express', $service->getCarrierCode('JRS Express'));

        $this->assertNull($service->getCarrierCode('Store Pickup'));
        $this->assertNull($service->getCarrierCode('Special Delivery (Local Artisan Rider)'));
    }

    public function test_direct_jt_express_tracking_resolver_parses_live_checkpoints()
    {
        Http::fake([
            'https://www.jtexpress.ph/index/router/index.html' => Http::response([
                'code' => 1,
                'data' => [
                    [
                        'billcode' => '781234567890',
                        'details' => [
                            [
                                'scantype' => 'Departure',
                                'desc'     => 'Departure from Manila Sorting Center to Laguna Hub',
                                'scantime' => '2026-10-17 06:00:00'
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $service = new CourierTrackingService();
        $tracking = $service->fetchTracking('J&T Express', '781234567890');

        $this->assertNotNull($tracking);
        $this->assertEquals('transit', $tracking['delivery_status']);
        $this->assertStringContainsString('Laguna Hub', $tracking['latest_event']);
    }

    public function test_direct_flash_express_tracking_resolver_parses_delivery_status()
    {
        Http::fake([
            'https://www.flashexpress.ph/tools/tracking/' => Http::response(
                '<html><body><div>In Transit - Arrived at Regional Hub</div></body></html>',
                200
            ),
        ]);

        $service = new CourierTrackingService();
        $tracking = $service->fetchTracking('Flash Express', 'FL123456789');

        $this->assertNotNull($tracking);
        $this->assertEquals('transit', $tracking['delivery_status']);
    }

    public function test_courier_webhook_updates_order_to_in_transit_and_notifies_customer()
    {
        $order = Order::create([
            'id'              => (string) Str::uuid(),
            'customerId'      => $this->customer->id,
            'sellerId'        => $this->seller->id,
            'totalAmount'     => 1200.00,
            'status'          => 'Shipped',
            'courierName'     => 'J&T Express',
            'trackingNumber'  => '781999888111',
            'paymentMethod'   => 'COD',
            'paymentStatus'   => 'Pending Payment (COD)',
            'shippingAddress' => ['city' => 'Santa Cruz', 'province' => 'Laguna'],
        ]);

        $response = $this->postJson('/api/webhooks/courier-tracking', [
            'tracking_number' => '781999888111',
            'delivery_status' => 'transit',
            'description'     => 'Parcel departure from Manila Hub to Southern Luzon Hub',
            'timestamp'       => '2026-10-17 08:30:00',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify order status updated to In Transit
        $this->assertEquals('In Transit', $order->fresh()->status);

        // Verify audit log created
        $this->assertDatabaseHas('order_status_histories', [
            'orderId'   => $order->id,
            'newStatus' => 'In Transit',
            'userRole'  => 'system',
        ]);

        // Verify notification sent to customer
        $this->assertDatabaseHas('notifications', [
            'userId'     => $this->customer->id,
            'targetRole' => 'customer',
            'title'      => 'Parcel In Transit',
        ]);
    }

    public function test_courier_webhook_delivers_parcel_and_marks_cod_as_paid()
    {
        $order = Order::create([
            'id'              => (string) Str::uuid(),
            'customerId'      => $this->customer->id,
            'sellerId'        => $this->seller->id,
            'totalAmount'     => 1200.00,
            'status'          => 'In Transit',
            'courierName'     => 'Flash Express',
            'trackingNumber'  => 'FL888777666',
            'paymentMethod'   => 'COD',
            'paymentStatus'   => 'Pending Payment (COD)',
            'shippingAddress' => ['city' => 'Calamba', 'province' => 'Laguna'],
        ]);

        $response = $this->postJson('/api/webhooks/courier-tracking', [
            'tracking_number' => 'FL888777666',
            'delivery_status' => 'delivered',
            'description'     => 'Delivered and signed by consignee.',
        ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('Delivered', $order->status);
        $this->assertEquals('Paid', $order->paymentStatus);

        // Notification created for Delivered
        $this->assertDatabaseHas('notifications', [
            'userId' => $this->customer->id,
            'title'  => 'Parcel Delivered',
        ]);
    }

    public function test_sync_courier_tracking_artisan_command_runs_cleanly()
    {
        $this->artisan('courier:sync-tracking')
            ->expectsOutputToContain('LumBarong Real-Time Courier Tracking Synchronizer')
            ->assertExitCode(0);
    }
}

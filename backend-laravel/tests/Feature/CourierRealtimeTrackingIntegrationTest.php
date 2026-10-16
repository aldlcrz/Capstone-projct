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

    public function test_courier_tracking_registration_sends_http_request_when_api_key_configured()
    {
        config(['services.trackingmore.api_key' => 'test_api_key_12345']);

        Http::fake([
            'https://api.trackingmore.com/v4/trackings/create' => Http::response([
                'meta' => ['code' => 200, 'message' => 'Success'],
                'data' => ['tracking_number' => '781234567890', 'courier_code' => 'jtexpress-ph']
            ], 200),
        ]);

        $service = new CourierTrackingService();
        $result = $service->registerTracking('J&T Express', '781234567890', 'order-uuid-1');

        $this->assertTrue($result['success']);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.trackingmore.com/v4/trackings/create'
                && $request['tracking_number'] === '781234567890'
                && $request['courier_code'] === 'jtexpress-ph'
                && $request->hasHeader('Tracking-Api-Key', 'test_api_key_12345');
        });
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
            'event' => 'tracking_update',
            'data' => [
                'tracking_number'        => '781999888111',
                'courier_code'           => 'jtexpress-ph',
                'delivery_status'        => 'transit',
                'latest_event'           => 'Parcel departure from Manila Hub to Southern Luzon Hub',
                'latest_checkpoint_time' => '2026-10-17 08:30:00',
            ]
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

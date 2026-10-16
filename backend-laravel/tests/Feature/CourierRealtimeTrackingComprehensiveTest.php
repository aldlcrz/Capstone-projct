<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipping;
use App\Models\Product;
use App\Models\ShipmentTrackingEvent;
use App\Models\User;
use App\Services\CourierTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourierRealtimeTrackingComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $seller;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::create([
            'id'           => (string) \Illuminate\Support\Str::uuid(),
            'name'         => 'Lumban Master Artisan',
            'username'     => 'artisan_lumban',
            'email'        => 'artisan@lumban.test',
            'password'     => \Illuminate\Support\Facades\Hash::make('Password123!'),
            'role'         => 'seller',
            'shopName'     => 'Lumban Master Artisan Shop',
            'status'       => 'active',
            'mobileNumber' => '09181234567',
            'isVerified'   => true,
        ]);

        $this->customer = User::create([
            'id'           => (string) \Illuminate\Support\Str::uuid(),
            'name'         => 'Maria Clara',
            'username'     => 'mariaclara',
            'email'        => 'maria@lumban.test',
            'password'     => \Illuminate\Support\Facades\Hash::make('Password123!'),
            'role'         => 'customer',
            'status'       => 'active',
            'mobileNumber' => '09171234567',
            'isVerified'   => true,
        ]);

        $this->product = Product::create([
            'id'          => (string) \Illuminate\Support\Str::uuid(),
            'sellerId'    => $this->seller->id,
            'name'        => 'Hand-Embroidered Barong Tagalog',
            'description' => 'Fine piña hand embroidery from Lumban Laguna',
            'price'       => 4500.00,
            'stock'       => 10,
            'status'      => 'active',
        ]);

        \App\Models\ShippingProvider::firstOrCreate([
            'code' => 'jtexpress-ph',
        ], [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'J&T Express Philippines',
            'is_active' => true,
        ]);
    }

    protected function createStandardOrder(string $status = 'To Ship', string $paymentMethod = 'COD'): Order
    {
        $order = Order::create([
            'customerId'      => $this->customer->id,
            'sellerId'        => $this->seller->id,
            'totalAmount'     => 4500.00,
            'status'          => $status,
            'paymentMethod'   => $paymentMethod,
            'paymentStatus'   => ($paymentMethod === 'COD') ? 'Pending' : 'Paid',
            'courierName'     => 'J&T Express Philippines',
            'trackingNumber'  => 'JNT-TEST-998877',
            'trackingLink'    => 'https://www.jtexpress.ph/index/query/gzquery.html?bills=JNT-TEST-998877',
            'shippingAddress' => json_encode([
                'fullName'   => 'Maria Clara',
                'phone'      => '09171234567',
                'address'    => 'Poblacion',
                'city'       => 'Lumban',
                'province'   => 'Laguna',
                'postalCode' => '4014',
            ]),
        ]);

        $provider = \App\Models\ShippingProvider::where('code', 'jtexpress-ph')->first();

        OrderShipping::create([
            'order_id'                       => $order->id,
            'provider_id'                    => $provider->id,
            'provider_name'                  => 'Standard Delivery',
            'fulfillment_provider_name'      => 'J&T Express Philippines',
            'origin_zone_name'               => 'Lumban Origin',
            'destination_zone_name'          => 'Laguna Destination',
            'actual_weight'                  => 0.5,
            'volumetric_weight'              => 0.5,
            'chargeable_weight'              => 0.5,
            'rate_base_snapshot'             => 150.00,
            'additional_weight_rate_snapshot'=> 0.00,
            'volumetric_divisor_snapshot'    => 3500,
            'shipping_fee'                   => 150.00,
            'estimated_days_min'             => 2,
            'estimated_days_max'             => 4,
            'tracking_number'                => 'JNT-TEST-998877',
            'shipping_status'                => $status,
        ]);

        return $order;
    }

    public function test_waybill_registration_preserves_order_status_without_premature_in_transit(): void
    {
        $order = $this->createStandardOrder('To Ship');
        $service = app(CourierTrackingService::class);

        $result = $service->registerTracking('J&T Express Philippines', 'JNT-TEST-998877', $order->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('To Ship', $order->fresh()->status);

        // A registered checkpoint exists in shipment_tracking_events
        $event = ShipmentTrackingEvent::where('order_id', $order->id)->first();
        $this->assertNotNull($event);
        $this->assertEquals('registered', $event->checkpoint_status);
    }

    public function test_genuine_pickup_scan_advances_order_to_in_transit_and_notifies_customer(): void
    {
        $order = $this->createStandardOrder('To Ship');
        $service = app(CourierTrackingService::class);

        $result = $service->recordTrackingEvent(
            $order,
            'picked_up',
            'Parcel collected by rider at Lumban Workshop',
            'Lumban Sorting Hub',
            now()->toDateTimeString()
        );

        $this->assertTrue($result['recorded']);
        $this->assertEquals('In Transit', $order->fresh()->status);
        $this->assertEquals('In Transit', $order->fresh()->shipping->shipping_status);

        $this->assertDatabaseHas('notifications', [
            'userId' => $this->customer->id,
            'title'  => 'Parcel In Transit',
        ]);
    }

    public function test_duplicate_webhook_payload_is_deduplicated_atomically_without_extra_notifications(): void
    {
        $order = $this->createStandardOrder('To Ship');
        $service = app(CourierTrackingService::class);
        $timestamp = now()->toDateTimeString();

        $res1 = $service->recordTrackingEvent(
            $order,
            'in_transit',
            'Arrived at Sortation Hub',
            'Cabuyao Laguna Hub',
            $timestamp
        );
        $this->assertTrue($res1['recorded']);
        $notifCount = DB::table('notifications')->where('userId', $this->customer->id)->count();

        // Exact duplicate delivery replay
        $res2 = $service->recordTrackingEvent(
            $order,
            'in_transit',
            'Arrived at Sortation Hub',
            'Cabuyao Laguna Hub',
            $timestamp
        );

        $this->assertFalse($res2['recorded']);
        $this->assertTrue($res2['is_duplicate'] ?? false);

        // Notifications count did NOT increase
        $this->assertEquals($notifCount, DB::table('notifications')->where('userId', $this->customer->id)->count());
        $this->assertEquals(1, ShipmentTrackingEvent::where('order_id', $order->id)->count());
    }

    public function test_invalid_webhook_signature_is_rejected_with_401(): void
    {
        $response = $this->postJson('/api/webhooks/courier/jtexpress', [
            'tracking_number' => 'JNT-TEST-998877',
            'status'          => 'in_transit',
        ], [
            'X-Lumbarong-Signature' => 'invalid-forged-signature',
        ]);

        $response->assertStatus(401);
    }

    public function test_valid_webhook_with_secret_processes_event(): void
    {
        $order = $this->createStandardOrder('To Ship');

        $response = $this->postJson('/api/webhooks/courier/jtexpress', [
            'tracking_number' => 'JNT-TEST-998877',
            'status'          => 'picked_up',
            'description'     => 'Picked up by courier rider',
            'location'        => 'Lumban Hub',
            'timestamp'       => now()->toDateTimeString(),
        ], [
            'X-Lumbarong-Signature' => 'lumbarong_courier_test_secret_2026',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('In Transit', $order->fresh()->status);
    }

    public function test_failed_delivery_checkpoint_does_not_regress_or_cancel_order(): void
    {
        $order = $this->createStandardOrder('In Transit');
        $service = app(CourierTrackingService::class);

        $result = $service->recordTrackingEvent(
            $order,
            'failed_delivery',
            'Consignee unavailable at delivery address',
            'Santa Cruz Delivery Area',
            now()->toDateTimeString()
        );

        $this->assertTrue($result['recorded']);
        $this->assertEquals('In Transit', $order->fresh()->status); // Remains In Transit

        $this->assertDatabaseHas('notifications', [
            'userId' => $this->customer->id,
            'title'  => 'Delivery Attempt Notice',
        ]);
    }

    public function test_returned_scan_logs_checkpoint_without_auto_cancelling_or_restoring_stock(): void
    {
        $order = $this->createStandardOrder('In Transit');
        $initialStock = $this->product->fresh()->stock;
        $service = app(CourierTrackingService::class);

        $result = $service->recordTrackingEvent(
            $order,
            'returned',
            'Parcel returned to sender by carrier',
            'Lumban Return Facility',
            now()->toDateTimeString()
        );

        $this->assertTrue($result['recorded']);
        // Order remains in transit / held for inspection (NEVER auto-cancelled)
        $this->assertEquals('In Transit', $order->fresh()->status);
        // Stock was NOT restored
        $this->assertEquals($initialStock, $this->product->fresh()->stock);
    }

    public function test_delivered_scan_updates_order_to_delivered_but_never_completed(): void
    {
        $order = $this->createStandardOrder('In Transit');
        $service = app(CourierTrackingService::class);

        $result = $service->recordTrackingEvent(
            $order,
            'delivered',
            'Parcel signed and received by consignee',
            'Lumban Laguna',
            now()->toDateTimeString()
        );

        $this->assertTrue($result['recorded']);
        $this->assertEquals('Delivered', $order->fresh()->status);
        $this->assertNotEquals('Completed', $order->fresh()->status); // Escrow remains safe
    }

    public function test_delivered_scan_marks_cod_as_paid_while_escrow_remains_safely_held(): void
    {
        $order = $this->createStandardOrder('In Transit', 'COD');
        $this->assertEquals('Pending', $order->paymentStatus);

        $service = app(CourierTrackingService::class);
        $service->recordTrackingEvent(
            $order,
            'delivered',
            'Delivered with COD payment collected by rider',
            'Lumban Laguna',
            now()->toDateTimeString()
        );

        $fresh = $order->fresh();
        $this->assertEquals('Delivered', $fresh->status);
        $this->assertEquals('Paid', $fresh->paymentStatus); // Payment marked collected
        $this->assertNotEquals('Completed', $fresh->status); // But order is NOT completed
    }
}

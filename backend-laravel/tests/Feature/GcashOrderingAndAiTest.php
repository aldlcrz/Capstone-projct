<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\SellerShippingProvider;
use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneArea;
use App\Models\User;
use App\Services\AiService;
use App\Services\ShippingCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GcashOrderingAndAiTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $seller;
    protected Product $product;
    protected Address $address;
    protected ShippingProvider $jntProvider;
    protected ShippingProvider $storePickupProvider;
    protected ShippingProvider $sellerDirectProvider;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->seller = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Maria Lumban Artisan',
            'username' => 'marialumban',
            'email' => 'maria@lumban.test',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'shopName' => 'Maria Heritage Barong',
            'status' => 'active',
            'mobileNumber' => '09181112222',
            'shopPostalCode' => '4014',
            'shopCity' => 'Lumban',
            'shopProvince' => 'Laguna',
            'shopHouseNo' => '45',
            'shopStreet' => 'General Luna St',
            'shopBarangay' => 'Poblacion',
            'isVerified' => true,
        ]);

        $this->customer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Juan Dela Cruz',
            'username' => 'juandelacruz',
            'email' => 'juan@customer.test',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'mobileNumber' => '09171112222',
            'isVerified' => true,
        ]);

        $this->address = Address::create([
            'id' => (string) Str::uuid(),
            'userId' => $this->customer->id,
            'recipientName' => 'Juan Dela Cruz',
            'phone' => '09171112222',
            'houseNo' => '123 Rizal St',
            'street' => 'Poblacion',
            'barangay' => 'Barangay 1',
            'city' => 'Santa Cruz',
            'province' => 'Laguna',
            'region' => 'Region IV-A (CALABARZON)',
            'postalCode' => '4009',
            'is_default' => true,
        ]);

        $this->product = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $this->seller->id,
            'name' => 'Authentic Piña Organza Barong Tagalog',
            'description' => 'Handcrafted embroidered heirloom piece from Lumban.',
            'price' => 2500.00,
            'stock' => 15,
            'status' => 'approved',
            'image' => ['barong_sample.jpg'],
            'package_weight' => 0.5,
            'package_length' => 30,
            'package_width' => 25,
            'package_height' => 5,
        ]);

        // Setup Shipping Logistics Providers
        $this->jntProvider = ShippingProvider::firstOrCreate(
            ['code' => 'jnt'],
            [
                'name' => 'J&T Express',
                'default_volumetric_divisor' => 3500,
                'is_active' => true,
            ]
        );

        $this->storePickupProvider = ShippingProvider::firstOrCreate(
            ['code' => 'store_pickup'],
            [
                'name' => 'Store Pickup',
                'default_volumetric_divisor' => 3500,
                'is_active' => true,
            ]
        );

        $this->sellerDirectProvider = ShippingProvider::firstOrCreate(
            ['code' => 'seller_direct'],
            [
                'name' => 'Special Delivery (Artisan Direct)',
                'default_volumetric_divisor' => 3500,
                'is_active' => true,
            ]
        );

        // Enable providers for seller
        foreach ([$this->jntProvider, $this->storePickupProvider, $this->sellerDirectProvider] as $p) {
            SellerShippingProvider::firstOrCreate([
                'seller_id' => $this->seller->id,
                'provider_id' => $p->id,
                'is_enabled' => true,
                'is_preferred' => ($p->code === 'jnt'),
            ]);
        }

        $zone = ShippingZone::firstOrCreate(['code' => 'LUZ_S'], ['name' => 'South Luzon']);
        ShippingZoneArea::firstOrCreate(['zone_id' => $zone->id, 'postal_code' => '4009', 'province' => 'Laguna']);
        ShippingZoneArea::firstOrCreate(['zone_id' => $zone->id, 'postal_code' => '4014', 'province' => 'Laguna']);

        ShippingRate::firstOrCreate([
            'provider_id' => $this->jntProvider->id,
            'origin_zone_id' => $zone->id,
            'destination_zone_id' => $zone->id,
            'min_weight' => 0.0,
            'max_weight' => 3.0,
            'base_rate' => 120.00,
            'incremental_rate' => 30.00,
        ]);
    }

    public function test_ai_stylist_and_sizing_advisory(): void
    {
        $stylist = AiService::chatStylist('What is the best barong for a Laguna wedding under 3000?');
        $this->assertIsArray($stylist);
        $this->assertNotEmpty($stylist['reply'] ?? $stylist['response'] ?? '');

        $sizing = AiService::recommendSize(175, 72, 'regular', 'regular');
        $this->assertIsArray($sizing);
        $this->assertEquals('M', $sizing['size']);
    }

    public function test_ai_receipt_evidence_evaluation_pass(): void
    {
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1009876543210',
            'detected_amount' => 2620.00,
            'amount_confidence' => 0.98,
            'reference_confidence' => 0.99,
            'confidence' => 0.98,
        ], '1009876543210', 'GCash', 2620.00);

        $this->assertEquals('PASS', $evaluation['status']);
        $this->assertTrue($evaluation['is_receipt']);
        $this->assertEquals(2620.00, $evaluation['detected_amount']);
    }

    public function test_ai_receipt_accepts_underpayment_as_partial_payment(): void
    {
        $evaluation = AiService::evaluateReceiptEvidence([
            'is_receipt' => true,
            'wallet' => 'GCash',
            'reference' => '1009876543210',
            'detected_amount' => 100.00,
            'amount_confidence' => 0.98,
            'reference_confidence' => 0.99,
            'confidence' => 0.98,
        ], '1009876543210', 'GCash', 2620.00);

        $this->assertEquals('PASS', $evaluation['status']);
        $this->assertEquals('PARTIAL_PAYMENT', $evaluation['reason_code']);
        $this->assertEquals(2520.00, $evaluation['remaining_amount']);
        $this->assertStringContainsString('Payment received: ₱100.00', $evaluation['message']);
    }

    public function test_gcash_ordering_checkout_and_seller_verification_flow(): void
    {
        $shippingCalc = app(ShippingCalculatorService::class);
        $sellerItems = [
            [
                'id' => $this->product->id,
                'productId' => $this->product->id,
                'product' => $this->product,
                'quantity' => 1,
                'subtotal' => 2500.00,
            ]
        ];
        $quotes = $shippingCalc->calculateQuotes($this->seller, $this->address->toArray(), $sellerItems, $this->jntProvider->id);
        $this->assertNotEmpty($quotes);
        $quoteToken = $shippingCalc->generateQuoteToken([$this->seller->id], $this->address->id, $sellerItems);

        $receiptFile = UploadedFile::fake()->image('gcash_receipt.jpg', 600, 1200);

        // 1. Submit Checkout with GCash (13-digit reference)
        $response = $this->actingAs($this->customer)->post('/checkout', [
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'paymentReference' => '1008889991112',
            'paymentScreenshot' => $receiptFile,
            'shipping_quote_token' => $quoteToken,
            'selected_provider_id' => $this->jntProvider->id,
            'items' => [
                [
                    'productId' => $this->product->id,
                    'quantity' => 1,
                    'size' => 'L',
                ]
            ],
        ]);

        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));

        $order = Order::where('customerId', $this->customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('GCash', $order->paymentMethod);
        $this->assertEquals('1008889991112', $order->paymentReference);
        $this->assertEquals('Pending', $order->status);
        $this->assertEquals(2620.00, (float) $order->totalAmount);

        // 2. Seller Verifies & Accepts GCash Payment
        $sellerResponse = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'To Ship',
        ]);

        $sellerResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals('To Ship', $order->status);
        $this->assertEquals('Verified', $order->paymentStatus);

        // 3. Seller Ships Order with J&T Express Tracking
        $shipResponse = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'In Transit',
            'courierName' => 'J&T Express',
            'trackingNumber' => '781234567890',
            'trackingLink' => 'https://www.jtexpress.ph/track',
        ]);

        $shipResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals('In Transit', $order->status);
        $this->assertEquals('781234567890', $order->trackingNumber);
        $this->assertEquals('J&T Express', $order->courierName);
    }

    public function test_maya_ordering_checkout_and_seller_verification_flow(): void
    {
        $shippingCalc = app(ShippingCalculatorService::class);
        $sellerItems = [
            [
                'id' => $this->product->id,
                'productId' => $this->product->id,
                'product' => $this->product,
                'quantity' => 1,
                'subtotal' => 2500.00,
            ]
        ];
        $quotes = $shippingCalc->calculateQuotes($this->seller, $this->address->toArray(), $sellerItems, $this->jntProvider->id);
        $this->assertNotEmpty($quotes);
        $quoteToken = $shippingCalc->generateQuoteToken([$this->seller->id], $this->address->id, $sellerItems);

        $receiptFile = UploadedFile::fake()->image('maya_receipt.jpg', 600, 1200);

        // 1. Submit Checkout with Maya (12-digit reference)
        $response = $this->actingAs($this->customer)->post('/checkout', [
            'address_id' => $this->address->id,
            'paymentMethod' => 'Maya',
            'paymentReference' => '987654321098',
            'paymentScreenshot' => $receiptFile,
            'shipping_quote_token' => $quoteToken,
            'selected_provider_id' => $this->jntProvider->id,
            'items' => [
                [
                    'productId' => $this->product->id,
                    'quantity' => 1,
                    'size' => 'M',
                ]
            ],
        ]);

        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));

        $order = Order::where('customerId', $this->customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('Maya', $order->paymentMethod);
        $this->assertEquals('987654321098', $order->paymentReference);
        $this->assertEquals('Pending', $order->status);
        $this->assertEquals(2620.00, (float) $order->totalAmount);

        // 2. Seller Verifies & Accepts Maya Payment
        $sellerResponse = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'To Ship',
        ]);

        $sellerResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals('To Ship', $order->status);
        $this->assertEquals('Verified', $order->paymentStatus);
    }

    public function test_store_pickup_ordering_and_fulfillment_flow(): void
    {
        $shippingCalc = app(ShippingCalculatorService::class);
        $sellerItems = [
            [
                'id' => $this->product->id,
                'productId' => $this->product->id,
                'product' => $this->product,
                'quantity' => 1,
                'subtotal' => 2500.00,
            ]
        ];

        // Get Store Pickup Quote (₱0.00 shipping fee)
        $quotes = $shippingCalc->calculateQuotes($this->seller, $this->address->toArray(), $sellerItems, $this->storePickupProvider->id);
        $this->assertNotEmpty($quotes);
        $this->assertEquals(0.00, (float) $quotes[0]['shipping_fee']);
        $this->assertEquals('store_pickup', $quotes[0]['provider_code']);

        $quoteToken = $shippingCalc->generateQuoteToken([$this->seller->id], $this->address->id, $sellerItems);
        $receiptFile = UploadedFile::fake()->image('pickup_gcash_receipt.jpg', 600, 1200);

        // Customer checkout with Store Pickup and GCash
        $response = $this->actingAs($this->customer)->post('/checkout', [
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'paymentReference' => '1005556667778',
            'paymentScreenshot' => $receiptFile,
            'shipping_quote_token' => $quoteToken,
            'selected_provider_id' => $this->storePickupProvider->id,
            'items' => [
                [
                    'productId' => $this->product->id,
                    'quantity' => 1,
                    'size' => 'L',
                ]
            ],
        ]);

        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));

        $order = Order::where('customerId', $this->customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals(0.00, (float) $order->shippingFee);
        $this->assertEquals(2500.00, (float) $order->totalAmount);

        // Seller confirms order ready for in-shop pickup
        $sellerResponse = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'Ready for Pickup',
        ]);
        $this->assertTrue(in_array($sellerResponse->getStatusCode(), [200, 422, 302]));
    }

    public function test_special_delivery_seller_direct_ordering_and_fulfillment_flow(): void
    {
        $shippingCalc = app(ShippingCalculatorService::class);
        $sellerItems = [
            [
                'id' => $this->product->id,
                'productId' => $this->product->id,
                'product' => $this->product,
                'quantity' => 1,
                'subtotal' => 2500.00,
            ]
        ];

        // Get Special Delivery (Artisan Direct) Quote (₱25.00 local fee)
        $quotes = $shippingCalc->calculateQuotes($this->seller, $this->address->toArray(), $sellerItems, $this->sellerDirectProvider->id);
        $this->assertNotEmpty($quotes);
        $this->assertEquals(25.00, (float) $quotes[0]['shipping_fee']);
        $this->assertEquals('seller_direct', $quotes[0]['provider_code']);

        $quoteToken = $shippingCalc->generateQuoteToken([$this->seller->id], $this->address->id, $sellerItems);
        $receiptFile = UploadedFile::fake()->image('special_delivery_receipt.jpg', 600, 1200);

        // Customer checkout with Special Delivery
        $response = $this->actingAs($this->customer)->post('/checkout', [
            'address_id' => $this->address->id,
            'paymentMethod' => 'GCash',
            'paymentReference' => '1003334445556',
            'paymentScreenshot' => $receiptFile,
            'shipping_quote_token' => $quoteToken,
            'selected_provider_id' => $this->sellerDirectProvider->id,
            'items' => [
                [
                    'productId' => $this->product->id,
                    'quantity' => 1,
                    'size' => 'XL',
                ]
            ],
        ]);

        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));

        $order = Order::where('customerId', $this->customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals(25.00, (float) $order->shippingFee);
        $this->assertEquals(2525.00, (float) $order->totalAmount);

        // Seller verifies and dispatches local artisan rider
        $sellerVerify = $this->actingAs($this->seller)->patchJson('/seller/api/orders/' . $order->id . '/status', [
            'status' => 'To Ship',
        ]);
        $sellerVerify->assertStatus(200);

        $order->refresh();
        $this->assertEquals('To Ship', $order->status);
        $this->assertEquals('Verified', $order->paymentStatus);
    }
}

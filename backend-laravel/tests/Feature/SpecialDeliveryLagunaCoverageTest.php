<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderShipping;
use App\Models\Product;
use App\Models\SellerShippingProvider;
use App\Models\SellerSpecialDeliveryRate;
use App\Models\ShippingProvider;
use App\Models\User;
use App\Services\Shipping\LagunaMunicipalityCatalog;
use App\Services\ShippingCalculatorService;
use Database\Seeders\ShippingLogisticsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SpecialDeliveryLagunaCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected User $sellerA;
    protected User $sellerB;
    protected User $buyer;
    protected ShippingProvider $specialDeliveryProvider;
    protected ShippingProvider $storePickupProvider;
    protected ShippingProvider $jntProvider;
    protected ShippingCalculatorService $shippingCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ShippingLogisticsSeeder::class);

        $this->shippingCalculator = app(ShippingCalculatorService::class);

        // Seed or retrieve logistics providers
        $this->specialDeliveryProvider = ShippingProvider::firstOrCreate(
            ['code' => 'seller_direct'],
            [
                'name' => 'Special Delivery (Local Artisan Rider)',
                'provider_type' => 'custom',
                'is_active' => true,
                'default_volumetric_divisor' => 3500,
            ]
        );

        $this->storePickupProvider = ShippingProvider::firstOrCreate(
            ['code' => 'store_pickup'],
            [
                'name' => 'Store Pickup (In-Shop Collection)',
                'provider_type' => 'custom',
                'is_active' => true,
                'default_volumetric_divisor' => 3500,
            ]
        );

        $this->jntProvider = ShippingProvider::firstOrCreate(
            ['code' => 'jnt'],
            [
                'name' => 'J&T Express',
                'provider_type' => 'integrated',
                'is_active' => true,
                'default_volumetric_divisor' => 3500,
            ]
        );

        // Create Sellers
        $this->sellerA = User::create([
            'role' => 'seller',
            'name' => 'Artisan Seller Alpha',
            'email' => 'seller_alpha_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'shopName' => 'Alpha Barong Lumban',
            'shopProvince' => 'Laguna',
            'shopCity' => 'Lumban',
            'shopPostalCode' => '4014',
            'status' => 'approved',
            'isVerified' => true,
        ]);

        $this->sellerB = User::create([
            'role' => 'seller',
            'name' => 'Artisan Seller Beta',
            'email' => 'seller_beta_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'shopName' => 'Beta Barong Paete',
            'shopProvince' => 'Laguna',
            'shopCity' => 'Paete',
            'shopPostalCode' => '4016',
            'status' => 'approved',
            'isVerified' => true,
        ]);

        // Create Buyer
        $this->buyer = User::create([
            'role' => 'customer',
            'name' => 'Laguna Buyer',
            'email' => 'buyer_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'status' => 'approved',
            'isVerified' => true,
        ]);

        // Enable standard courier for both sellers
        SellerShippingProvider::create([
            'seller_id' => $this->sellerA->id,
            'provider_id' => $this->jntProvider->id,
            'is_enabled' => true,
        ]);
        SellerShippingProvider::create([
            'seller_id' => $this->sellerB->id,
            'provider_id' => $this->jntProvider->id,
            'is_enabled' => true,
        ]);
    }

    public function test_seller_can_view_special_delivery_settings_page(): void
    {
        $response = $this->actingAs($this->sellerA)->get(route('seller.special-delivery.index'));
        $response->assertStatus(200);
        $response->assertSee('Special Delivery Settings');
        $response->assertSee('Laguna, Philippines');
        $response->assertSee('Lumban');
        $response->assertSee('Pagsanjan');
        $response->assertSee('Santa Cruz');
    }

    public function test_seller_can_configure_and_save_special_delivery_coverage_and_surcharges(): void
    {
        $payload = [
            'special_delivery_enabled' => '1',
            'base_delivery_fee' => '50.00',
            'municipalities' => ['lumban', 'pagsanjan', 'santa_cruz'],
            'surcharges' => [
                'lumban' => '0.00',
                'pagsanjan' => '20.00',
                'santa_cruz' => '30.00',
            ],
        ];

        $response = $this->actingAs($this->sellerA)->post(route('seller.special-delivery.update'), $payload);
        $response->assertRedirect(route('seller.special-delivery.index'));
        $response->assertSessionHas('success');

        // Verify seller_shipping_providers table updated
        $sellerProvider = SellerShippingProvider::where('seller_id', $this->sellerA->id)
            ->where('provider_id', $this->specialDeliveryProvider->id)
            ->first();

        $this->assertNotNull($sellerProvider);
        $this->assertTrue((bool)$sellerProvider->is_enabled);
        $this->assertEquals(50.00, (float)$sellerProvider->custom_fee);

        // Verify seller_special_delivery_rates table updated
        $rates = SellerSpecialDeliveryRate::where('seller_id', $this->sellerA->id)->get();
        $this->assertCount(3, $rates);

        $lumbanRate = $rates->firstWhere('municipality_key', 'lumban');
        $this->assertNotNull($lumbanRate);
        $this->assertEquals(0.00, (float)$lumbanRate->surcharge);
        $this->assertTrue((bool)$lumbanRate->is_enabled);

        $pagsanjanRate = $rates->firstWhere('municipality_key', 'pagsanjan');
        $this->assertNotNull($pagsanjanRate);
        $this->assertEquals(20.00, (float)$pagsanjanRate->surcharge);

        $staCruzRate = $rates->firstWhere('municipality_key', 'santa_cruz');
        $this->assertNotNull($staCruzRate);
        $this->assertEquals(30.00, (float)$staCruzRate->surcharge);
    }

    public function test_seller_cannot_submit_invalid_or_unsupported_municipality_keys(): void
    {
        $payload = [
            'special_delivery_enabled' => '1',
            'base_delivery_fee' => '50.00',
            'municipalities' => ['manila_central', 'cavite_city', 'lumban'],
            'surcharges' => [
                'manila_central' => '100.00',
                'cavite_city' => '50.00',
                'lumban' => '10.00',
            ],
        ];

        $response = $this->actingAs($this->sellerA)->post(route('seller.special-delivery.update'), $payload);
        $response->assertRedirect(route('seller.special-delivery.index'));

        // Only valid Laguna keys should be persisted
        $rates = SellerSpecialDeliveryRate::where('seller_id', $this->sellerA->id)->get();
        $this->assertCount(1, $rates);
        $this->assertEquals('lumban', $rates->first()->municipality_key);
        $this->assertEquals(10.00, (float)$rates->first()->surcharge);
    }

    public function test_seller_cannot_modify_another_sellers_special_delivery_settings(): void
    {
        // Seller A configures settings
        SellerSpecialDeliveryRate::create([
            'seller_id' => $this->sellerA->id,
            'municipality_key' => 'lumban',
            'municipality_name' => 'Lumban',
            'surcharge' => 15.00,
            'is_enabled' => true,
        ]);

        // Seller B configures their own settings
        $payload = [
            'special_delivery_enabled' => '1',
            'base_delivery_fee' => '60.00',
            'municipalities' => ['paete'],
            'surcharges' => [
                'paete' => '5.00',
            ],
        ];

        $this->actingAs($this->sellerB)->post(route('seller.special-delivery.update'), $payload);

        // Seller A's rate remains untouched
        $sellerARate = SellerSpecialDeliveryRate::where('seller_id', $this->sellerA->id)->first();
        $this->assertEquals('lumban', $sellerARate->municipality_key);
        $this->assertEquals(15.00, (float)$sellerARate->surcharge);

        // Seller B has only their rate
        $sellerBRate = SellerSpecialDeliveryRate::where('seller_id', $this->sellerB->id)->first();
        $this->assertEquals('paete', $sellerBRate->municipality_key);
        $this->assertEquals(5.00, (float)$sellerBRate->surcharge);
    }

    public function test_empty_coverage_disables_special_delivery(): void
    {
        // Seller enables Special Delivery but selects zero municipalities
        $payload = [
            'special_delivery_enabled' => '1',
            'base_delivery_fee' => '50.00',
            'municipalities' => [],
            'surcharges' => [],
        ];

        $this->actingAs($this->sellerA)->post(route('seller.special-delivery.update'), $payload);

        $product = Product::create([
            'sellerId' => $this->sellerA->id,
            'name' => 'Lumban Barong A',
            'price' => 1000,
            'stock' => 10,
        ]);

        $destinationInLaguna = [
            'province' => 'Laguna',
            'city' => 'Lumban',
            'barangay' => 'Poblacion',
            'postalCode' => '4014',
        ];

        // Should NOT quote seller_direct because coverage is empty
        $quotes = $this->shippingCalculator->calculateQuotes($this->sellerA, $destinationInLaguna, [
            ['id' => $product->id, 'quantity' => 1],
        ]);

        $providerCodes = array_column($quotes, 'provider_code');
        $this->assertNotContains('seller_direct', $providerCodes);
    }

    public function test_pricing_calculation_base_plus_surcharge(): void
    {
        // Seller A configures Base Fee: 50.00, Pagsanjan Surcharge: 20.00, Santa Cruz Surcharge: 30.00
        SellerShippingProvider::updateOrCreate(
            ['seller_id' => $this->sellerA->id, 'provider_id' => $this->specialDeliveryProvider->id],
            ['is_enabled' => true, 'custom_fee' => 50.00]
        );

        SellerSpecialDeliveryRate::create([
            'seller_id' => $this->sellerA->id,
            'municipality_key' => 'pagsanjan',
            'municipality_name' => 'Pagsanjan',
            'surcharge' => 20.00,
            'is_enabled' => true,
        ]);

        SellerSpecialDeliveryRate::create([
            'seller_id' => $this->sellerA->id,
            'municipality_key' => 'santa_cruz',
            'municipality_name' => 'Santa Cruz',
            'surcharge' => 30.00,
            'is_enabled' => true,
        ]);

        $product = Product::create([
            'sellerId' => $this->sellerA->id,
            'name' => 'Lumban Barong B',
            'price' => 1200,
            'stock' => 10,
        ]);

        // Quote for Pagsanjan -> Expected Fee = 50 + 20 = 70.00
        $destPagsanjan = [
            'province' => 'Laguna',
            'city' => 'Pagsanjan',
            'barangay' => 'Sampaloc',
            'postalCode' => '4008',
        ];

        $quotesPagsanjan = $this->shippingCalculator->calculateQuotes(
            $this->sellerA,
            $destPagsanjan,
            [['id' => $product->id, 'quantity' => 1]],
            $this->specialDeliveryProvider->id
        );

        $this->assertCount(1, $quotesPagsanjan);
        $this->assertEquals('seller_direct', $quotesPagsanjan[0]['provider_code']);
        $this->assertEquals(70.00, (float)$quotesPagsanjan[0]['shipping_fee']);
        $this->assertEquals(50.00, (float)$quotesPagsanjan[0]['rate_base_snapshot']);
        $this->assertEquals(20.00, (float)$quotesPagsanjan[0]['additional_weight_rate_snapshot']);

        // Quote for Santa Cruz -> Expected Fee = 50 + 30 = 80.00
        $destSantaCruz = [
            'province' => 'Laguna',
            'city' => 'Santa Cruz',
            'barangay' => 'Poblacion',
            'postalCode' => '4009',
        ];

        $quotesSantaCruz = $this->shippingCalculator->calculateQuotes(
            $this->sellerA,
            $destSantaCruz,
            [['id' => $product->id, 'quantity' => 1]],
            $this->specialDeliveryProvider->id
        );

        $this->assertCount(1, $quotesSantaCruz);
        $this->assertEquals(80.00, (float)$quotesSantaCruz[0]['shipping_fee']);
    }

    public function test_unselected_laguna_municipality_is_ineligible_for_special_delivery(): void
    {
        // Seller A only covers Lumban
        SellerShippingProvider::updateOrCreate(
            ['seller_id' => $this->sellerA->id, 'provider_id' => $this->specialDeliveryProvider->id],
            ['is_enabled' => true, 'custom_fee' => 50.00]
        );

        SellerSpecialDeliveryRate::create([
            'seller_id' => $this->sellerA->id,
            'municipality_key' => 'lumban',
            'municipality_name' => 'Lumban',
            'surcharge' => 0.00,
            'is_enabled' => true,
        ]);

        $product = Product::create([
            'sellerId' => $this->sellerA->id,
            'name' => 'Lumban Barong C',
            'price' => 1500,
            'stock' => 10,
        ]);

        // Destination in Calamba (Laguna, but NOT selected by seller A)
        $destCalamba = [
            'province' => 'Laguna',
            'city' => 'Calamba City',
            'barangay' => 'Canlubang',
            'postalCode' => '4027',
        ];

        $quotes = $this->shippingCalculator->calculateQuotes($this->sellerA, $destCalamba, [
            ['id' => $product->id, 'quantity' => 1],
        ]);

        $providerCodes = array_column($quotes, 'provider_code');
        $this->assertNotContains('seller_direct', $providerCodes);
    }

    public function test_destinations_outside_laguna_are_strictly_rejected_for_special_delivery(): void
    {
        // Seller A covers all Laguna municipalities
        SellerShippingProvider::updateOrCreate(
            ['seller_id' => $this->sellerA->id, 'provider_id' => $this->specialDeliveryProvider->id],
            ['is_enabled' => true, 'custom_fee' => 50.00]
        );

        foreach (LagunaMunicipalityCatalog::keys() as $mKey) {
            SellerSpecialDeliveryRate::create([
                'seller_id' => $this->sellerA->id,
                'municipality_key' => $mKey,
                'municipality_name' => LagunaMunicipalityCatalog::getName($mKey),
                'surcharge' => 0.00,
                'is_enabled' => true,
            ]);
        }

        $product = Product::create([
            'sellerId' => $this->sellerA->id,
            'name' => 'Lumban Barong D',
            'price' => 2000,
            'stock' => 10,
        ]);

        // Non-Laguna destinations: Manila, Cavite, Batangas, Cebu
        $outsideDestinations = [
            ['province' => 'Metro Manila', 'city' => 'Manila', 'postalCode' => '1000'],
            ['province' => 'Cavite', 'city' => 'Tagaytay', 'postalCode' => '4120'],
            ['province' => 'Batangas', 'city' => 'Batangas City', 'postalCode' => '4200'],
            ['province' => 'Rizal', 'city' => 'Antipolo', 'postalCode' => '1870'],
            ['province' => 'Cebu', 'city' => 'Cebu City', 'postalCode' => '6000'],
        ];

        foreach ($outsideDestinations as $dest) {
            $quotes = $this->shippingCalculator->calculateQuotes($this->sellerA, $dest, [
                ['id' => $product->id, 'quantity' => 1],
            ]);

            $providerCodes = array_column($quotes, 'provider_code');
            $this->assertNotContains('seller_direct', $providerCodes, "Special delivery should never be offered for {$dest['province']}");
        }
    }

    public function test_multi_vendor_independence_one_seller_coverage_does_not_authorize_another(): void
    {
        // Seller A covers Lumban and Pagsanjan
        SellerShippingProvider::updateOrCreate(
            ['seller_id' => $this->sellerA->id, 'provider_id' => $this->specialDeliveryProvider->id],
            ['is_enabled' => true, 'custom_fee' => 50.00]
        );
        SellerSpecialDeliveryRate::create([
            'seller_id' => $this->sellerA->id,
            'municipality_key' => 'pagsanjan',
            'municipality_name' => 'Pagsanjan',
            'surcharge' => 10.00,
            'is_enabled' => true,
        ]);

        // Seller B covers Paete only
        SellerShippingProvider::updateOrCreate(
            ['seller_id' => $this->sellerB->id, 'provider_id' => $this->specialDeliveryProvider->id],
            ['is_enabled' => true, 'custom_fee' => 60.00]
        );
        SellerSpecialDeliveryRate::create([
            'seller_id' => $this->sellerB->id,
            'municipality_key' => 'paete',
            'municipality_name' => 'Paete',
            'surcharge' => 0.00,
            'is_enabled' => true,
        ]);

        $productA = Product::create([
            'sellerId' => $this->sellerA->id,
            'name' => 'Barong Alpha',
            'price' => 1500,
            'stock' => 10,
        ]);
        $productB = Product::create([
            'sellerId' => $this->sellerB->id,
            'name' => 'Barong Beta',
            'price' => 1800,
            'stock' => 10,
        ]);

        $destPagsanjan = [
            'province' => 'Laguna',
            'city' => 'Pagsanjan',
            'barangay' => 'Poblacion',
            'postalCode' => '4008',
        ];

        // Seller A has Special Delivery for Pagsanjan
        $quotesA = $this->shippingCalculator->calculateQuotes($this->sellerA, $destPagsanjan, [
            ['id' => $productA->id, 'quantity' => 1],
        ]);
        $codesA = array_column($quotesA, 'provider_code');
        $this->assertContains('seller_direct', $codesA);

        // Seller B does NOT have Special Delivery for Pagsanjan
        $quotesB = $this->shippingCalculator->calculateQuotes($this->sellerB, $destPagsanjan, [
            ['id' => $productB->id, 'quantity' => 1],
        ]);
        $codesB = array_column($quotesB, 'provider_code');
        $this->assertNotContains('seller_direct', $codesB);
    }

    public function test_existing_order_preserves_immutable_shipping_fee_snapshot(): void
    {
        // Seller A has ₱50 Base Fee + ₱20 Surcharge = ₱70
        SellerShippingProvider::updateOrCreate(
            ['seller_id' => $this->sellerA->id, 'provider_id' => $this->specialDeliveryProvider->id],
            ['is_enabled' => true, 'custom_fee' => 50.00]
        );

        $rate = SellerSpecialDeliveryRate::create([
            'seller_id' => $this->sellerA->id,
            'municipality_key' => 'pagsanjan',
            'municipality_name' => 'Pagsanjan',
            'surcharge' => 20.00,
            'is_enabled' => true,
        ]);

        // Place Order with Snapshot in order_shipping table
        $order = Order::create([
            'customerId' => $this->buyer->id,
            'sellerId' => $this->sellerA->id,
            'totalAmount' => 1070.00,
            'shippingAddress' => 'Pagsanjan, Laguna',
            'paymentMethod' => 'GCash',
            'paymentStatus' => 'paid',
            'status' => 'processing',
        ]);

        $orderShipping = OrderShipping::create([
            'order_id' => $order->id,
            'provider_id' => $this->specialDeliveryProvider->id,
            'provider_name' => 'Special Delivery (Local Artisan Rider)',
            'origin_zone_name' => 'Lumban / East Laguna Hub',
            'destination_zone_name' => 'Pagsanjan',
            'actual_weight' => 0.50,
            'volumetric_weight' => 0.10,
            'chargeable_weight' => 0.50,
            'rate_base_snapshot' => 50.00,
            'additional_weight_rate_snapshot' => 20.00,
            'volumetric_divisor_snapshot' => 3500,
            'shipping_fee' => 70.00,
            'estimated_days_min' => 1,
            'estimated_days_max' => 1,
        ]);

        // Seller later modifies their settings to Base Fee: ₱100, Surcharge: ₱50
        SellerShippingProvider::updateOrCreate(
            ['seller_id' => $this->sellerA->id, 'provider_id' => $this->specialDeliveryProvider->id],
            ['custom_fee' => 100.00]
        );
        $rate->update(['surcharge' => 50.00]);

        // Verify existing order and its shipping record retain original ₱70 fee snapshot
        $refreshedOrder = Order::find($order->id);
        $refreshedShipping = OrderShipping::where('order_id', $order->id)->first();

        $this->assertEquals(1070.00, (float)$refreshedOrder->totalAmount);
        $this->assertEquals(70.00, (float)$refreshedShipping->shipping_fee);
        $this->assertEquals(50.00, (float)$refreshedShipping->rate_base_snapshot);
        $this->assertEquals(20.00, (float)$refreshedShipping->additional_weight_rate_snapshot);
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use App\Models\Product;
use App\Models\ShippingProvider;
use App\Models\ShippingZone;
use App\Models\ShippingZoneArea;
use App\Models\ShippingRate;
use App\Models\SellerShippingProvider;
use App\Services\ShippingZoneResolverService;
use App\Services\ShippingCalculatorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

class ShippingCalculatorTest extends TestCase
{
    use DatabaseTransactions;

    protected ShippingZoneResolverService $resolver;
    protected ShippingCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ShippingLogisticsSeeder::class);
        $this->resolver = new ShippingZoneResolverService();
        $this->calculator = new ShippingCalculatorService($this->resolver);
    }

    public function test_zone_resolver_prioritizes_exact_postal_code_over_province()
    {
        $uniqueSuffix = Str::random(5);
        $zoneA = ShippingZone::create(['name' => 'Specific Zone ' . $uniqueSuffix, 'code' => 'SP_' . $uniqueSuffix]);
        $zoneB = ShippingZone::create(['name' => 'Broad Zone ' . $uniqueSuffix, 'code' => 'BR_' . $uniqueSuffix]);

        // Broad province rule
        ShippingZoneArea::create([
            'zone_id' => $zoneB->id,
            'province' => 'TestLagunaProv_' . $uniqueSuffix,
            'city' => null,
            'barangay' => null,
            'postal_code' => null,
            'postal_code_prefix' => null,
        ]);

        // Exact postal code rule
        ShippingZoneArea::create([
            'zone_id' => $zoneA->id,
            'province' => 'TestLagunaProv_' . $uniqueSuffix,
            'city' => 'Lumban',
            'barangay' => null,
            'postal_code' => '9876',
            'postal_code_prefix' => '9876',
        ]);

        // Resolve with exact postal code: MUST resolve to zoneA (Specific Zone), not zoneB
        $resolved = $this->resolver->resolve('TestLagunaProv_' . $uniqueSuffix, 'Lumban', null, '9876');
        $this->assertNotNull($resolved);
        $this->assertEquals($zoneA->id, $resolved->id);

        // Resolve with different postal code: MUST fall back to zoneB (Broad Zone)
        $resolvedFallback = $this->resolver->resolve('TestLagunaProv_' . $uniqueSuffix, 'Pagsanjan', null, '9870');
        $this->assertNotNull($resolvedFallback);
        $this->assertEquals($zoneB->id, $resolvedFallback->id);
    }

    public function test_zone_resolver_returns_null_for_unserviceable_destination()
    {
        $resolved = $this->resolver->resolve('NowhereLandProvince', 'UnknownCity', null, '9999');
        $this->assertNull($resolved);
    }

    public function test_calculator_computes_chargeable_weight_using_volumetric_weight()
    {
        // Setup seller
        $seller = User::create([
            'name' => 'Artisan Seller',
            'email' => 'artisan_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
            'shopProvince' => 'Metro Manila',
            'shopPostalCode' => '1000',
        ]);

        // Setup buyer address
        $buyerAddress = [
            'province' => 'Metro Manila',
            'city' => 'Manila',
            'barangay' => 'Ermita',
            'postalCode' => '1000',
        ];

        // Product with low actual weight (0.2kg) but large dimensions: 30 x 20 x 10 cm = 6000 cm3
        // Volumetric weight with 3500 divisor = 6000 / 3500 = 1.71 kg
        // Chargeable weight should be 1.71 kg (dominating actual weight)
        $product = Product::create([
            'sellerId' => $seller->id,
            'name' => 'Woven Straw Hat',
            'price' => 500,
            'stock' => 10,
            'package_weight_per_unit' => 0.20,
            'package_length_per_unit' => 30.00,
            'package_width_per_unit' => 20.00,
            'package_height_per_unit' => 10.00,
            'handling_days' => 2,
        ]);

        $quotes = $this->calculator->calculateQuotes($seller, $buyerAddress, [
            ['id' => $product->id, 'quantity' => 1],
        ]);

        $this->assertNotEmpty($quotes);
        $firstQuote = $quotes[0];
        $this->assertEquals(0.20, $firstQuote['actual_weight']);
        $this->assertEquals(1.71, $firstQuote['volumetric_weight']);
        $this->assertEquals(1.71, $firstQuote['chargeable_weight']);
    }

    public function test_calculator_calculates_open_ended_bracket_incremental_charges()
    {
        $seller = User::create([
            'name' => 'Artisan Seller 2',
            'email' => 'artisan_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
            'shopProvince' => 'Metro Manila',
            'shopPostalCode' => '1000',
        ]);

        $buyerAddress = [
            'province' => 'Metro Manila',
            'city' => 'Manila',
            'postalCode' => '1000',
        ];

        // Heavy item: 5.5 kg (exceeds 3.00 kg, enters bracket 3: min_weight 3.01, max_weight NULL)
        $product = Product::create([
            'sellerId' => $seller->id,
            'name' => 'Heavy Wood Carving',
            'price' => 1500,
            'stock' => 5,
            'package_weight_per_unit' => 5.50,
            'package_length_per_unit' => 10.00,
            'package_width_per_unit' => 10.00,
            'package_height_per_unit' => 10.00,
            'handling_days' => 3,
        ]);

        $quotes = $this->calculator->calculateQuotes($seller, $buyerAddress, [
            ['id' => $product->id, 'quantity' => 1],
        ]);

        $this->assertNotEmpty($quotes);
        $jntQuote = collect($quotes)->firstWhere('provider_code', 'jnt');
        $this->assertNotNull($jntQuote);

        // Extra weight = ceil(5.50 - 3.01) = ceil(2.49) = 3 kg
        // J&T NCR to NCR bracket 3 base_rate = 160, additional = 25 * 3 = 75 => total = 235
        $this->assertEquals(5.50, $jntQuote['chargeable_weight']);
        $this->assertEquals(160.00, $jntQuote['rate_base_snapshot']);
        $this->assertEquals(235.00, $jntQuote['shipping_fee']);
    }

    public function test_quote_token_validation_ensures_cart_and_address_consistency()
    {
        $sellerId = (string) Str::uuid();
        $addrId = (string) Str::uuid();
        $cart = [
            ['id' => 'prod-1', 'quantity' => 2],
        ];

        $token = $this->calculator->generateQuoteToken($sellerId, $addrId, $cart);
        $this->assertTrue($this->calculator->validateQuoteToken($token, $sellerId, $addrId, $cart));

        // Tampered quantity
        $tamperedCart = [
            ['id' => 'prod-1', 'quantity' => 3],
        ];
        $this->assertFalse($this->calculator->validateQuoteToken($token, $sellerId, $addrId, $tamperedCart));

        // Different address
        $this->assertFalse($this->calculator->validateQuoteToken($token, $sellerId, 'different-addr', $cart));
    }

    public function test_legacy_product_without_package_specs_gracefully_applies_defaults()
    {
        $seller = User::create([
            'name' => 'Legacy Seller',
            'email' => 'artisan_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
            'shopProvince' => 'Metro Manila',
            'shopPostalCode' => '1000',
        ]);

        $product = Product::create([
            'sellerId' => $seller->id,
            'name' => 'Old Product',
            'price' => 250,
            'stock' => 3,
            'package_weight_per_unit' => 0.00, // Missing specs
            'package_length_per_unit' => 0.00,
        ]);

        $quotes = $this->calculator->calculateQuotes($seller, ['province' => 'Metro Manila'], [
            ['id' => $product->id, 'quantity' => 1],
        ]);

        $this->assertNotEmpty($quotes);
        $this->assertGreaterThan(0.00, (float) $quotes[0]['shipping_fee']);
    }

    public function test_deterministic_weight_bracket_boundary_and_excess_kg_calculations()
    {
        $uniqueSuffix = Str::random(6);
        $zoneOrigin = ShippingZone::create(['name' => 'Boundary Origin ' . $uniqueSuffix, 'code' => 'BO_' . $uniqueSuffix]);
        $zoneDest = ShippingZone::create(['name' => 'Boundary Dest ' . $uniqueSuffix, 'code' => 'BD_' . $uniqueSuffix]);

        ShippingZoneArea::create([
            'zone_id' => $zoneOrigin->id,
            'province' => 'BoundaryOriginProv_' . $uniqueSuffix,
            'city' => 'OriginCity',
            'postal_code' => '8881',
            'postal_code_prefix' => '8881',
        ]);

        ShippingZoneArea::create([
            'zone_id' => $zoneDest->id,
            'province' => 'BoundaryDestProv_' . $uniqueSuffix,
            'city' => 'DestCity',
            'postal_code' => '8882',
            'postal_code_prefix' => '8882',
        ]);

        $provider = ShippingProvider::create([
            'name' => 'Boundary Express ' . $uniqueSuffix,
            'code' => 'bnd_' . strtolower($uniqueSuffix),
            'default_volumetric_divisor' => 3500,
            'is_active' => true,
        ]);

        // Bracket 1: 0.00 - 1.00 kg -> ₱70.00
        ShippingRate::create([
            'provider_id' => $provider->id,
            'origin_zone_id' => $zoneOrigin->id,
            'destination_zone_id' => $zoneDest->id,
            'min_weight' => 0.00,
            'max_weight' => 1.00,
            'base_rate' => 70.00,
            'additional_weight_rate' => 0.00,
            'volumetric_divisor' => 3500,
            'estimated_days_min' => 2,
            'estimated_days_max' => 4,
            'is_active' => true,
        ]);

        // Bracket 2: 1.01 - 2.00 kg -> ₱90.00
        ShippingRate::create([
            'provider_id' => $provider->id,
            'origin_zone_id' => $zoneOrigin->id,
            'destination_zone_id' => $zoneDest->id,
            'min_weight' => 1.01,
            'max_weight' => 2.00,
            'base_rate' => 90.00,
            'additional_weight_rate' => 0.00,
            'volumetric_divisor' => 3500,
            'estimated_days_min' => 2,
            'estimated_days_max' => 4,
            'is_active' => true,
        ]);

        // Bracket 3: 2.01 - 3.00 kg -> ₱110.00
        ShippingRate::create([
            'provider_id' => $provider->id,
            'origin_zone_id' => $zoneOrigin->id,
            'destination_zone_id' => $zoneDest->id,
            'min_weight' => 2.01,
            'max_weight' => 3.00,
            'base_rate' => 110.00,
            'additional_weight_rate' => 0.00,
            'volumetric_divisor' => 3500,
            'estimated_days_min' => 2,
            'estimated_days_max' => 4,
            'is_active' => true,
        ]);

        // Bracket 4: 3.01 - NULL kg -> ₱110.00 base + ₱20.00 per excess kg
        ShippingRate::create([
            'provider_id' => $provider->id,
            'origin_zone_id' => $zoneOrigin->id,
            'destination_zone_id' => $zoneDest->id,
            'min_weight' => 3.01,
            'max_weight' => null,
            'base_rate' => 110.00,
            'additional_weight_rate' => 20.00,
            'volumetric_divisor' => 3500,
            'estimated_days_min' => 2,
            'estimated_days_max' => 4,
            'is_active' => true,
        ]);

        $seller = User::create([
            'name' => 'Boundary Seller',
            'email' => 'boundary_seller_' . $uniqueSuffix . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
            'shopProvince' => 'BoundaryOriginProv_' . $uniqueSuffix,
            'shopCity' => 'OriginCity',
            'shopPostalCode' => '8881',
        ]);

        $buyerAddress = [
            'province' => 'BoundaryDestProv_' . $uniqueSuffix,
            'city' => 'DestCity',
            'postalCode' => '8882',
        ];

        // Boundary cases to test:
        // [weight, expected_fee]
        $testCases = [
            [1.00, 70.00],   // Top boundary of bracket 1
            [1.01, 90.00],   // Bottom boundary of bracket 2
            [2.00, 90.00],   // Top boundary of bracket 2
            [2.01, 110.00],  // Bottom boundary of bracket 3
            [3.00, 110.00],  // Top boundary of bracket 3
            [3.01, 110.00],  // Bottom boundary of bracket 4 (extraWeight = ceil(3.01 - 3.01) = 0 -> 110 + 0 = 110)
            [3.50, 130.00],  // Fractional excess (extraWeight = ceil(3.50 - 3.01) = ceil(0.49) = 1 -> 110 + 20 = 130)
            [4.00, 130.00],  // 1 full excess kg (extraWeight = ceil(4.00 - 3.01) = ceil(0.99) = 1 -> 110 + 20 = 130)
            [4.01, 130.00],  // extraWeight = ceil(4.01 - 3.01) = ceil(1.00) = 1 -> 110 + 20 = 130
            [4.02, 150.00],  // extraWeight = ceil(4.02 - 3.01) = ceil(1.01) = 2 -> 110 + 40 = 150
            [4.50, 150.00],  // extraWeight = ceil(4.50 - 3.01) = ceil(1.49) = 2 -> 110 + 40 = 150
            [5.00, 150.00],  // extraWeight = ceil(5.00 - 3.01) = ceil(1.99) = 2 -> 110 + 40 = 150
        ];

        foreach ($testCases as [$weight, $expectedFee]) {
            $product = Product::create([
                'sellerId' => $seller->id,
                'name' => "Boundary Product {$weight}kg",
                'price' => 100,
                'stock' => 5,
                'package_weight_per_unit' => $weight,
                'package_length_per_unit' => 10.00,
                'package_width_per_unit' => 10.00,
                'package_height_per_unit' => 10.00,
                'handling_days' => 1,
            ]);

            $quotes = $this->calculator->calculateQuotes($seller, $buyerAddress, [
                ['id' => $product->id, 'quantity' => 1],
            ]);

            $bndQuote = collect($quotes)->firstWhere('provider_code', $provider->code);
            $this->assertNotNull($bndQuote, "Quote not found for {$weight}kg");
            $this->assertEquals(
                $expectedFee,
                (float) $bndQuote['shipping_fee'],
                "Weight {$weight}kg expected fee ₱{$expectedFee}, got ₱{$bndQuote['shipping_fee']}"
            );
        }
    }

    public function test_multi_seller_quote_token_deterministic_sorting_and_validation()
    {
        $sellerA = (string) Str::uuid();
        $sellerB = (string) Str::uuid();
        $sellerC = (string) Str::uuid();
        $addrId = (string) Str::uuid();
        $cart = [
            ['id' => 'prod-1', 'quantity' => 2],
            ['id' => 'prod-2', 'quantity' => 1],
        ];

        // Generate token with unsorted array
        $token = $this->calculator->generateQuoteToken([$sellerC, $sellerA, $sellerB], $addrId, $cart);
        $this->assertNotEmpty($token);

        // Validation with different order should still succeed because normalized sorting is deterministic
        $this->assertTrue($this->calculator->validateQuoteToken($token, [$sellerB, $sellerC, $sellerA], $addrId, $cart));

        // Validation missing a seller must fail
        $this->assertFalse($this->calculator->validateQuoteToken($token, [$sellerA, $sellerB], $addrId, $cart));

        // Validation with extra seller must fail
        $this->assertFalse($this->calculator->validateQuoteToken($token, [$sellerA, $sellerB, $sellerC, 'extra-seller'], $addrId, $cart));
    }

    public function test_deterministic_delivery_days_calculation_with_max_handling_days()
    {
        $seller = User::create([
            'name' => 'ETA Seller',
            'email' => 'eta_seller_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
            'shopProvince' => 'Metro Manila',
            'shopPostalCode' => '1000',
        ]);

        $buyerAddress = [
            'province' => 'Metro Manila',
            'city' => 'Manila',
            'postalCode' => '1000',
        ];

        // Item 1: 1 handling day
        $prod1 = Product::create([
            'sellerId' => $seller->id,
            'name' => 'Fast Craft',
            'price' => 100,
            'stock' => 5,
            'package_weight_per_unit' => 0.5,
            'package_length_per_unit' => 10,
            'package_width_per_unit' => 10,
            'package_height_per_unit' => 10,
            'handling_days' => 1,
        ]);

        // Item 2: 4 handling days
        $prod2 = Product::create([
            'sellerId' => $seller->id,
            'name' => 'Custom Embroidery',
            'price' => 500,
            'stock' => 5,
            'package_weight_per_unit' => 0.5,
            'package_length_per_unit' => 10,
            'package_width_per_unit' => 10,
            'package_height_per_unit' => 10,
            'handling_days' => 4,
        ]);

        $quotes = $this->calculator->calculateQuotes($seller, $buyerAddress, [
            ['id' => $prod1->id, 'quantity' => 1],
            ['id' => $prod2->id, 'quantity' => 1],
        ]);

        $firstQuote = $quotes[0];
        // J&T NCR-NCR rate has estimated_days_min = 1, estimated_days_max = 2 (or standard 2-4)
        // Max handling days = MAX(1, 4) = 4
        $matchingRate = ShippingRate::find($firstQuote['shipping_rate_id']);
        $this->assertEquals(4 + $matchingRate->estimated_days_min, $firstQuote['estimated_days_min']);
        $this->assertEquals(4 + $matchingRate->estimated_days_max, $firstQuote['estimated_days_max']);
    }

    public function test_order_shipping_immutability_blocks_modifications_to_pricing_snapshot()
    {
        $customer = User::create([
            'name' => 'Snap Customer',
            'email' => 'snap_cust_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $seller = User::create([
            'name' => 'Snap Seller',
            'email' => 'snap_seller_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
        ]);

        $order = \App\Models\Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $customer->id,
            'sellerId' => $seller->id,
            'totalAmount' => 500.00,
            'status' => 'Pending',
            'shippingAddress' => json_encode(['province' => 'Laguna', 'city' => 'Lumban']),
        ]);

        $provider = ShippingProvider::where('code', 'jnt')->first() ?: ShippingProvider::first();

        $shipping = \App\Models\OrderShipping::create([
            'order_id' => $order->id,
            'provider_id' => $provider->id,
            'provider_name' => $provider->name,
            'pricing_provider_id' => $provider->id,
            'pricing_provider_name' => $provider->name,
            'actual_weight' => 0.50,
            'volumetric_weight' => 0.40,
            'chargeable_weight' => 1.00,
            'rate_base_snapshot' => 85.00,
            'additional_weight_rate_snapshot' => 0.00,
            'volumetric_divisor_snapshot' => 3500,
            'shipping_fee' => 85.00,
            'estimated_days_min' => 2,
            'estimated_days_max' => 4,
            'origin_zone_name' => 'South Luzon',
            'destination_zone_name' => 'NCR',
            'shipping_status' => 'Pending',
        ]);

        // Mutable fulfillment fields CAN be updated
        $shipping->update([
            'fulfillment_provider_name' => 'Flash Express',
            'tracking_number' => 'FLASH-123456',
            'shipping_status' => 'Shipped',
        ]);
        $shipping->refresh();
        $this->assertEquals('Flash Express', $shipping->fulfillment_provider_name);
        $this->assertEquals('FLASH-123456', $shipping->tracking_number);

        // Attempting to mutate pricing snapshot field MUST throw DomainException
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('immutable');
        $shipping->update(['shipping_fee' => 10.00]);
    }

    public function test_seller_preferred_provider_strict_validation_falls_back_when_deactivated()
    {
        $seller = User::create([
            'name' => 'Strict Seller',
            'email' => 'strict_seller_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
            'shopProvince' => 'Metro Manila',
            'shopPostalCode' => '1000',
        ]);

        // Create a custom inactive provider and set it as preferred
        $customInactiveProvider = ShippingProvider::create([
            'name' => 'Disabled Logistics',
            'code' => 'disabled_log_' . Str::random(4),
            'is_active' => false,
        ]);

        SellerShippingProvider::create([
            'seller_id' => $seller->id,
            'provider_id' => $customInactiveProvider->id,
            'is_enabled' => true,
            'is_default' => true,
        ]);

        // Set platform default provider
        $platformProvider = ShippingProvider::where('code', 'jnt')->first();
        if ($platformProvider) {
            $platformProvider->update(['is_platform_default' => true, 'is_active' => true]);
        }

        // Resolving preferred provider must bypass inactive provider and resolve active platform default
        $resolved = $this->calculator->getSellerPreferredProvider($seller);
        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is_active);
        $this->assertNotEquals($customInactiveProvider->id, $resolved->id);
    }

    public function test_calculator_strictly_ignores_legacy_products_shipping_fee()
    {
        $seller = User::create([
            'name' => 'Legacy Fee Seller',
            'email' => 'legacy_seller_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
            'shopProvince' => 'Metro Manila',
            'shopPostalCode' => '1000',
        ]);

        // Product has legacy shippingFee = 999.00
        $product = Product::create([
            'sellerId' => $seller->id,
            'name' => 'Product With Legacy Fee',
            'price' => 200,
            'stock' => 5,
            'shippingFee' => 999.00, // Legacy fee that must be ignored
            'package_weight_per_unit' => 0.50,
            'package_length_per_unit' => 10.00,
            'package_width_per_unit' => 10.00,
            'package_height_per_unit' => 10.00,
            'handling_days' => 1,
        ]);

        $quotes = $this->calculator->calculateQuotes($seller, ['province' => 'Metro Manila'], [
            ['id' => $product->id, 'quantity' => 1],
        ]);

        $this->assertNotEmpty($quotes);
        foreach ($quotes as $quote) {
            // Calculated fee is database rate matrix bracket (e.g. ₱50-₱85), never the legacy ₱999
            $this->assertNotEquals(999.00, (float) $quote['shipping_fee']);
        }
    }

    public function test_is_local_cluster_accurately_detects_nearby_laguna_municipalities()
    {
        $seller = User::create([
            'name' => 'Lumban Seller',
            'email' => 'lumban_seller_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
            'shopProvince' => 'Laguna',
            'shopCity' => 'Lumban',
            'shopPostalCode' => '4014',
        ]);

        // Local cluster municipalities
        $this->assertTrue($this->calculator->isLocalCluster($seller, ['province' => 'Laguna', 'city' => 'Lumban', 'postalCode' => '4014']));
        $this->assertTrue($this->calculator->isLocalCluster($seller, ['province' => 'Laguna', 'city' => 'Santa Cruz', 'postalCode' => '4009']));
        $this->assertTrue($this->calculator->isLocalCluster($seller, ['province' => 'Laguna', 'city' => 'Pagsanjan', 'postalCode' => '4008']));
        $this->assertTrue($this->calculator->isLocalCluster($seller, ['province' => 'Laguna', 'city' => 'Paete', 'postalCode' => '4016']));
        $this->assertTrue($this->calculator->isLocalCluster($seller, ['province' => 'Laguna', 'city' => 'Kalayaan', 'postalCode' => '4015']));

        // Non-local / far-away destinations
        $this->assertFalse($this->calculator->isLocalCluster($seller, ['province' => 'Laguna', 'city' => 'San Pedro', 'postalCode' => '4023']));
        $this->assertFalse($this->calculator->isLocalCluster($seller, ['province' => 'Laguna', 'city' => 'Biñan', 'postalCode' => '4024']));
        $this->assertFalse($this->calculator->isLocalCluster($seller, ['province' => 'Metro Manila', 'city' => 'Manila', 'postalCode' => '1000']));
        $this->assertFalse($this->calculator->isLocalCluster($seller, ['province' => 'Cebu', 'city' => 'Cebu City', 'postalCode' => '6000']));
    }

    public function test_local_cluster_returns_store_pickup_and_seller_direct()
    {
        $seller = User::create([
            'name' => 'Lumban Artisan',
            'email' => 'lumban_artisan_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
            'shopProvince' => 'Laguna',
            'shopCity' => 'Lumban',
            'shopPostalCode' => '4014',
        ]);

        $product = Product::create([
            'sellerId' => $seller->id,
            'name' => 'Barong Tagalog',
            'price' => 2500,
            'stock' => 10,
            'package_weight_per_unit' => 0.50,
            'package_length_per_unit' => 20.00,
            'package_width_per_unit' => 15.00,
            'package_height_per_unit' => 5.00,
            'handling_days' => 1,
        ]);

        // Destination in Pagsanjan (adjacent to Lumban)
        $destination = [
            'province' => 'Laguna',
            'city' => 'Pagsanjan',
            'barangay' => 'Poblacion',
            'postalCode' => '4008',
        ];

        $quotes = $this->calculator->calculateQuotes($seller, $destination, [
            ['id' => $product->id, 'quantity' => 1],
        ]);

        $this->assertNotEmpty($quotes);
        $providerCodes = array_column($quotes, 'provider_code');

        // Must include store_pickup (₱0.00) and seller_direct (₱25.00)
        $this->assertContains('store_pickup', $providerCodes);
        $this->assertContains('seller_direct', $providerCodes);

        $pickupQuote = collect($quotes)->firstWhere('provider_code', 'store_pickup');
        $this->assertEquals(0.00, (float) $pickupQuote['shipping_fee']);

        $directQuote = collect($quotes)->firstWhere('provider_code', 'seller_direct');
        $this->assertEquals(25.00, (float) $directQuote['shipping_fee']);

        // Courier (J&T) must be suppressed for local cluster
        $this->assertNotContains('jnt', $providerCodes);
    }

    public function test_non_local_destination_suppresses_local_options_and_returns_standard_delivery()
    {
        $seller = User::create([
            'name' => 'Lumban Artisan',
            'email' => 'lumban_artisan_2_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
            'shopProvince' => 'Laguna',
            'shopCity' => 'Lumban',
            'shopPostalCode' => '4014',
        ]);

        $product = Product::create([
            'sellerId' => $seller->id,
            'name' => 'Barong Tagalog',
            'price' => 2500,
            'stock' => 10,
            'package_weight_per_unit' => 0.50,
            'package_length_per_unit' => 20.00,
            'package_width_per_unit' => 15.00,
            'package_height_per_unit' => 5.00,
            'handling_days' => 1,
        ]);

        // Destination in San Pedro, Laguna (far away from Lumban)
        $destination = [
            'province' => 'Laguna',
            'city' => 'San Pedro',
            'barangay' => 'San Antonio',
            'postalCode' => '4023',
        ];

        $quotes = $this->calculator->calculateQuotes($seller, $destination, [
            ['id' => $product->id, 'quantity' => 1],
        ]);

        $this->assertNotEmpty($quotes);
        $providerCodes = array_column($quotes, 'provider_code');

        // Must NOT include store_pickup or seller_direct
        $this->assertNotContains('store_pickup', $providerCodes);
        $this->assertNotContains('seller_direct', $providerCodes);

        // Must return standard delivery quote with valid positive fee
        $firstQuote = $quotes[0];
        $this->assertGreaterThan(0.00, (float) $firstQuote['shipping_fee']);
    }

    public function test_get_available_payment_methods_returns_cod_only_for_local_cluster()
    {
        $localMethods = $this->calculator->getAvailablePaymentMethods(true);
        $this->assertContains('COD', $localMethods);
        $this->assertContains('GCash', $localMethods);
        $this->assertContains('Maya', $localMethods);

        $nonLocalMethods = $this->calculator->getAvailablePaymentMethods(false);
        $this->assertNotContains('COD', $nonLocalMethods);
        $this->assertContains('GCash', $nonLocalMethods);
        $this->assertContains('Maya', $nonLocalMethods);
    }
}


<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipping;
use App\Models\OrderStatusHistory;
use App\Models\ShippingProvider;
use App\Models\ShippingZone;
use App\Models\ShippingZoneArea;
use App\Models\ShippingRate;
use App\Models\SellerShippingProvider;
use App\Services\ShippingZoneResolverService;
use App\Services\ShippingCalculatorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ShippingAndLogisticsArchitectureTest extends TestCase
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

    protected function createCustomer(array $attrs = []): User
    {
        return User::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => 'Test Customer ' . Str::random(4),
            'username' => 'cust_' . Str::random(6),
            'email' => 'cust_' . Str::random(6) . '@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'mobileNumber' => '0917' . rand(1000000, 9999999),
            'isVerified' => true,
            'email_verified_at' => now(),
        ], $attrs));
    }

    protected function createSeller(array $attrs = []): User
    {
        return User::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => 'Test Artisan Seller ' . Str::random(4),
            'username' => 'seller_' . Str::random(6),
            'email' => 'seller_' . Str::random(6) . '@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'seller',
            'shopName' => 'Artisan Shop ' . Str::random(4),
            'shopProvince' => 'Laguna',
            'shopCity' => 'Lumban',
            'shopBarangay' => 'Poblacion',
            'shopPostalCode' => '4014',
            'status' => 'active',
            'mobileNumber' => '0918' . rand(1000000, 9999999),
            'isVerified' => true,
            'email_verified_at' => now(),
            'profile_completed' => true,
        ], $attrs));
    }

    protected function createAddress(User $user, array $attrs = []): Address
    {
        $defaults = [
            'id' => (string) Str::uuid(),
            'userId' => $user->id,
            'recipientName' => $user->name,
            'phone' => $user->mobileNumber ?? '09171234567',
            'houseNo' => '123',
            'street' => 'Test Street',
            'region' => 'NCR',
            'barangay' => 'Barangay 1',
            'city' => 'Manila',
            'province' => 'Metro Manila',
            'postalCode' => '1000',
            'isDefault' => true,
        ];

        return Address::create(array_merge($defaults, $attrs));
    }

    protected function createTestProduct(User $seller, array $attrs = []): Product
    {
        return Product::create(array_merge([
            'id' => (string) Str::uuid(),
            'sellerId' => $seller->id,
            'name' => 'Handmade Wood Craft ' . Str::random(4),
            'description' => 'Fine artisan craft',
            'price' => 500.00,
            'stock' => 20,
            'package_weight_per_unit' => 1.00,
            'package_length_per_unit' => 30.00,
            'package_width_per_unit' => 20.00,
            'package_height_per_unit' => 5.00,
            'handling_days' => 2,
            'status' => 'active',
            'approval_status' => 'approved',
        ], $attrs));
    }

    /* -------------------------------------------------------------------------- */
    /* 1. ZONE RESOLUTION TESTS                                                   */
    /* -------------------------------------------------------------------------- */

    public function test_zone_resolver_prioritizes_exact_postal_code_over_prefix_and_province()
    {
        $uniqueSuffix = Str::random(6);
        $zoneExact = ShippingZone::create(['name' => 'Exact Zone ' . $uniqueSuffix, 'code' => 'EX_' . $uniqueSuffix]);
        $zonePrefix = ShippingZone::create(['name' => 'Prefix Zone ' . $uniqueSuffix, 'code' => 'PX_' . $uniqueSuffix]);
        $zoneProv = ShippingZone::create(['name' => 'Prov Zone ' . $uniqueSuffix, 'code' => 'PR_' . $uniqueSuffix]);

        // Province broad rule
        ShippingZoneArea::create([
            'zone_id' => $zoneProv->id,
            'province' => 'TestProv_' . $uniqueSuffix,
            'city' => null,
            'barangay' => null,
            'postal_code' => null,
            'postal_code_prefix' => null,
        ]);

        // Prefix rule
        ShippingZoneArea::create([
            'zone_id' => $zonePrefix->id,
            'province' => 'TestProv_' . $uniqueSuffix,
            'city' => 'TestCity',
            'barangay' => null,
            'postal_code' => null,
            'postal_code_prefix' => '88',
        ]);

        // Exact postal code rule
        ShippingZoneArea::create([
            'zone_id' => $zoneExact->id,
            'province' => 'TestProv_' . $uniqueSuffix,
            'city' => 'TestCity',
            'barangay' => null,
            'postal_code' => '8899',
            'postal_code_prefix' => '8899',
        ]);

        // Match exact
        $matchExact = $this->resolver->resolve('TestProv_' . $uniqueSuffix, 'TestCity', null, '8899');
        $this->assertEquals($zoneExact->id, $matchExact->id);

        // Match prefix
        $matchPrefix = $this->resolver->resolve('TestProv_' . $uniqueSuffix, 'TestCity', null, '8810');
        $this->assertEquals($zonePrefix->id, $matchPrefix->id);

        // Fallback to broad province
        $matchProv = $this->resolver->resolve('TestProv_' . $uniqueSuffix, 'OtherCity', null, '1111');
        $this->assertEquals($zoneProv->id, $matchProv->id);
    }

    public function test_zone_resolver_returns_null_for_unconfigured_destination()
    {
        $resolved = $this->resolver->resolve('NonExistentProvince', 'UnknownCity', null, '0000');
        $this->assertNull($resolved);
    }

    /* -------------------------------------------------------------------------- */
    /* 2. WEIGHT & VOLUMETRIC CALCULATION TESTS                                   */
    /* -------------------------------------------------------------------------- */

    public function test_chargeable_weight_takes_maximum_of_actual_and_volumetric()
    {
        $seller = $this->createSeller();
        $buyerAddr = ['province' => 'Metro Manila', 'city' => 'Manila', 'postalCode' => '1000'];

        // Actual weight dominates: 3.0 kg actual vs 10x10x10cm / 3500 = 0.285 kg volumetric
        $heavyProduct = $this->createTestProduct($seller, [
            'package_weight_per_unit' => 3.00,
            'package_length_per_unit' => 10.00,
            'package_width_per_unit' => 10.00,
            'package_height_per_unit' => 10.00,
        ]);

        $quotesHeavy = $this->calculator->calculateQuotes($seller, $buyerAddr, [
            ['id' => $heavyProduct->id, 'quantity' => 1],
        ]);
        $this->assertEquals(3.00, $quotesHeavy[0]['chargeable_weight']);

        // Volumetric weight dominates: 0.2 kg actual vs 50x40x20cm = 40000 / 3500 = 11.43 kg
        $bulkyProduct = $this->createTestProduct($seller, [
            'package_weight_per_unit' => 0.20,
            'package_length_per_unit' => 50.00,
            'package_width_per_unit' => 40.00,
            'package_height_per_unit' => 20.00,
        ]);

        $quotesBulky = $this->calculator->calculateQuotes($seller, $buyerAddr, [
            ['id' => $bulkyProduct->id, 'quantity' => 1],
        ]);
        $this->assertEquals(11.43, $quotesBulky[0]['chargeable_weight']);
    }

    public function test_consolidated_volume_multiplies_across_multiple_quantities_and_items()
    {
        $seller = $this->createSeller();
        $buyerAddr = ['province' => 'Metro Manila', 'city' => 'Manila', 'postalCode' => '1000'];

        $prod1 = $this->createTestProduct($seller, [
            'package_weight_per_unit' => 0.50,
            'package_length_per_unit' => 20.00,
            'package_width_per_unit' => 10.00,
            'package_height_per_unit' => 5.00, // 1000 cm3 each
        ]);

        $prod2 = $this->createTestProduct($seller, [
            'package_weight_per_unit' => 0.80,
            'package_length_per_unit' => 20.00,
            'package_width_per_unit' => 15.00,
            'package_height_per_unit' => 10.00, // 3000 cm3 each
        ]);

        // 2x prod1 (1.0kg, 2000 cm3) + 3x prod2 (2.4kg, 9000 cm3)
        // Total actual weight = 3.40 kg
        // Total volume = 11,000 cm3
        // Volumetric with 3500 divisor = 11000 / 3500 = 3.14 kg
        // Chargeable weight = max(3.40, 3.14) = 3.40 kg
        $quotes = $this->calculator->calculateQuotes($seller, $buyerAddr, [
            ['id' => $prod1->id, 'quantity' => 2],
            ['id' => $prod2->id, 'quantity' => 3],
        ]);

        $this->assertNotEmpty($quotes);
        $this->assertEquals(3.40, $quotes[0]['actual_weight']);
        $this->assertEquals(3.14, $quotes[0]['volumetric_weight']);
        $this->assertEquals(3.40, $quotes[0]['chargeable_weight']);
    }

    /* -------------------------------------------------------------------------- */
    /* 3. RATE BRACKET & INCREMENTAL WEIGHT PRICING TESTS                         */
    /* -------------------------------------------------------------------------- */

    public function test_rate_bracket_boundaries_match_exact_bracket()
    {
        $originZone = ShippingZone::where('code', 'NCR')->firstOrFail();
        $destZone = ShippingZone::where('code', 'NCR')->firstOrFail();
        $provider = ShippingProvider::where('code', 'jnt')->firstOrFail();

        // 0.99 kg falls into bracket 1 (0.00 - 1.00) => base_rate 70.00
        $rate099 = ShippingRate::where('provider_id', $provider->id)
            ->where('origin_zone_id', $originZone->id)
            ->where('destination_zone_id', $destZone->id)
            ->where('min_weight', '<=', 0.99)
            ->where(function ($q) { $q->where('max_weight', '>=', 0.99)->orWhereNull('max_weight'); })
            ->first();
        $this->assertEquals(70.00, (float) $rate099->base_rate);

        // 1.00 kg falls into bracket 1 (0.00 - 1.00) => base_rate 70.00
        $rate100 = ShippingRate::where('provider_id', $provider->id)
            ->where('origin_zone_id', $originZone->id)
            ->where('destination_zone_id', $destZone->id)
            ->where('min_weight', '<=', 1.00)
            ->where(function ($q) { $q->where('max_weight', '>=', 1.00)->orWhereNull('max_weight'); })
            ->first();
        $this->assertEquals(70.00, (float) $rate100->base_rate);

        // 1.01 kg falls into bracket 2 (1.01 - 3.00) => base_rate 110.00
        $rate101 = ShippingRate::where('provider_id', $provider->id)
            ->where('origin_zone_id', $originZone->id)
            ->where('destination_zone_id', $destZone->id)
            ->where('min_weight', '<=', 1.01)
            ->where(function ($q) { $q->where('max_weight', '>=', 1.01)->orWhereNull('max_weight'); })
            ->first();
        $this->assertEquals(110.00, (float) $rate101->base_rate);
    }

    public function test_oversized_shipment_without_open_ended_rate_throws_exception()
    {
        $seller = $this->createSeller();
        $buyerAddr = ['province' => 'Metro Manila', 'city' => 'Manila', 'postalCode' => '1000'];

        // Temporarily delete open-ended rates for this origin/destination
        $originZone = $this->resolver->resolve($seller->shopProvince, $seller->shopCity, null, $seller->shopPostalCode);
        $destZone = $this->resolver->resolve($buyerAddr['province'], $buyerAddr['city'], null, $buyerAddr['postalCode']);
        ShippingRate::where('origin_zone_id', $originZone->id)
            ->where('destination_zone_id', $destZone->id)
            ->whereNull('max_weight')
            ->delete();

        // 50kg shipment
        $oversizedProd = $this->createTestProduct($seller, [
            'package_weight_per_unit' => 50.00,
            'package_length_per_unit' => 10.00,
            'package_width_per_unit' => 10.00,
            'package_height_per_unit' => 10.00,
        ]);

        $this->expectException(\Exception::class);

        $this->calculator->calculateQuotes($seller, $buyerAddr, [
            ['id' => $oversizedProd->id, 'quantity' => 1],
        ]);
    }

    /* -------------------------------------------------------------------------- */
    /* 4. PROVIDER FILTERING (ACTIVE / SELLER-ENABLED) TESTS                      */
    /* -------------------------------------------------------------------------- */

    public function test_inactive_provider_is_excluded_from_quotes()
    {
        $seller = $this->createSeller();
        $buyerAddr = ['province' => 'Metro Manila', 'city' => 'Manila', 'postalCode' => '1000'];
        $product = $this->createTestProduct($seller);

        // Deactivate LBC
        ShippingProvider::where('code', 'lbc')->update(['is_active' => false]);

        $quotes = $this->calculator->calculateQuotes($seller, $buyerAddr, [
            ['id' => $product->id, 'quantity' => 1],
        ]);

        $providerCodes = collect($quotes)->pluck('provider_code')->all();
        $this->assertNotContains('lbc', $providerCodes);
    }

    public function test_seller_disabled_provider_is_excluded_from_quotes()
    {
        $seller = $this->createSeller();
        $buyerAddr = ['province' => 'Metro Manila', 'city' => 'Manila', 'postalCode' => '1000'];
        $product = $this->createTestProduct($seller);

        $jnt = ShippingProvider::where('code', 'jnt')->firstOrFail();
        $spx = ShippingProvider::where('code', 'spx')->firstOrFail();

        // Seller enables J&T, disables SPX
        SellerShippingProvider::create([
            'seller_id' => $seller->id,
            'provider_id' => $jnt->id,
            'is_enabled' => true,
        ]);
        SellerShippingProvider::create([
            'seller_id' => $seller->id,
            'provider_id' => $spx->id,
            'is_enabled' => false,
        ]);

        $quotes = $this->calculator->calculateQuotes($seller, $buyerAddr, [
            ['id' => $product->id, 'quantity' => 1],
        ]);

        $providerCodes = collect($quotes)->pluck('provider_code')->all();
        $this->assertContains('jnt', $providerCodes);
        $this->assertNotContains('spx', $providerCodes);
    }

    /* -------------------------------------------------------------------------- */
    /* 5. SHIPPING QUOTES API & CHECKOUT SERVER AUTHORITY TESTS                   */
    /* -------------------------------------------------------------------------- */

    public function test_quote_endpoint_returns_valid_quotes_and_signed_token()
    {
        $seller = $this->createSeller();
        $customer = $this->createCustomer();
        $address = $this->createAddress($customer);
        $product = $this->createTestProduct($seller);

        $response = $this->actingAs($customer)->postJson('/checkout/shipping-quotes', [
            'seller_id' => $seller->id,
            'address_id' => $address->id,
            'items' => [
                ['id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'quotes' => [
                '*' => [
                    'provider_id',
                    'provider_name',
                    'provider_code',
                    'shipping_fee',
                    'chargeable_weight',
                    'estimated_days_min',
                    'estimated_days_max',
                    'delivery_estimate_display',
                ]
            ],
            'quote_token',
        ]);
    }

    public function test_checkout_ignores_tampered_client_shipping_fee_and_recalculates_server_side()
    {
        $seller = $this->createSeller();
        $customer = $this->createCustomer();
        $address = $this->createAddress($customer);
        $product = $this->createTestProduct($seller, [
            'price' => 1000.00,
            'stock' => 10,
            'package_weight_per_unit' => 1.00,
            'package_length_per_unit' => 30.00,
            'package_width_per_unit' => 20.00,
            'package_height_per_unit' => 5.00,
        ]);

        // Get legitimate quote token
        $quoteResponse = $this->actingAs($customer)->postJson('/checkout/shipping-quotes', [
            'seller_id' => $seller->id,
            'address_id' => $address->id,
            'items' => [
                ['id' => $product->id, 'quantity' => 1],
            ],
        ]);
        $quotes = $quoteResponse->json('quotes');
        $quoteToken = $quoteResponse->json('quote_token');
        $selectedProvider = $quotes[0];

        $screenshot = UploadedFile::fake()->image('gcash_receipt_screenshot.jpg', 600, 1200);

        // Attempt checkout submitting a tampered ₱1.00 shipping fee and manipulated total
        $checkoutResponse = $this->actingAs($customer)->post('/checkout', [
            'seller_id' => $seller->id,
            'addressId' => $address->id,
            'paymentMethod' => 'GCash',
            'paymentReference' => '100' . rand(1000000000, 9999999999),
            'paymentScreenshot' => $screenshot,
            'shipping_provider_id' => $selectedProvider['provider_id'],
            'shipping_quote_token' => $quoteToken,
            'shippingFee' => 1.00,     // TAMPERED
            'total' => 1001.00,         // TAMPERED
            'items' => [
                [
                    'id' => $product->id,
                    'productId' => $product->id,
                    'quantity' => 1,
                    'price' => 1000.00,
                    'size' => 'Standard',
                    'variation' => 'Original',
                ]
            ],
        ]);
        $checkoutResponse->assertRedirect(route('orders'));

        // Assert that the database recorded the authoritative calculated fee, NOT ₱1.00
        $order = Order::where('customerId', $customer->id)->latest('createdAt')->first();
        $this->assertNotNull($order);
        $this->assertEquals((float) $selectedProvider['shipping_fee'], (float) $order->shippingFee);
        $this->assertEquals(1000.00 + (float) $selectedProvider['shipping_fee'], (float) $order->totalAmount);

        // Verify immutable snapshot
        $shippingSnapshot = OrderShipping::where('order_id', $order->id)->first();
        $this->assertNotNull($shippingSnapshot);
        $this->assertEquals($selectedProvider['provider_id'], $shippingSnapshot->provider_id);
        $this->assertEquals((float) $selectedProvider['shipping_fee'], (float) $shippingSnapshot->shipping_fee);
    }

    public function test_checkout_fails_when_cart_items_are_modified_after_quote()
    {
        $seller = $this->createSeller();
        $customer = $this->createCustomer();
        $address = $this->createAddress($customer);
        $product = $this->createTestProduct($seller);

        // Get quote for 1 item
        $quoteResponse = $this->actingAs($customer)->postJson('/checkout/shipping-quotes', [
            'seller_id' => $seller->id,
            'address_id' => $address->id,
            'items' => [
                ['id' => $product->id, 'quantity' => 1],
            ],
        ]);
        $quotes = $quoteResponse->json('quotes');
        $quoteToken = $quoteResponse->json('quote_token');

        $screenshot = UploadedFile::fake()->image('gcash_receipt_screenshot.jpg', 600, 1200);

        // Submit checkout for 5 items with the 1-item token
        $checkoutResponse = $this->actingAs($customer)->postJson('/checkout', [
            'seller_id' => $seller->id,
            'addressId' => $address->id,
            'paymentMethod' => 'GCash',
            'paymentReference' => '100' . rand(1000000000, 9999999999),
            'paymentScreenshot' => $screenshot,
            'shipping_provider_id' => $quotes[0]['provider_id'],
            'shipping_quote_token' => $quoteToken,
            'items' => [
                [
                    'id' => $product->id,
                    'quantity' => 5,
                    'price' => 500.00,
                ]
            ],
        ]);

        $checkoutResponse->assertStatus(422);
        $checkoutResponse->assertJsonFragment([
            'message' => 'Your shipping quote has expired or the order items changed. Please review and refresh your shipping quote.',
        ]);
    }

    /* -------------------------------------------------------------------------- */
    /* 6. IMMUTABLE SNAPSHOT PERSISTENCE TEST                                     */
    /* -------------------------------------------------------------------------- */

    public function test_modifying_provider_rates_does_not_alter_historical_order_shipping_snapshot()
    {
        $seller = $this->createSeller();
        $customer = $this->createCustomer();
        $address = $this->createAddress($customer);
        $product = $this->createTestProduct($seller);

        $quoteResponse = $this->actingAs($customer)->postJson('/checkout/shipping-quotes', [
            'seller_id' => $seller->id,
            'address_id' => $address->id,
            'items' => [
                ['id' => $product->id, 'quantity' => 1],
            ],
        ]);
        $quotes = $quoteResponse->json('quotes');
        $quoteToken = $quoteResponse->json('quote_token');
        $selectedProvider = $quotes[0];

        $screenshot = UploadedFile::fake()->image('gcash_receipt_screenshot.jpg', 600, 1200);

        $checkoutResponse = $this->actingAs($customer)->post('/checkout', [
            'seller_id' => $seller->id,
            'addressId' => $address->id,
            'paymentMethod' => 'GCash',
            'paymentReference' => '100' . sprintf('%05d%05d', mt_rand(10000, 99999), mt_rand(10000, 99999)),
            'paymentScreenshot' => $screenshot,
            'shipping_provider_id' => $selectedProvider['provider_id'],
            'shipping_quote_token' => $quoteToken,
            'items' => [
                [
                    'id' => $product->id,
                    'productId' => $product->id,
                    'quantity' => 1,
                    'price' => 500.00,
                    'size' => 'Standard',
                    'variation' => 'Original',
                ]
            ],
        ]);
        $checkoutResponse->assertRedirect(route('orders'));

        $order = Order::where('customerId', $customer->id)->latest('createdAt')->first();
        $this->assertNotNull($order);
        $snapshot = OrderShipping::where('order_id', $order->id)->first();
        $this->assertNotNull($snapshot);
        $originalFee = (float) $snapshot->shipping_fee;

        // Change rates in the database to ₱999.00
        ShippingRate::where('id', $snapshot->shipping_rate_id)->update([
            'base_rate' => 999.00,
        ]);

        // Reload snapshot from database: must be unchanged
        $freshSnapshot = $snapshot->fresh();
        $this->assertEquals($originalFee, (float) $freshSnapshot->shipping_fee);
        $this->assertNotEquals(999.00, (float) $freshSnapshot->shipping_fee);
    }

    /* -------------------------------------------------------------------------- */
    /* 7. LEGACY PRODUCT GATING TEST                                              */
    /* -------------------------------------------------------------------------- */

    public function test_legacy_product_without_package_specs_is_blocked_from_checkout()
    {
        $seller = $this->createSeller();
        $customer = $this->createCustomer();
        $address = $this->createAddress($customer);

        // Product without dimensions (legacy product)
        $legacyProduct = Product::create([
            'id' => (string) Str::uuid(),
            'sellerId' => $seller->id,
            'name' => 'Legacy Antique Item',
            'price' => 1000.00,
            'stock' => 5,
            'package_weight_per_unit' => 0.00, // Incomplete specs
            'package_length_per_unit' => 0.00,
            'package_width_per_unit' => 0.00,
            'package_height_per_unit' => 0.00,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $response = $this->actingAs($customer)->postJson('/checkout/shipping-quotes', [
            'seller_id' => $seller->id,
            'address_id' => $address->id,
            'items' => [
                ['id' => $legacyProduct->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => "Product \"{$legacyProduct->name}\" lacks physical package dimensions. The seller must complete package specifications before this item can be shipped.",
        ]);
    }

    /* -------------------------------------------------------------------------- */
    /* 8. SECTION 26 END-TO-END ACCEPTANCE TEST                                   */
    /* -------------------------------------------------------------------------- */

    public function test_section_26_end_to_end_flow()
    {
        // Seller: South Luzon origin (Laguna)
        $seller = $this->createSeller([
            'shopProvince' => 'Laguna',
            'shopCity' => 'Lumban',
            'shopBarangay' => 'Poblacion',
            'shopPostalCode' => '4014',
        ]);

        // Product: 1.00 kg, 30 x 20 x 5 cm, 2 handling days
        $product = $this->createTestProduct($seller, [
            'name' => 'Laguna Woodcraft Bowl',
            'price' => 850.00,
            'stock' => 15,
            'package_weight_per_unit' => 1.00,
            'package_length_per_unit' => 30.00,
            'package_width_per_unit' => 20.00,
            'package_height_per_unit' => 5.00,
            'handling_days' => 2,
        ]);

        // Buyer: Metro Manila destination
        $buyer = $this->createCustomer();
        $buyerAddress = $this->createAddress($buyer, [
            'province' => 'Metro Manila',
            'city' => 'Manila',
            'barangay' => 'Ermita',
            'postalCode' => '1000',
        ]);

        // 1. Request Quotes
        $quoteResponse = $this->actingAs($buyer)->postJson('/checkout/shipping-quotes', [
            'seller_id' => $seller->id,
            'address_id' => $buyerAddress->id,
            'items' => [
                ['id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $quoteResponse->assertStatus(200);
        $quotes = $quoteResponse->json('quotes');
        $quoteToken = $quoteResponse->json('quote_token');

        // Verify quotes exist for providers
        $this->assertNotEmpty($quotes);
        $jntQuote = collect($quotes)->firstWhere('provider_code', 'jnt');
        $spxQuote = collect($quotes)->firstWhere('provider_code', 'spx');
        $lbcQuote = collect($quotes)->firstWhere('provider_code', 'lbc');

        $this->assertNotNull($jntQuote);
        $this->assertNotNull($spxQuote);
        $this->assertNotNull($lbcQuote);

        // Verify estimated delivery includes handling days (2 days handling + provider transit days)
        $this->assertGreaterThanOrEqual(3, $jntQuote['estimated_days_min']);

        $screenshot = UploadedFile::fake()->image('maya_receipt_screenshot.jpg', 600, 1200);

        // 2. Select SPX Express and Checkout
        $checkoutResponse = $this->actingAs($buyer)->post('/checkout', [
            'seller_id' => $seller->id,
            'addressId' => $buyerAddress->id,
            'paymentMethod' => 'Maya',
            'paymentReference' => '900' . sprintf('%05d%04d', mt_rand(10000, 99999), mt_rand(1000, 9999)),
            'paymentScreenshot' => $screenshot,
            'shipping_provider_id' => $spxQuote['provider_id'],
            'shipping_quote_token' => $quoteToken,
            'items' => [
                [
                    'id' => $product->id,
                    'productId' => $product->id,
                    'quantity' => 1,
                    'price' => 850.00,
                    'size' => 'Standard',
                    'variation' => 'Natural',
                ]
            ],
        ]);

        $checkoutResponse->assertRedirect(route('orders'));

        // 3. Verify Order and Immutable Snapshot
        $order = Order::where('customerId', $buyer->id)->latest('createdAt')->first();
        $this->assertNotNull($order);
        $this->assertEquals((float) $spxQuote['shipping_fee'], (float) $order->shippingFee);
        $this->assertEquals(850.00 + (float) $spxQuote['shipping_fee'], (float) $order->totalAmount);

        $shippingSnapshot = OrderShipping::where('order_id', $order->id)->first();
        $this->assertNotNull($shippingSnapshot);
        $this->assertEquals('SPX Express', $shippingSnapshot->provider_name);
        $this->assertEquals('South Luzon', $shippingSnapshot->origin_zone_name);
        $this->assertEquals('National Capital Region (NCR)', $shippingSnapshot->destination_zone_name);
        $this->assertEquals(1.00, (float) $shippingSnapshot->chargeable_weight);
        $this->assertEquals((float) $spxQuote['shipping_fee'], (float) $shippingSnapshot->shipping_fee);

        // 4. Verify Stock was decremented safely
        $this->assertEquals(14, $product->fresh()->stock);
    }

    /* -------------------------------------------------------------------------- */
    /* 9. MULTI-ADDRESS CHECKOUT & TOKEN SWITCHING TEST                           */
    /* -------------------------------------------------------------------------- */

    public function test_multi_address_switching_invalidates_previous_token_and_persists_correct_destination_snapshot()
    {
        // Seller in Lumban, Laguna (South Luzon)
        $seller = $this->createSeller([
            'shopProvince' => 'Laguna',
            'shopCity' => 'Lumban',
            'shopBarangay' => 'Poblacion',
            'shopPostalCode' => '4014',
        ]);

        $product = $this->createTestProduct($seller, [
            'price' => 500.00,
            'stock' => 10,
            'package_weight_per_unit' => 1.00,
            'package_length_per_unit' => 20.00,
            'package_width_per_unit' => 15.00,
            'package_height_per_unit' => 5.00,
            'handling_days' => 2,
        ]);

        $customer = $this->createCustomer();

        // Customer has Address 1 (Manila, NCR)
        $addressA = $this->createAddress($customer, [
            'province' => 'Metro Manila',
            'city' => 'Manila',
            'barangay' => 'Barangay 1',
            'postalCode' => '1000',
            'isDefault' => true,
        ]);

        // Customer has Address 2 (Cebu City, Visayas - Inter-island route)
        $addressB = $this->createAddress($customer, [
            'province' => 'Cebu',
            'city' => 'Cebu City',
            'barangay' => 'Lahug',
            'postalCode' => '6000',
            'isDefault' => false,
        ]);

        // Step 1: Customer requests quotes for Address A (Manila)
        $quoteResponseA = $this->actingAs($customer)->postJson('/checkout/shipping-quotes', [
            'seller_id' => $seller->id,
            'address_id' => $addressA->id,
            'items' => [
                ['id' => $product->id, 'quantity' => 1],
            ],
        ]);
        $quoteResponseA->assertStatus(200);
        $quotesA = $quoteResponseA->json('quotes');
        $tokenA = $quoteResponseA->json('quote_token');
        $this->assertNotEmpty($quotesA);
        $jntQuoteA = collect($quotesA)->firstWhere('provider_code', 'jnt');
        $this->assertNotNull($jntQuoteA);

        // Step 2: Customer switches address to Address B (Cebu)
        $quoteResponseB = $this->actingAs($customer)->postJson('/checkout/shipping-quotes', [
            'seller_id' => $seller->id,
            'address_id' => $addressB->id,
            'items' => [
                ['id' => $product->id, 'quantity' => 1],
            ],
        ]);
        $quoteResponseB->assertStatus(200);
        $quotesB = $quoteResponseB->json('quotes');
        $tokenB = $quoteResponseB->json('quote_token');
        $this->assertNotEmpty($quotesB);
        $jntQuoteB = collect($quotesB)->firstWhere('provider_code', 'jnt');
        $this->assertNotNull($jntQuoteB);

        // Verify that Visayas rate is higher than Luzon rate
        $this->assertNotEquals((float) $jntQuoteA['shipping_fee'], (float) $jntQuoteB['shipping_fee']);
        $this->assertGreaterThan((float) $jntQuoteA['shipping_fee'], (float) $jntQuoteB['shipping_fee']);

        $screenshot = UploadedFile::fake()->image('gcash_receipt.jpg', 600, 1200);

        // Step 3: Malicious/Stale Attempt - submit Address B using Token A
        $staleAttemptResponse = $this->actingAs($customer)->postJson('/checkout', [
            'seller_id' => $seller->id,
            'addressId' => $addressB->id,
            'paymentMethod' => 'GCash',
            'paymentReference' => '100' . sprintf('%05d%05d', mt_rand(10000, 99999), mt_rand(10000, 99999)),
            'paymentScreenshot' => $screenshot,
            'shipping_provider_id' => $jntQuoteA['provider_id'],
            'shipping_quote_token' => $tokenA, // STALE TOKEN BOUND TO ADDRESS A
            'items' => [
                [
                    'id' => $product->id,
                    'productId' => $product->id,
                    'quantity' => 1,
                    'price' => 500.00,
                    'size' => 'Standard',
                    'variation' => 'Original',
                ]
            ],
        ]);

        $staleAttemptResponse->assertStatus(422);
        $staleAttemptResponse->assertJsonFragment([
            'message' => 'Your shipping quote has expired or the order items changed. Please review and refresh your shipping quote.',
        ]);

        // Step 4: Legitimate checkout using Address B with Token B
        $screenshot2 = UploadedFile::fake()->image('gcash_receipt2.jpg', 600, 1200);

        $validCheckoutResponse = $this->actingAs($customer)->post('/checkout', [
            'seller_id' => $seller->id,
            'addressId' => $addressB->id,
            'paymentMethod' => 'GCash',
            'paymentReference' => '100' . sprintf('%05d%05d', mt_rand(10000, 99999), mt_rand(10000, 99999)),
            'paymentScreenshot' => $screenshot2,
            'shipping_provider_id' => $jntQuoteB['provider_id'],
            'shipping_quote_token' => $tokenB,
            'items' => [
                [
                    'id' => $product->id,
                    'productId' => $product->id,
                    'quantity' => 1,
                    'price' => 500.00,
                    'size' => 'Standard',
                    'variation' => 'Original',
                ]
            ],
        ]);

        $validCheckoutResponse->assertRedirect(route('orders'));

        // Step 5: Verify Order & Snapshot match Address B (Visayas)
        $order = Order::where('customerId', $customer->id)->latest('createdAt')->first();
        $this->assertNotNull($order);
        $this->assertEquals((float) $jntQuoteB['shipping_fee'], (float) $order->shippingFee);
        $this->assertEquals(500.00 + (float) $jntQuoteB['shipping_fee'], (float) $order->totalAmount);

        $shippingSnapshot = OrderShipping::where('order_id', $order->id)->first();
        $this->assertNotNull($shippingSnapshot);
        $this->assertEquals('South Luzon', $shippingSnapshot->origin_zone_name);
        $this->assertEquals('Visayas', $shippingSnapshot->destination_zone_name);
        $this->assertEquals((float) $jntQuoteB['shipping_fee'], (float) $shippingSnapshot->shipping_fee);
    }

    /* -------------------------------------------------------------------------- */
    /* 10. SELLER FULFILLMENT & TRACKING SYNCHRONIZATION TEST                     */
    /* -------------------------------------------------------------------------- */

    public function test_seller_shipped_workflow_synchronizes_order_and_order_shipping_status()
    {
        $seller = $this->createSeller();
        $customer = $this->createCustomer();
        $address = $this->createAddress($customer);
        $product = $this->createTestProduct($seller);

        // Get quote and place order
        $quoteResponse = $this->actingAs($customer)->postJson('/checkout/shipping-quotes', [
            'seller_id' => $seller->id,
            'address_id' => $address->id,
            'items' => [
                ['id' => $product->id, 'quantity' => 1],
            ],
        ]);
        $quotes = $quoteResponse->json('quotes');
        $token = $quoteResponse->json('quote_token');
        $chosenQuote = $quotes[0];

        $screenshot = UploadedFile::fake()->image('gcash_receipt.jpg', 600, 1200);

        $this->actingAs($customer)->post('/checkout', [
            'seller_id' => $seller->id,
            'addressId' => $address->id,
            'paymentMethod' => 'GCash',
            'paymentReference' => '100' . sprintf('%05d%05d', mt_rand(10000, 99999), mt_rand(10000, 99999)),
            'paymentScreenshot' => $screenshot,
            'shipping_provider_id' => $chosenQuote['provider_id'],
            'shipping_quote_token' => $token,
            'items' => [
                [
                    'id' => $product->id,
                    'productId' => $product->id,
                    'quantity' => 1,
                    'price' => 500.00,
                    'size' => 'Standard',
                    'variation' => 'Original',
                ]
            ],
        ]);

        $order = Order::where('customerId', $customer->id)->latest('createdAt')->first();
        $this->assertNotNull($order);
        $this->assertEquals('Pending', $order->status);

        $shippingSnapshot = OrderShipping::where('order_id', $order->id)->first();
        $this->assertEquals('Pending', $shippingSnapshot->shipping_status);
        $this->assertNull($shippingSnapshot->tracking_number);

        // Seller accepts order: moves to 'To Ship'
        $toShipResponse = $this->actingAs($seller)->patchJson("/seller/api/orders/{$order->id}/status", [
            'status' => 'To Ship',
        ]);
        $toShipResponse->assertStatus(200);

        $order->refresh();
        $shippingSnapshot->refresh();
        $this->assertEquals('To Ship', $order->status);
        $this->assertEquals('To Ship', $shippingSnapshot->shipping_status);

        // Seller fulfills order: moves to 'Shipped' with courier and tracking number
        $shippedResponse = $this->actingAs($seller)->patchJson("/seller/api/orders/{$order->id}/status", [
            'status' => 'Shipped',
            'courierName' => $chosenQuote['provider_name'],
            'trackingNumber' => 'JNT-TRACK-99887766',
            'trackingLink' => 'https://www.jtexpress.ph/track?billcode=JNT-TRACK-99887766',
        ]);
        $shippedResponse->assertStatus(200);

        $order->refresh();
        $shippingSnapshot->refresh();

        // Verify authoritative synchronization across orders and order_shipping
        $this->assertEquals('Shipped', $order->status);
        $this->assertEquals('JNT-TRACK-99887766', $order->trackingNumber);
        $this->assertEquals('Shipped', $shippingSnapshot->shipping_status);
        $this->assertEquals('JNT-TRACK-99887766', $shippingSnapshot->tracking_number);

        // Verify OrderStatusHistory record exists
        $history = OrderStatusHistory::where('orderId', $order->id)->where('newStatus', 'Shipped')->first();
        $this->assertNotNull($history);
        $this->assertEquals($seller->id, $history->updatedBy);
    }
}

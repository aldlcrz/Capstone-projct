<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\DummyUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class LumBarongDummyUsersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_seeds_all_100_dummy_users_with_exact_names_and_emails()
    {
        $this->seed(DummyUserSeeder::class);

        $jsonPath = database_path('data_dummy_users.json');
        $this->assertFileExists($jsonPath);

        $dummyUsers = json_decode(file_get_contents($jsonPath), true);
        $this->assertCount(100, $dummyUsers);

        foreach ($dummyUsers as $data) {
            $user = User::where('email', strtolower(trim($data['email'])))->first();
            $this->assertNotNull($user, "User with email {$data['email']} should exist in database");
            $this->assertEquals($data['name'], $user->name, "User name for {$data['email']} must match exactly");
            $this->assertEquals('customer', $user->role);
            $this->assertEquals('active', $user->status);
            $this->assertTrue((bool) $user->isVerified);
        }

        $this->assertEquals(100, User::where('role', 'customer')->count());
    }

    /** @test */
    public function it_calculates_customer_orders_dynamically_from_database()
    {
        $this->seed(DummyUserSeeder::class);

        $seller = User::factory()->create([
            'role' => 'seller',
            'name' => 'Lumban Artisan',
            'email' => 'artisan@lumbarong.com',
        ]);

        $firstCustomer = User::where('role', 'customer')->first();
        $this->assertNotNull($firstCustomer);

        // Initial count is 0
        $this->assertEquals(0, $firstCustomer->customerOrders()->count());

        // Create 2 orders for this customer
        Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $firstCustomer->id,
            'sellerId' => $seller->id,
            'totalAmount' => 1500.00,
            'status' => 'completed',
            'paymentMethod' => 'gcash',
            'shippingAddress' => ['address' => 'Lumban, Laguna'],
        ]);

        Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $firstCustomer->id,
            'sellerId' => $seller->id,
            'totalAmount' => 3200.00,
            'status' => 'processing',
            'paymentMethod' => 'gcash',
            'shippingAddress' => ['address' => 'Lumban, Laguna'],
        ]);

        // Customer's order count is now 2
        $this->assertEquals(2, $firstCustomer->customerOrders()->count());

        // Another customer should still have 0 orders
        $secondCustomer = User::where('role', 'customer')->where('id', '!=', $firstCustomer->id)->first();
        $this->assertEquals(0, $secondCustomer->customerOrders()->count());
    }

    /** @test */
    public function admin_users_page_displays_orders_column_and_no_joined_column()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@lumbarong.test',
        ]);

        $seller = User::factory()->create([
            'role' => 'seller',
            'email' => 'seller@lumbarong.test',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'ALLAN E. CORPUZ',
            'email' => 'allanecorpuz@gmail.com',
            'status' => 'active',
        ]);

        Order::create([
            'id' => (string) Str::uuid(),
            'customerId' => $customer->id,
            'sellerId' => $seller->id,
            'totalAmount' => 2500.00,
            'status' => 'pending',
            'paymentMethod' => 'gcash',
            'shippingAddress' => ['address' => 'Lumban, Laguna'],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users'));
        $response->assertOk();

        // Must display Orders header and the actual count
        $response->assertSee('Orders');
        $response->assertSee('ALLAN E. CORPUZ');
        $response->assertSee('allanecorpuz@gmail.com');
        $response->assertSee('1 order');

        // Joined column must NOT be present in table headers
        $response->assertDontSee('<th class="px-4 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[22%] hidden lg:table-cell">Joined</th>', false);
    }

    /** @test */
    public function superadmin_customers_page_displays_orders_placed_without_joined_registered_column()
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin@lumbarong.test',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'LYKA ANDREA RIVERA',
            'email' => 'lykaandrearivera@gmail.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.customers'));
        $response->assertOk();

        $response->assertSee('Orders Placed');
        $response->assertSee('LYKA ANDREA RIVERA');
        $response->assertSee('lykaandrearivera@gmail.com');
        $response->assertSee('0 orders');

        // Registered column header must not be present
        $response->assertDontSee('>Registered</th>', false);
    }

    /** @test */
    public function users_import_command_executes_and_verifies_successfully()
    {
        $exitCode = Artisan::call('users:import-dummy');
        $this->assertEquals(0, $exitCode);

        $verifyExitCode = Artisan::call('users:import-dummy', ['--verify-only' => true]);
        $this->assertEquals(0, $verifyExitCode);
    }
}

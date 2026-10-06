<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanCustomerAccountsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_deletes_all_customers_except_the_specified_keep_email()
    {
        // 1. Keep account
        $keepCustomer = User::factory()->create([
            'email' => 'customer@gmail.com',
            'role'  => 'customer',
            'name'  => 'Kept Customer',
        ]);

        // 2. Customers to delete
        $otherCustomer1 = User::factory()->create([
            'email' => 'buyer1@example.com',
            'role'  => 'customer',
            'name'  => 'Customer One',
        ]);
        Address::create([
            'userId'        => $otherCustomer1->id,
            'recipientName' => 'Customer One',
            'phone'         => '09123456789',
            'region'        => 'Region IV-A',
            'province'      => 'Laguna',
            'city'          => 'Lumban',
            'barangay'      => 'Barangay 1',
            'street'        => '123 Street',
            'houseNo'       => '12',
            'postalCode'    => '4014',
            'isDefault'     => true,
        ]);

        $otherCustomer2 = User::factory()->create([
            'email' => 'buyer2@example.com',
            'role'  => 'customer',
            'name'  => 'Customer Two',
        ]);

        // 3. Seller, Admin, Superadmin that should NEVER be deleted
        $seller = User::factory()->create([
            'email' => 'seller@example.com',
            'role'  => 'seller',
            'name'  => 'Artisan Seller',
        ]);

        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role'  => 'admin',
            'name'  => 'Platform Admin',
        ]);

        $superadmin = User::factory()->create([
            'email' => 'superadmin@example.com',
            'role'  => 'superadmin',
            'name'  => 'Super Admin',
        ]);

        // Run dry-run first
        $this->artisan('customers:clean', ['--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN')
            ->expectsOutputToContain('buyer1@example.com')
            ->expectsOutputToContain('buyer2@example.com')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'buyer1@example.com']);

        // Run with --force
        $this->artisan('customers:clean', ['--force' => true])
            ->expectsOutputToContain('Successfully deleted 2 customer account(s)')
            ->assertExitCode(0);

        // Verify kept customer remains
        $this->assertDatabaseHas('users', ['email' => 'customer@gmail.com']);

        // Verify protected roles remain untouched
        $this->assertDatabaseHas('users', ['email' => 'seller@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'superadmin@example.com']);

        // Verify target customers and related data were deleted
        $this->assertDatabaseMissing('users', ['email' => 'buyer1@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'buyer2@example.com']);
        $this->assertDatabaseMissing('addresses', ['userId' => $otherCustomer1->id]);
    }
}

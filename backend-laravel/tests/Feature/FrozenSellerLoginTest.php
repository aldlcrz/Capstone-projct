<?php

namespace Tests\Feature;

use App\Models\CommissionRecord;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FrozenSellerLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_frozen_seller_without_unpaid_records_renders_login_page_cleanly(): void
    {
        SystemSetting::create(['key' => 'superadmin_gcash_number', 'value' => '09171234567']);
        SystemSetting::create(['key' => 'superadmin_maya_number', 'value' => '09181234567']);

        $seller = User::factory()->create([
            'email' => 'frozen_manual@example.com',
            'password' => Hash::make('password123'),
            'role' => 'seller',
            'status' => 'frozen',
            'isVerified' => true,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'frozen_manual@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);

        $getLogin = $this->get('/login');
        $getLogin->assertStatus(200);
        $getLogin->assertSee('Overdue Commission Settle Portal');
        $getLogin->assertSee('Open GCash / Maya Payment Portal');
        $getLogin->assertSee('09171234567');
        $getLogin->assertSee('09181234567');
    }

    public function test_frozen_seller_with_unpaid_records_shows_amount_and_period(): void
    {
        $seller = User::factory()->create([
            'email' => 'frozen_debt@example.com',
            'password' => Hash::make('password123'),
            'role' => 'seller',
            'status' => 'frozen',
            'isVerified' => true,
        ]);

        CommissionRecord::create([
            'sellerId' => $seller->id,
            'period' => '2026-10',
            'totalSales' => 2844.00,
            'commissionRate' => 5.0,
            'commissionAmount' => 142.20,
            'status' => 'unpaid',
            'dueDate' => now()->subDay(),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'frozen_debt@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);

        $getLogin = $this->get('/login');
        $getLogin->assertStatus(200);
        $getLogin->assertSee('Overdue Commission Settle Portal');
    }

    public function test_frozen_seller_with_pending_proof_shows_status_tracker(): void
    {
        $seller = User::factory()->create([
            'email' => 'frozen_pending@example.com',
            'password' => Hash::make('password123'),
            'role' => 'seller',
            'status' => 'frozen',
            'isVerified' => true,
        ]);

        CommissionRecord::create([
            'sellerId' => $seller->id,
            'period' => '2026-10',
            'totalSales' => 2844.00,
            'commissionRate' => 5.0,
            'commissionAmount' => 142.20,
            'status' => 'unpaid',
            'paymentProof' => 'proofs/sample.jpg',
            'referenceNumber' => 'REF998877',
            'paymentMethod' => 'GCash',
            'dueDate' => now()->subDay(),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'frozen_pending@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('commission_payment_pending', true);

        $getLogin = $this->get('/login');
        $getLogin->assertStatus(200);
        $getLogin->assertSee('Payment Verification');
        $getLogin->assertSee('Admin Review');
    }

    public function test_frozen_seller_modal_displays_restoration_instructions_and_avoids_contradictory_login_prompt(): void
    {
        SystemSetting::create(['key' => 'superadmin_gcash_number', 'value' => '09171234567']);
        SystemSetting::create(['key' => 'superadmin_maya_number', 'value' => '09181234567']);

        $seller = User::factory()->create([
            'email' => 'frozen_instructions@example.com',
            'password' => Hash::make('password123'),
            'role' => 'seller',
            'status' => 'frozen',
            'isVerified' => true,
        ]);

        CommissionRecord::create([
            'sellerId' => $seller->id,
            'period' => '2026-10',
            'totalSales' => 1000.00,
            'commissionRate' => 5.0,
            'commissionAmount' => 50.00,
            'status' => 'unpaid',
            'dueDate' => now()->subDay(),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'frozen_instructions@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');

        $getLogin = $this->get('/login');
        $getLogin->assertStatus(200);
        $getLogin->assertSee('How Account Restoration Works');
        $getLogin->assertSee('Close Notice');
        $getLogin->assertDontSee('Please log in to your artisan account to securely submit your commission settlement proof');
    }
}

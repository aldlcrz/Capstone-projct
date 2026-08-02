<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_report_endpoint(): void
    {
        $customer = User::create([
            'name' => 'John Customer',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'role' => 'customer',
        ]);

        $seller = User::create([
            'name' => 'Artisan Seller',
            'email' => 'artisan@example.com',
            'password' => bcrypt('password123'),
            'role' => 'seller',
        ]);

        $response = $this->actingAs($customer)
            ->postJson('/api/v1/reports', [
                'reportedId' => $seller->id,
                'type' => 'CustomerReportingSeller',
                'reason' => 'Fraud / Scam',
                'description' => 'This seller is listing items that do not exist. Please investigate.',
                'evidence' => 'http://localhost:8000/uploads/misc/evidence_screenshot.png'
            ]);

        $response->assertStatus(201);
        $this->assertEquals(1, Report::count());
        $this->assertEquals('http://localhost:8000/uploads/misc/evidence_screenshot.png', Report::first()->evidence);
    }
}

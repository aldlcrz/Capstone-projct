<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerReportsViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_submitted_reports_without_blade_compilation_errors(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'approved',
            'isVerified' => true,
        ]);

        $seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'approved',
            'isVerified' => true,
        ]);

        $product = Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Handcrafted Barong',
            'sellerId' => $seller->id,
            'price' => 1500,
            'stock' => 10,
            'status' => 'approved',
        ]);

        $report = Report::create([
            'id' => (string) Str::uuid(),
            'reporterId' => $customer->id,
            'reportedId' => $seller->id,
            'productId' => $product->id,
            'type' => 'CustomerReportingSeller',
            'reason' => 'Counterfeit / Fake Item',
            'description' => 'Item quality does not match description.',
            'status' => 'Pending',
            'reportType' => 'product',
            'referenceId' => 'ORD-12345678',
            'createdAt' => now(),
        ]);

        $response = $this->actingAs($customer)->get(route('profile.reports'));

        $response->assertStatus(200);
        $response->assertSee('My Submitted Reports');
        $response->assertSee('Counterfeit / Fake Item');
        $response->assertSee('Handcrafted Barong');
        $response->assertDontSee('{!!');
        $response->assertDontSee('$rType');
        $response->assertDontSee('$rtype');
    }
}

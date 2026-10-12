<?php

namespace Tests\Feature;

use App\Models\ArchivedRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperAdminCustomerManagementGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role'       => 'superadmin',
            'isVerified' => true,
            'status'     => 'active',
        ]);

        $this->customer = User::factory()->create([
            'name'       => 'Maria Santos',
            'email'      => 'maria.santos@test.lumbarong.shop',
            'role'       => 'customer',
            'isVerified' => true,
            'status'     => 'active',
        ]);
    }

    public function test_superadmin_customer_management_page_has_actions_and_no_view_only_restriction(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.customers'));

        $response->assertOk();
        $response->assertSee('Customer');
        $response->assertSee('Management');
        $response->assertSee('Supreme Control');
        $response->assertSee('Actions');
        $response->assertSee('Ban');
        $response->assertSee('Delete');
        $response->assertSee('Maria Santos');

        // Conflicting view-only labels must be absent
        $response->assertDontSee('View Only');
        $response->assertDontSee('in view-only mode');
    }

    public function test_superadmin_can_ban_customer_with_custom_reason(): void
    {
        $response = $this->actingAs($this->superAdmin)->patch("/superadmin/customers/{$this->customer->id}/ban", [
            'reason' => 'Violation of platform terms: continuous fake orders',
        ]);

        $response->assertRedirect(route('superadmin.customers'));
        $this->customer->refresh();
        $this->assertEquals('blocked', $this->customer->status);
        $this->assertEquals('Violation of platform terms: continuous fake orders', $this->customer->violationReason);

        // Visiting customers view should now show Restore button for this user
        $viewResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.customers'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Restore');
        $viewResponse->assertSee('Banned');
    }

    public function test_superadmin_can_unban_and_restore_customer(): void
    {
        $this->customer->update([
            'status'          => 'blocked',
            'violationReason' => 'Temporary suspension',
        ]);

        $response = $this->actingAs($this->superAdmin)->patch("/superadmin/customers/{$this->customer->id}/unban");

        $response->assertRedirect(route('superadmin.customers'));
        $this->customer->refresh();
        $this->assertEquals('active', $this->customer->status);
        $this->assertNull($this->customer->violationReason);
    }

    public function test_superadmin_can_permanently_delete_and_archive_customer(): void
    {
        $customerId = $this->customer->id;

        $response = $this->actingAs($this->superAdmin)->delete("/superadmin/customers/{$customerId}", [
            'reason' => 'Account purge requested by user',
        ]);

        $response->assertRedirect(route('superadmin.customers'));
        $this->assertNull(User::find($customerId));

        // Ensure customer was archived
        $archived = ArchivedRecord::where('item_type', 'customer')
            ->where('item_id', $customerId)
            ->first();

        $this->assertNotNull($archived);
        $this->assertEquals('Maria Santos', $archived->name);
        $this->assertEquals('maria.santos@test.lumbarong.shop', $archived->identifier);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_initialize_shop_default_categories()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.categories.initialize'));

        $response->assertRedirect();
        
        $this->assertDatabaseHas('categories', ['name' => 'Wedding Barong']);
        $this->assertDatabaseHas('categories', ['name' => 'Filipiniana Gown']);
        $this->assertDatabaseHas('categories', ['name' => 'Polo Barong']);
        $this->assertDatabaseHas('categories', ['name' => 'Boys\' Barong']);
        $this->assertDatabaseHas('categories', ['name' => 'Accessories']);
    }

    public function test_admin_can_create_and_delete_category()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Custom Handcrafted Shawl',
                'description' => 'Fine embroidered shawls',
                'target_group' => ['Women'],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Custom Handcrafted Shawl']);

        $category = Category::where('name', 'Custom Handcrafted Shawl')->first();

        /** @var User $admin */
        $deleteResponse = $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category->id));

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}

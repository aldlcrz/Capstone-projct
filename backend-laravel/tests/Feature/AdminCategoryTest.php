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

    public function test_deleted_accessories_category_is_not_recreated_by_seeder_or_seller_pages()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $accessories = Category::create([
            'name' => 'Accessories',
            'description' => 'Cufflinks and pins',
            'target_group' => ['Men', 'Women'],
        ]);

        $heritageAccessories = Category::create([
            'name' => 'Heritage Accessories',
            'description' => 'Heirloom brooches',
            'target_group' => ['Men', 'Women'],
        ]);

        $weddingBarong = Category::create([
            'name' => 'Wedding Barong',
            'description' => 'Formal wedding barongs',
            'target_group' => ['Men'],
        ]);

        // Admin deletes Accessories
        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $accessories->id), [
                'reason' => 'unnecessary',
            ])
            ->assertRedirect();

        // Both Accessories and its empty twin Heritage Accessories are removed
        $this->assertDatabaseMissing('categories', ['name' => 'Accessories']);
        $this->assertDatabaseMissing('categories', ['name' => 'Heritage Accessories']);
        $this->assertDatabaseHas('archived_records', [
            'item_type' => 'category',
            'name' => 'Accessories',
        ]);

        // Subsequent seeder calls or seller product page loads must NOT recreate deleted categories
        \Database\Seeders\CategorySeeder::ensureDefaultCategories(true);
        \Database\Seeders\CategorySeeder::ensureDefaultCategories(false);

        $this->actingAs($admin)
            ->post(route('admin.categories.initialize'))
            ->assertRedirect();

        $this->assertDatabaseMissing('categories', ['name' => 'Accessories']);
        $this->assertDatabaseMissing('categories', ['name' => 'Heritage Accessories']);
        $this->assertDatabaseHas('categories', ['id' => $weddingBarong->id]);
    }
}

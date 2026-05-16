<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create Admin
        User::factory()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'LumBarong Admin',
            'email' => 'admin@lumbarong.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        // Create Seller
        User::factory()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Artisan Seller',
            'email' => 'seller@lumbarong.com',
            'password' => 'password',
            'role' => 'seller',
        ]);

        // Create Customer
        User::factory()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Heritage Customer',
            'email' => 'customer@lumbarong.com',
            'password' => 'password',
            'role' => 'customer',
        ]);
    }
}

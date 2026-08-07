<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            [
                'name' => 'Wedding Barong',
                'description' => 'Formal hand-embroidered wedding barongs for grooms and entourage',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Piña Formal Barong',
                'description' => 'Fine handwoven Piña silk formal barongs',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Jusi Classic Barong',
                'description' => 'Classic Jusi embroidered barongs for events and office wear',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Polo Barong',
                'description' => 'Short-sleeve casual and modern polo barongs',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Camisa de Chino',
                'description' => 'Traditional inner undershirts for Barong Tagalog',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Filipiniana Gown',
                'description' => 'Elegant hand-embroidered Filipiniana gowns and Maria Clara attire',
                'target_group' => ['Women'],
            ],
            [
                'name' => 'Modern Terno Top',
                'description' => 'Stylish modern butterfly sleeve terno tops',
                'target_group' => ['Women'],
            ],
            [
                'name' => 'Lady Barong',
                'description' => 'Tailored lady barong blouses for women',
                'target_group' => ['Women'],
            ],
            [
                'name' => 'Boys\' Barong',
                'description' => 'Miniature traditional barong tagalog for boys',
                'target_group' => ['Kids'],
            ],
            [
                'name' => 'Girls\' Filipiniana',
                'description' => 'Traditional and modern Filipiniana dresses for girls',
                'target_group' => ['Kids'],
            ],
            [
                'name' => 'Accessories',
                'description' => 'Cufflinks, pins, and heritage Filipiniana accessories',
                'target_group' => [],
            ],
        ];

        // Clean up old generic demographic categories if they have no products
        Category::whereIn('name', ['Men', 'Women', 'Kids'])
            ->doesntHave('products')
            ->delete();

        foreach ($defaults as $cat) {
            Category::firstOrCreate(
                ['name' => $cat['name']],
                [
                    'id' => (string) Str::uuid(),
                    'description' => $cat['description'],
                    'target_group' => $cat['target_group'],
                ]
            );
        }
    }
}

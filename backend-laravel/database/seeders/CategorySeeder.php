<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Get the default list of Lumban heritage categories.
     */
    public static function getDefaultCategories(): array
    {
        return [
            [
                'name' => 'Camisa de Chino',
                'description' => 'Traditional inner undershirts for Barong Tagalog',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Casual',
                'description' => 'Relaxed everyday Barongs, polo barongs, and modern casual linen pieces',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Formal Barong',
                'description' => 'Fine formal hand-embroidered Barong Tagalog garments for ceremonies',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Jusi Classic Barong',
                'description' => 'Classic Jusi embroidered barongs for events, diplomacy, and office wear',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Lumban Specials',
                'description' => 'Exclusive bespoke creations handcrafted by master embroiderers of Lumban',
                'target_group' => ['Men', 'Women'],
            ],
            [
                'name' => 'Modern',
                'description' => 'Contemporary minimalist and modern silhouettes blending heritage craftsmanship',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Piña Formal Barong',
                'description' => 'Exquisite handwoven Piña silk formal barongs with intricate calado',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Semi-Formal',
                'description' => 'Versatile semi-formal barongs ideal for banquets, dinners, and gatherings',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Special Occasion',
                'description' => 'Gala, anniversary, graduation, and landmark milestone event attire',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Traditional',
                'description' => 'Timeless full-open and half-open Barong Tagalog in traditional Lumban style',
                'target_group' => ['Men'],
            ],
            [
                'name' => 'Wedding Barong',
                'description' => 'Formal hand-embroidered wedding barongs for grooms and entourage',
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
        ];
    }

    /**
     * Ensure all default categories are persisted in database.
     * Respects administrative deletions recorded in archived_records.
     */
    public static function ensureDefaultCategories(bool $onlyIfEmpty = false): void
    {
        try {
            if ($onlyIfEmpty && Category::count() > 0) {
                return;
            }

            $defaults = self::getDefaultCategories();

            // Clean up old generic demographic categories if they have no products
            Category::whereIn('name', ['Men', 'Women', 'Kids'])
                ->doesntHave('products')
                ->delete();

            // Never recreate categories that an Admin/SuperAdmin previously deleted & archived
            $archivedNames = [];
            if (\Illuminate\Support\Facades\Schema::hasTable('archived_records')) {
                $archivedNames = \App\Models\ArchivedRecord::where('item_type', 'category')
                    ->pluck('name')
                    ->map(fn ($name) => mb_strtolower(trim((string) $name), 'UTF-8'))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }

            foreach ($defaults as $cat) {
                $lowerName = mb_strtolower(trim($cat['name']), 'UTF-8');
                if (in_array($lowerName, $archivedNames, true)) {
                    continue;
                }

                $category = Category::where('name', $cat['name'])->first();
                if (!$category) {
                    Category::create([
                        'id' => (string) Str::uuid(),
                        'name' => $cat['name'],
                        'description' => $cat['description'],
                        'target_group' => $cat['target_group'],
                    ]);
                } else {
                    // Update target_group if missing
                    if (empty($category->target_group)) {
                        $category->target_group = $cat['target_group'];
                        $category->save();
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fail safely if DB table is inaccessible
        }
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        self::ensureDefaultCategories();
    }
}

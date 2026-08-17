<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class AdminCategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')
            ->orderBy('name', 'asc')
            ->get();

        // Auto-heal categories that have generic placeholder images
        foreach ($categories as $cat) {
            if (empty($cat->image) || $cat->image === '/uploads/categories/pina_formal.png') {
                $matched = $cat->getImageUrl();
                if ($matched !== $cat->image) {
                    $cat->update(['image' => $matched]);
                }
            }
        }
            
        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $name = trim($request->name);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_group' => 'required|array|min:1',
            'target_group.*' => 'in:Men,Women,Kids',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ], [
            'target_group.required' => 'Please select at least one tag (Men, Women, or Kids).',
            'target_group.min' => 'Please select at least one tag (Men, Women, or Kids).',
            'image.required' => 'A category image is required.',
        ]);

        // Case-insensitive duplicate name check
        $exists = Category::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])->exists();
        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => 'A category with this name already exists.'])
                ->with('error', 'Category "' . $name . '" already exists. Duplicate category names are not allowed.');
        }

        $imagePath = '/uploads/categories/pina_formal.png';
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'cat_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            $destinationPath = public_path('uploads/categories');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }
            
            $file->move($destinationPath, $filename);
            $imagePath = '/uploads/categories/' . $filename;
        }

        Category::create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'description' => $request->description,
            'target_group' => $request->target_group,
            'image' => $imagePath,
        ]);

        return redirect()->back()->with('success', 'Category "' . $name . '" created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);
        $name = trim($request->name);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_group' => 'required|array|min:1',
            'target_group.*' => 'in:Men,Women,Kids',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ], [
            'target_group.required' => 'Please select at least one tag (Men, Women, or Kids).',
            'target_group.min' => 'Please select at least one tag (Men, Women, or Kids).',
        ]);

        // Case-insensitive duplicate check against other categories
        $exists = Category::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => 'A category with this name already exists.'])
                ->with('error', 'Category "' . $name . '" already exists. Duplicate category names are not allowed.');
        }

        $updateData = [
            'name' => $name,
            'description' => $request->description,
            'target_group' => $request->target_group,
        ];

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'cat_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            $destinationPath = public_path('uploads/categories');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }
            
            $file->move($destinationPath, $filename);
            $updateData['image'] = '/uploads/categories/' . $filename;
        }

        $category->update($updateData);

        return redirect()->back()->with('success', 'Category updated successfully.');
    }

    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        // Check if category has products
        // Note: Category model has products relationship
        if ($category->products()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete category with active products.');
        }

        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully.');
    }

    /**
     * Initialize default categories if they don't exist.
     */
    public function initializeDefaults()
    {
        $defaults = [
            [
                'name' => 'Wedding Barong',
                'description' => 'Formal hand-embroidered wedding barongs for grooms and entourage',
                'target_group' => ['Men'],
                'image' => '/uploads/categories/wedding_groom.png',
            ],
            [
                'name' => 'Piña Formal Barong',
                'description' => 'Fine handwoven Piña silk formal barongs',
                'target_group' => ['Men'],
                'image' => '/uploads/categories/pina_formal.png',
            ],
            [
                'name' => 'Jusi Classic Barong',
                'description' => 'Classic Jusi embroidered barongs for events and office wear',
                'target_group' => ['Men'],
                'image' => '/uploads/categories/jusi_classic.png',
            ],
            [
                'name' => 'Polo Barong',
                'description' => 'Short-sleeve casual and modern polo barongs',
                'target_group' => ['Men'],
                'image' => '/uploads/categories/polo_casual.png',
            ],
            [
                'name' => 'Camisa de Chino',
                'description' => 'Traditional inner undershirts for Barong Tagalog',
                'target_group' => ['Men'],
                'image' => '/uploads/categories/camisa_undershirt.png',
            ],
            [
                'name' => 'Filipiniana Gown',
                'description' => 'Elegant hand-embroidered Filipiniana gowns and Maria Clara attire',
                'target_group' => ['Women'],
                'image' => '/uploads/categories/women_filipiniana.png',
            ],
            [
                'name' => 'Modern Terno Top',
                'description' => 'Stylish modern butterfly sleeve terno tops',
                'target_group' => ['Women'],
                'image' => '/uploads/categories/women_terno.png',
            ],
            [
                'name' => 'Lady Barong',
                'description' => 'Tailored lady barong blouses for women',
                'target_group' => ['Women'],
                'image' => '/uploads/categories/women_lady_barong.png',
            ],
            [
                'name' => 'Boys\' Barong',
                'description' => 'Miniature traditional barong tagalog for boys',
                'target_group' => ['Kids'],
                'image' => '/uploads/categories/kids_boys.png',
            ],
            [
                'name' => 'Girls\' Filipiniana',
                'description' => 'Traditional and modern Filipiniana dresses for girls',
                'target_group' => ['Kids'],
                'image' => '/uploads/categories/kids_girls.png',
            ],
            [
                'name' => 'Accessories',
                'description' => 'Cufflinks, pins, and heritage Filipiniana accessories',
                'target_group' => [],
                'image' => '/uploads/categories/accessories.png',
            ],
        ];

        // Clean up old generic demographic categories if they have no active products
        Category::whereIn('name', ['Men', 'Women', 'Kids'])
            ->doesntHave('products')
            ->delete();

        $added = 0;

        foreach ($defaults as $cat) {
            $existing = Category::where('name', $cat['name'])->first();
            if (!$existing) {
                Category::create([
                    'id' => (string) Str::uuid(),
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'target_group' => $cat['target_group'],
                    'image' => $cat['image'],
                ]);
                $added++;
            } else if (empty($existing->image) || $existing->image === '/uploads/categories/pina_formal.png') {
                $existing->update(['image' => $cat['image']]);
            }
        }

        // Also sync any other legacy categories with matching keyword images
        foreach (Category::all() as $category) {
            if (empty($category->image) || $category->image === '/uploads/categories/pina_formal.png') {
                $category->update(['image' => $category->getImageUrl()]);
            }
        }

        return redirect()->back()->with('success', "Updated and synchronized default category images.");
    }
}

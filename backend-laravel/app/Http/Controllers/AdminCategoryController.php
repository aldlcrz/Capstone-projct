<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')
            ->orderBy('name', 'asc')
            ->get();
            
        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'target_group' => 'nullable|array',
            'target_group.*' => 'in:Men,Women,Kids',
        ]);

        Category::create([
            'id' => (string) Str::uuid(),
            'name' => $request->name,
            'description' => $request->description,
            'target_group' => $request->target_group ?? [],
        ]);

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string',
            'target_group' => 'nullable|array',
            'target_group.*' => 'in:Men,Women,Kids',
        ]);

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'target_group' => $request->target_group ?? [],
        ]);

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
        $defaults = ['Women', 'Kids', 'Men'];
        $added = 0;

        foreach ($defaults as $name) {
            if (!Category::where('name', $name)->exists()) {
                Category::create([
                    'id' => (string) Str::uuid(),
                    'name' => $name,
                    'description' => "Products for {$name}",
                    'target_group' => [$name],
                ]);
                $added++;
            }
        }

        return redirect()->back()->with('success', "Initialized {$added} default categories.");
    }
}

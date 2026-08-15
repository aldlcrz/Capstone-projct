<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories.
     */
    public function index(Request $request)
    {
        $tree = $request->query('tree');

        if ($tree === 'true') {
            $categories = Category::whereNull('parentId')
                ->with(['children' => function($query) {
                    $query->with('children');
                }])
                ->orderBy('id', 'asc')
                ->get();
            return response()->json($categories);
        }

        $categories = Category::with('parent:id,name')
            ->orderBy('id', 'asc')
            ->get();
            
        return response()->json($categories);
    }

    /**
     * Get category by ID.
     */
    public function show($id)
    {
        $category = Category::with(['parent', 'children'])->find($id);
        if (!$category) return response()->json(['message' => 'Category not found'], 404);
        return response()->json($category);
    }

    /**
     * Create a new category.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:categories,name',
            'description' => 'nullable|string',
            'parentId' => 'nullable|exists:categories,id',
        ]);

        $category = Category::create($request->all());
        return response()->json($category, 201);
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|required|unique:categories,name,' . $id,
            'description' => 'nullable|string',
            'parentId' => 'nullable|exists:categories,id',
        ]);

        $category->update($request->all());
        return response()->json($category);
    }

    /**
     * Delete a category.
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        if ($category->children()->count() > 0) {
            return response()->json(['message' => 'Cannot delete category with subcategories.'], 400);
        }

        $productCount = Product::where('categories', 'like', "%{$category->name}%")->count();
        if ($productCount > 0) {
            return response()->json(['message' => "Cannot delete '{$category->name}' because it contains {$productCount} active product(s). Remove them first."], 400);
        }

        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
}

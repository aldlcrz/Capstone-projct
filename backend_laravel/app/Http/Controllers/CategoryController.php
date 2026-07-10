<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function getCategories(Request $request)
    {
        try {
            $tree = $request->query('tree');

            if ($tree === 'true') {
                $categories = Category::whereNull('parentId')
                    ->with(['children' => function ($query) {
                        $query->with('children'); // Supports 3 levels deep
                    }])
                    ->orderBy('id', 'ASC')
                    ->get();

                return response()->json($categories);
            }

            $categories = Category::with('parent:id,name')
                ->orderBy('id', 'ASC')
                ->get();

            return response()->json($categories);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function getCategoryById(string|int $id)
    {
        try {
            $category = Category::with(['parent', 'children'])->find($id);

            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }

            return response()->json($category);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function createCategory(Request $request)
    {
        try {
            $name = $request->input('name');
            $description = $request->input('description');
            $parentId = $request->input('parentId');

            $existing = Category::where('name', $name)->first();
            if ($existing) {
                return response()->json(['message' => 'Category already exists'], 400);
            }

            $category = Category::create([
                'name' => $name,
                'description' => $description,
                'parentId' => $parentId
            ]);

            return response()->json($category, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateCategory(Request $request, string|int $id)
    {
        try {
            $name = $request->input('name');
            $description = $request->input('description');
            $parentId = $request->input('parentId');

            $category = Category::find($id);
            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }

            $category->update([
                'name' => $name,
                'description' => $description,
                'parentId' => $parentId
            ]);

            return response()->json($category);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteCategory(string|int $id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }

            $hasChildren = Category::where('parentId', $category->id)->count();
            if ($hasChildren > 0) {
                return response()->json(['message' => 'Cannot delete category with subcategories.'], 400);
            }

            // Products list JSON check
            $productCount = Product::where('categories', 'like', "%{$category->name}%")->count();
            if ($productCount > 0) {
                return response()->json([
                    'message' => "Cannot delete '{$category->name}' because it contains {$productCount} active product(s). Remove them first."
                ], 400);
            }

            $category->delete();
            return response()->json(['message' => 'Category deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductManagementController extends Controller
{
    public function index()
    {
        $products = Product::where('sellerId', Auth::id())->orderBy('createdAt', 'desc')->get();
        return view('seller.products.index', compact('products'));
    }

    public function create()
    {
        $categories = \App\Models\Category::orderBy('name', 'asc')->get();
        return view('seller.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:100',
            'description' => 'required',
            'price' => 'required|numeric|min:1|max:10000',
            'CategoryId' => 'required|exists:categories,id',
            'images' => 'required|array',
            'images.*' => 'image',
        ]);

        try {
            $product = new Product();
            $product->id = (string) Str::uuid();
            $product->sellerId = Auth::id();
            $product->name = $request->name;
            $product->description = $request->description;
            $product->price = $request->price;
            $product->stock = $request->stock ?? 0;
            $product->CategoryId = $request->CategoryId;
            $product->status = 'pending'; // Needs admin approval
            
            $images = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('products', 'public');
                    $images[] = $path;
                }
            }
            $product->image = $images;
            $product->save();

            return redirect()->route('seller.products.index')->with('success', 'Product listed and awaiting approval.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to list product: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $product = Product::where('id', $id)->where('sellerId', Auth::id())->firstOrFail();
        $categories = \App\Models\Category::orderBy('name', 'asc')->get();
        return view('seller.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::where('id', $id)->where('sellerId', Auth::id())->firstOrFail();

        $request->validate([
            'name'        => 'required|max:100',
            'description' => 'required',
            'price'       => 'required|numeric|min:1|max:10000',
            'stock'       => 'required|integer|min:0',
            'CategoryId'  => 'required|exists:categories,id',
            'images.*'    => 'nullable|image',
        ]);

        $product->name        = $request->name;
        $product->description = $request->description;
        $product->price       = $request->price;
        $product->stock       = $request->stock;
        $product->CategoryId  = $request->CategoryId;
        $product->sizes       = $request->sizes ?? [];
        $product->status      = 'pending'; // Re-submit for approval on edit

        // Handle image removal
        $currentImages = is_array($product->image)
            ? $product->image
            : (json_decode($product->image ?? '[]', true) ?? []);

        if ($request->has('remove_images')) {
            $toRemove = $request->remove_images;
            $currentImages = array_filter($currentImages, fn($img) => !in_array($img, $toRemove));
            // Delete physical files for local storage paths
            foreach ($toRemove as $img) {
                if (!str_starts_with($img, 'http')) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($img);
                }
            }
        }

        // Handle new image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $currentImages[] = $path;
            }
        }

        $product->image = array_values($currentImages);
        $product->save();

        return redirect()->route('seller.products.index')->with('success', 'Product updated and pending review.');
    }

    public function destroy($id)
    {
        $product = Product::where('id', $id)->where('sellerId', Auth::id())->firstOrFail();

        // Delete product images from local storage
        $images = is_array($product->image)
            ? $product->image
            : (json_decode($product->image ?? '[]', true) ?? []);

        foreach ($images as $img) {
            if (!str_starts_with($img, 'http')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($img);
            }
        }

        $product->delete();

        return redirect()->route('seller.products.index')->with('success', 'Product listing removed.');
    }
}

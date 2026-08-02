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
        $user = Auth::user();
        if (!$user->isPremiumActive()) {
            $productCount = Product::where('sellerId', $user->id)->count();
            if ($productCount >= 10) {
                return redirect()->route('seller.products.index')->with('error', 'Free accounts are limited to 10 product listings. Upgrade to Premium for unlimited listings!');
            }
        }
        $categories = \App\Models\Category::orderBy('name', 'asc')->get();
        return view('seller.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isPremiumActive()) {
            $productCount = Product::where('sellerId', $user->id)->count();
            if ($productCount >= 10) {
                return redirect()->route('seller.products.index')->with('error', 'Free accounts are limited to 10 product listings. Upgrade to Premium for unlimited listings!');
            }
        }

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
            $product->shippingFee = $request->shippingFee ?? 0;
            $product->shippingDays = $request->shippingDays ?? 5;
            $product->CategoryId = $request->CategoryId;
            $product->target_group = $request->target_group ?? null;
            $product->sizes = $request->sizes ?? [];
            
            if ($request->has('size_stocks') && is_array($request->size_stocks)) {
                $selectedSizes = $request->sizes ?? [];
                $sizeStocks = array_filter($request->size_stocks, function($key) use ($selectedSizes) {
                    return in_array($key, $selectedSizes);
                }, ARRAY_FILTER_USE_KEY);
                $product->size_stocks = $sizeStocks;
                if (!empty($sizeStocks)) {
                    $product->stock = array_sum(array_map('intval', $sizeStocks));
                } else {
                    $product->stock = $request->stock ?? 0;
                }
            } else {
                $product->size_stocks = [];
                $product->stock = $request->stock ?? 0;
            }

            // Per-product payment availability flags
            $product->is_gcash_available = $request->has('product_is_gcash_available');
            $product->gcash_number       = null;
            $product->gcash_qr_code      = null;
            $product->is_maya_available  = $request->has('product_is_maya_available');
            $product->maya_number        = null;
            $product->maya_qr_code       = null;

            // Lumban Special discount
            $product->is_on_sale          = $request->boolean('is_on_sale');
            $product->discount_percentage = $product->is_on_sale ? ($request->discount_percentage ?? 0) : null;

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

            // Notify admins about the new product listing
            \App\Models\Notification::sendToAdmins(
                'New Product Listed',
                "Artisan " . Auth::user()->name . " has listed a new product: \"{$product->name}\" for review.",
                'system',
                '/admin/products'
            );

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
            'CategoryId'  => 'required|exists:categories,id',
            'images.*'    => 'nullable|image',
        ]);

        $product->name         = $request->name;
        $product->description  = $request->description;
        $product->price        = $request->price;
        $product->shippingFee  = $request->shippingFee ?? 0;
        $product->shippingDays = $request->shippingDays ?? 5;
        $product->CategoryId   = $request->CategoryId;
        $product->target_group = $request->target_group ?? null;
        $product->sizes        = $request->sizes ?? [];
        
        if ($request->has('size_stocks') && is_array($request->size_stocks)) {
            $selectedSizes = $request->sizes ?? [];
            $sizeStocks = array_filter($request->size_stocks, function($key) use ($selectedSizes) {
                return in_array($key, $selectedSizes);
            }, ARRAY_FILTER_USE_KEY);
            $product->size_stocks = $sizeStocks;
            if (!empty($sizeStocks)) {
                $product->stock = array_sum(array_map('intval', $sizeStocks));
            } else {
                $product->stock = $request->stock;
            }
        } else {
            $product->size_stocks = [];
            $product->stock = $request->stock;
        }

        // Per-product payment availability flags
        $product->is_gcash_available = $request->has('product_is_gcash_available');
        if ($product->gcash_qr_code) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($product->gcash_qr_code);
        }
        $product->gcash_number       = null;
        $product->gcash_qr_code      = null;

        $product->is_maya_available  = $request->has('product_is_maya_available');
        if ($product->maya_qr_code) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($product->maya_qr_code);
        }
        $product->maya_number        = null;
        $product->maya_qr_code       = null;

        // Lumban Special discount
        $product->is_on_sale          = $request->boolean('is_on_sale');
        $product->discount_percentage = $product->is_on_sale ? ($request->discount_percentage ?? 0) : null;

        $product->status = 'pending'; // Re-submit for approval on edit

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

        // Notify admins about the product update
        \App\Models\Notification::sendToAdmins(
            'Product Listing Updated',
            "Artisan " . Auth::user()->name . " has updated product listing: \"{$product->name}\". It is pending review.",
            'system',
            '/admin/products'
        );

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

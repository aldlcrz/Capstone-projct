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
        /** @var \App\Models\User $user */
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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isPremiumActive()) {
            $productCount = Product::where('sellerId', $user->id)->count();
            if ($productCount >= 10) {
                return redirect()->route('seller.products.index')->with('error', 'Free accounts are limited to 10 product listings. Upgrade to Premium for unlimited listings!');
            }
        }

        $request->validate([
            'name'                => 'required|string|max:100',
            'description'         => 'required|string',
            'price'               => 'required|numeric|min:1|max:10000',
            'CategoryId'          => 'required|exists:categories,id',
            'images'              => 'required|array|min:1',
            'images.*'            => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'sizes'               => 'required|array|min:1',
            'sizes.*'             => 'string',
            'size_stocks.*'       => 'nullable|integer|min:0|max:10000',
            'shippingFee'         => 'nullable|numeric|min:0|max:500',
            'shippingDays'        => 'nullable|integer|min:1|max:30',
            'discount_percentage' => 'nullable|numeric|min:1|max:99',
        ], [
            'name.required'        => 'Product Name is required.',
            'description.required' => 'Artisan Description is required.',
            'price.required'       => 'Product Price is required.',
            'price.min'            => 'Product Price must be at least ₱1.00.',
            'price.max'            => 'Product Price cannot exceed ₱10,000.00.',
            'shippingFee.max'      => 'Shipping Fee cannot exceed ₱500.00.',
            'shippingDays.max'     => 'Estimated Shipping Days cannot exceed 30 days.',
            'size_stocks.*.max'    => 'Size stock quantity cannot exceed 10,000 units.',
            'CategoryId.required'  => 'Please select a Product Category.',
            'images.required'      => 'Please upload at least one product image.',
            'sizes.required'       => 'Please select at least one Heritage Size (e.g. S, M, L, XL, XXL, Custom).',
            'sizes.min'            => 'Please select at least one Heritage Size (e.g. S, M, L, XL, XXL, Custom).',
        ]);

        $selectedSizes = $request->sizes ?? [];
        $sizeStocks = is_array($request->size_stocks) ? array_filter($request->size_stocks, function($key) use ($selectedSizes) {
            return in_array($key, $selectedSizes);
        }, ARRAY_FILTER_USE_KEY) : [];

        $totalStock = array_sum(array_map('intval', $sizeStocks));
        if ($totalStock <= 0) {
            return redirect()->back()->withInput()->with('error', 'Please assign a stock quantity greater than 0 for at least one selected size.');
        }

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
            $product->sizes = $selectedSizes;
            $product->size_stocks = $sizeStocks;
            $product->stock = $totalStock;

            // Ensure storage directories exist on Hostinger / server
            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('qrcodes');
            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('products');

            // Per-product payment availability and overrides
            $product->is_gcash_available = $request->has('product_is_gcash_available');
            $product->gcash_number       = $request->filled('gcashNumber') ? $request->gcashNumber : null;
            if ($request->hasFile('gcashQrCode')) {
                $file = $request->file('gcashQrCode');
                $filename = time() . '_gcash_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/qrcodes'), $filename);
                $product->gcash_qr_code = 'uploads/qrcodes/' . $filename;
            } else {
                $product->gcash_qr_code = null;
            }

            $product->is_maya_available  = $request->has('product_is_maya_available');
            $product->maya_number        = $request->filled('mayaNumber') ? $request->mayaNumber : null;
            if ($request->hasFile('mayaQrCode')) {
                $file = $request->file('mayaQrCode');
                $filename = time() . '_maya_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/qrcodes'), $filename);
                $product->maya_qr_code = 'uploads/qrcodes/' . $filename;
            } else {
                $product->maya_qr_code = null;
            }

            // Lumban Special discount
            $product->is_on_sale          = $request->boolean('is_on_sale');
            $product->discount_percentage = $product->is_on_sale ? ($request->discount_percentage ?? 0) : null;

            $product->status = 'pending'; // Needs admin approval

            $images = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $filename = time() . '_' . Str::random(8) . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('uploads/products'), $filename);
                    $images[] = 'uploads/products/' . $filename;
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

    public function edit(string $id)
    {
        $product = Product::where('id', $id)->where('sellerId', Auth::id())->firstOrFail();
        $categories = \App\Models\Category::orderBy('name', 'asc')->get();
        return view('seller.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $product = Product::where('id', $id)->where('sellerId', Auth::id())->firstOrFail();

        $request->validate([
            'name'                => 'required|string|max:100',
            'description'         => 'required|string',
            'price'               => 'required|numeric|min:1|max:10000',
            'CategoryId'          => 'required|exists:categories,id',
            'images.*'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'sizes'               => 'required|array|min:1',
            'sizes.*'             => 'string',
            'size_stocks.*'       => 'nullable|integer|min:0|max:10000',
            'shippingFee'         => 'nullable|numeric|min:0|max:500',
            'shippingDays'        => 'nullable|integer|min:1|max:30',
            'discount_percentage' => 'nullable|numeric|min:1|max:99',
        ], [
            'name.required'        => 'Product Name is required.',
            'description.required' => 'Artisan Description is required.',
            'price.required'       => 'Product Price is required.',
            'price.min'            => 'Product Price must be at least ₱1.00.',
            'price.max'            => 'Product Price cannot exceed ₱10,000.00.',
            'shippingFee.max'      => 'Shipping Fee cannot exceed ₱500.00.',
            'shippingDays.max'     => 'Estimated Shipping Days cannot exceed 30 days.',
            'size_stocks.*.max'    => 'Size stock quantity cannot exceed 10,000 units.',
            'CategoryId.required'  => 'Please select a Product Category.',
            'sizes.required'       => 'Please select at least one Heritage Size (e.g. S, M, L, XL, XXL, Custom).',
            'sizes.min'            => 'Please select at least one Heritage Size (e.g. S, M, L, XL, XXL, Custom).',
        ]);

        $selectedSizes = $request->sizes ?? [];
        $sizeStocks = is_array($request->size_stocks) ? array_filter($request->size_stocks, function($key) use ($selectedSizes) {
            return in_array($key, $selectedSizes);
        }, ARRAY_FILTER_USE_KEY) : [];

        $totalStock = array_sum(array_map('intval', $sizeStocks));
        if ($totalStock <= 0) {
            return redirect()->back()->withInput()->with('error', 'Please assign a stock quantity greater than 0 for at least one selected size.');
        }

        $product->name         = $request->name;
        $product->description  = $request->description;
        $product->price        = $request->price;
        $product->shippingFee  = $request->shippingFee ?? 0;
        $product->shippingDays = $request->shippingDays ?? 5;
        $product->CategoryId   = $request->CategoryId;
        $product->target_group = $request->target_group ?? null;
        $product->sizes        = $selectedSizes;
        $product->size_stocks  = $sizeStocks;
        $product->stock        = $totalStock;

        // Per-product payment availability and overrides
        $product->is_gcash_available = $request->has('product_is_gcash_available');
        if ($request->filled('gcashNumber')) {
            $product->gcash_number = $request->gcashNumber;
        }

        if ($request->hasFile('gcashQrCode')) {
            $file = $request->file('gcashQrCode');
            $filename = time() . '_gcash_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/qrcodes'), $filename);
            $product->gcash_qr_code = 'uploads/qrcodes/' . $filename;
        }

        $product->is_maya_available = $request->has('product_is_maya_available');
        if ($request->filled('mayaNumber')) {
            $product->maya_number = $request->mayaNumber;
        }

        if ($request->hasFile('mayaQrCode')) {
            $file = $request->file('mayaQrCode');
            $filename = time() . '_maya_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/qrcodes'), $filename);
            $product->maya_qr_code = 'uploads/qrcodes/' . $filename;
        }

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
                    $cleanImg = preg_replace('/^(storage|uploads)\//', '', $img);
                    $cleanImg = ltrim(str_replace('\\', '/', $cleanImg), '/');
                    if (file_exists(public_path('uploads/' . $cleanImg))) {
                        @unlink(public_path('uploads/' . $cleanImg));
                    }
                    if (file_exists(public_path('uploads/products/' . $cleanImg))) {
                        @unlink(public_path('uploads/products/' . $cleanImg));
                    }
                }
            }
        }

        // Handle new image uploads (prepend new images so the newest photo becomes primary thumbnail)
        if ($request->hasFile('images')) {
            $newImages = [];
            foreach ($request->file('images') as $image) {
                $filename = time() . '_' . Str::random(8) . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/products'), $filename);
                $newImages[] = 'uploads/products/' . $filename;
            }
            $currentImages = array_merge($newImages, array_values($currentImages));
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

    public function destroy(string $id)
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

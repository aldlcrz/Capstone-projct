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
        $products = Product::where('sellerId', Auth::id())
            ->with(['reviews.customer:id,name,profilePhoto'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('createdAt', 'desc')
            ->get();
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
        foreach ($categories as $cat) {
            $tg = $cat->target_group;
            if (empty($tg) || !is_array($tg)) {
                $nameLower = strtolower($cat->name);
                if (str_contains($nameLower, 'gown') || str_contains($nameLower, 'terno') || str_contains($nameLower, 'lady') || (str_contains($nameLower, 'filipiniana') && !str_contains($nameLower, 'girl'))) {
                    $cat->target_group = ['Women'];
                } elseif (str_contains($nameLower, 'boy') || str_contains($nameLower, 'girl') || str_contains($nameLower, 'kid')) {
                    $cat->target_group = ['Kids'];
                } elseif (str_contains($nameLower, 'barong') || str_contains($nameLower, 'camisa') || str_contains($nameLower, 'polo') || str_contains($nameLower, 'men')) {
                    $cat->target_group = ['Men'];
                } else {
                    $cat->target_group = [];
                }
            }
        }
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

        $isDraft = $request->input('action') === 'draft';

        if ($isDraft) {
            $request->validate([
                'name'                => 'required|string|max:100',
                'description'         => 'nullable|string|max:500',
                'price'               => 'nullable|numeric|min:0|max:10000',
                'images.*'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'CategoryId'          => 'nullable|exists:categories,id',
            ], [
                'name.required' => 'Product Name is required to save a draft.',
            ]);
        } else {
            $request->validate([
                'name'                => 'required|string|max:100',
                'description'         => 'required|string|min:10|max:500',
                'price'               => 'required|numeric|min:1|max:10000',
                'shippingFee'         => 'required|numeric|min:0|max:500',
                'shippingDays'        => 'required|integer|min:1|max:30',
                'CategoryId'          => 'required|exists:categories,id',
                'target_group'        => 'required|string|in:Men,Women,Kids',
                'images'              => 'required|array|min:1',
                'images.*'            => 'image|mimes:jpeg,png,jpg,webp|max:5120',
                'sizes'               => 'required|array|min:1',
                'sizes.*'             => 'string',
                'size_stocks.*'       => 'nullable|integer|min:0|max:10000',
                'discount_percentage' => 'nullable|numeric|min:1|max:99',
            ], [
                'name.required'         => 'Product Name is required.',
                'description.required'  => 'Artisan Description is required.',
                'description.min'       => 'Artisan Description must be at least 10 characters.',
                'price.required'        => 'Product Price is required.',
                'price.min'             => 'Product Price must be at least ₱1.00.',
                'price.max'             => 'Product Price cannot exceed ₱10,000.00.',
                'shippingFee.required'  => 'Shipping Fee is required (enter 0 for free delivery).',
                'shippingFee.max'       => 'Shipping Fee cannot exceed ₱500.00.',
                'shippingDays.required' => 'Estimated Shipping Days is required.',
                'shippingDays.min'      => 'Estimated Shipping Days must be at least 1 day.',
                'shippingDays.max'      => 'Estimated Shipping Days cannot exceed 30 days.',
                'CategoryId.required'   => 'Please select a Product Category.',
                'target_group.required' => 'Please select who this product is for (Men, Women, or Kids).',
                'images.required'       => 'Please upload at least one product image.',
                'sizes.required'        => 'Please select at least one Heritage Size (e.g. S, M, L, XL, XXL, Custom).',
                'sizes.min'             => 'Please select at least one Heritage Size (e.g. S, M, L, XL, XXL, Custom).',
                'size_stocks.*.max'     => 'Size stock quantity cannot exceed 10,000 units.',
            ]);

            $hasCompletePayment = false;

            // GCash validation
            if ($request->has('product_is_gcash_available')) {
                $hasGcashNumber = !empty($request->gcashNumber) || !empty($user->gcashNumber);
                $hasGcashQr = $request->hasFile('gcashQrCode') || !empty($user->gcashQrCode);
                if (!$hasGcashNumber || !$hasGcashQr) {
                    return redirect()->back()->withInput()->with('error', 'GCash is enabled but incomplete. Both a GCash Mobile Number and a QR Code are required.');
                }
                $hasCompletePayment = true;
            }

            // Maya validation
            if ($request->has('product_is_maya_available')) {
                $hasMayaNumber = !empty($request->mayaNumber) || !empty($user->mayaNumber);
                $hasMayaQr = $request->hasFile('mayaQrCode') || !empty($user->mayaQrCode);
                if (!$hasMayaNumber || !$hasMayaQr) {
                    return redirect()->back()->withInput()->with('error', 'Maya is enabled but incomplete. Both a Maya Account Number and a QR Code are required.');
                }
                $hasCompletePayment = true;
            }

            if (!$hasCompletePayment) {
                return redirect()->back()->withInput()->with('error', 'Please enable at least one complete payment method with both a mobile number and a QR code.');
            }

            $selectedSizes = $request->sizes ?? [];
            $sizeStocks = is_array($request->size_stocks) ? array_filter($request->size_stocks, function($key) use ($selectedSizes) {
                return in_array($key, $selectedSizes);
            }, ARRAY_FILTER_USE_KEY) : [];

            $totalStock = array_sum(array_map('intval', $sizeStocks));
            if ($totalStock <= 0) {
                return redirect()->back()->withInput()->with('error', 'Please assign a stock quantity greater than 0 for at least one selected size.');
            }
        }

        $selectedSizes = $request->sizes ?? [];
        $sizeStocks = is_array($request->size_stocks) ? array_filter($request->size_stocks, function($key) use ($selectedSizes) {
            return in_array($key, $selectedSizes);
        }, ARRAY_FILTER_USE_KEY) : [];
        $totalStock = array_sum(array_map('intval', $sizeStocks));

        try {
            $product = new Product();
            $product->id = (string) Str::uuid();
            $product->sellerId = Auth::id();
            $product->name = $request->name;
            $product->description = $request->description ?? '';
            $product->fabric_type = $request->input('fabric_type', '100% Piña');
            $product->price = $request->price ?? 0;
            $product->shippingFee = $request->shippingFee ?? 0;
            $product->shippingDays = $request->shippingDays ?? 5;
            $product->CategoryId = $request->CategoryId ?: \App\Models\Category::first()?->id;
            $product->target_group = $request->target_group ?? 'Men';
            $product->sizes = !empty($selectedSizes) ? $selectedSizes : ['M'];
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

            // Custom Size Guide Image & Measurements
            if ($request->hasFile('size_guide_image')) {
                $file = $request->file('size_guide_image');
                $filename = time() . '_sizeguide_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/sizeguides'), $filename);
                $product->size_guide_image = 'uploads/sizeguides/' . $filename;
            } else {
                $product->size_guide_image = null;
            }

            $sizeMeasurements = $request->input('size_guide_measurements', []);
            if (is_array($sizeMeasurements)) {
                $cleanMeasurements = array_filter($sizeMeasurements, function($m) {
                    return !empty($m['size']) && (!empty($m['chest']) || !empty($m['shoulder']) || !empty($m['length']) || !empty($m['sleeves']) || !empty($m['width']));
                });
                $product->size_guide_measurements = array_values($cleanMeasurements);
            } else {
                $product->size_guide_measurements = null;
            }

            // Lumban Special discount
            $product->is_on_sale          = $request->boolean('is_on_sale');
            $product->discount_percentage = $product->is_on_sale ? ($request->discount_percentage ?? 0) : null;

            // Product Variations / Variants (Variant Name & Product Image)
            $product->has_variants = $request->boolean('has_variants');
            $savedVariations = [];
            if ($product->has_variants) {
                $variantNames = $request->input('variant_names', []);
                $variantImages = $request->file('variant_images', []);

                if (!file_exists(public_path('uploads/products/variants'))) {
                    @mkdir(public_path('uploads/products/variants'), 0777, true);
                }

                if (is_array($variantNames)) {
                    foreach ($variantNames as $idx => $vName) {
                        $vName = trim($vName);
                        if (empty($vName)) continue;

                        $vImgPath = null;
                        if (isset($variantImages[$idx]) && $variantImages[$idx]->isValid()) {
                            $vFile = $variantImages[$idx];
                            $vFileName = time() . '_variant_' . Str::random(8) . '.' . $vFile->getClientOriginalExtension();
                            $vFile->move(public_path('uploads/products/variants'), $vFileName);
                            $vImgPath = 'uploads/products/variants/' . $vFileName;
                        }

                        $savedVariations[] = [
                            'name'  => $vName,
                            'image' => $vImgPath,
                        ];
                    }
                }
                $product->variations = !empty($savedVariations) ? $savedVariations : null;
            } else {
                $product->variations = null;
            }

            $product->status = $isDraft ? 'draft' : 'pending'; // Draft vs Pending Admin Approval

            $images = [];
            $uploadedHashes = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $hash = md5_file($image->getRealPath());
                    if (in_array($hash, $uploadedHashes)) {
                        continue; // Skip duplicate image in same upload batch
                    }
                    $uploadedHashes[] = $hash;

                    $filename = time() . '_' . Str::random(8) . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('uploads/products'), $filename);
                    $images[] = 'uploads/products/' . $filename;
                }
            }
            $product->image = !empty($images) ? $images : ['products/default.jpg'];
            $product->save();

            if (!$isDraft) {
                // Notify admins about the new product listing
                \App\Models\Notification::sendToAdmins(
                    'New Product Listed',
                    "Artisan " . Auth::user()->name . " has listed a new product: \"{$product->name}\" for review.",
                    'system',
                    '/admin/products'
                );
                return redirect()->route('seller.products.index')->with('success', 'Product listed and awaiting admin approval.');
            }

            return redirect()->route('seller.products.index')->with('success', 'Product saved as draft.');

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to save product: ' . $e->getMessage());
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
            'description'         => 'required|string|min:10|max:500',
            'price'               => 'required|numeric|min:1|max:10000',
            'CategoryId'          => 'required|exists:categories,id',
            'target_group'        => 'required|string|in:Men,Women,Kids',
            'images.*'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'size_guide_image'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'sizes'               => 'required|array|min:1',
            'sizes.*'             => 'string',
            'size_stocks.*'       => 'nullable|integer|min:0|max:10000',
            'shippingFee'         => 'required|numeric|min:0|max:500',
            'shippingDays'        => 'required|integer|min:1|max:30',
            'discount_percentage' => 'nullable|numeric|min:1|max:99',
        ], [
            'name.required'         => 'Product Name is required.',
            'description.required'  => 'Artisan Description is required.',
            'description.min'       => 'Artisan Description must be at least 10 characters.',
            'price.required'        => 'Product Price is required.',
            'price.min'             => 'Product Price must be at least ₱1.00.',
            'price.max'             => 'Product Price cannot exceed ₱10,000.00.',
            'shippingFee.required'  => 'Shipping Fee is required (enter 0 for free delivery).',
            'shippingFee.max'       => 'Shipping Fee cannot exceed ₱500.00.',
            'shippingDays.required' => 'Estimated Shipping Days is required.',
            'shippingDays.max'      => 'Estimated Shipping Days cannot exceed 30 days.',
            'size_stocks.*.max'     => 'Size stock quantity cannot exceed 10,000 units.',
            'CategoryId.required'   => 'Please select a Product Category.',
            'target_group.required' => 'Please select who this product is for (Men, Women, or Kids).',
            'sizes.required'        => 'Please select at least one Heritage Size (e.g. S, M, L, XL, XXL, Custom).',
            'sizes.min'             => 'Please select at least one Heritage Size (e.g. S, M, L, XL, XXL, Custom).',
        ]);

        $user = Auth::user();
        $hasCompletePayment = false;

        // GCash validation
        if ($request->has('product_is_gcash_available')) {
            $hasGcashNumber = !empty($request->gcashNumber) || !empty($product->gcash_number) || !empty($user->gcashNumber);
            $hasGcashQr = $request->hasFile('gcashQrCode') || !empty($product->gcash_qr_code) || !empty($user->gcashQrCode);
            if (!$hasGcashNumber || !$hasGcashQr) {
                return redirect()->back()->withInput()->with('error', 'GCash is enabled but incomplete. Both a GCash Mobile Number and a QR Code are required.');
            }
            $hasCompletePayment = true;
        }

        // Maya validation
        if ($request->has('product_is_maya_available')) {
            $hasMayaNumber = !empty($request->mayaNumber) || !empty($product->maya_number) || !empty($user->mayaNumber);
            $hasMayaQr = $request->hasFile('mayaQrCode') || !empty($product->maya_qr_code) || !empty($user->mayaQrCode);
            if (!$hasMayaNumber || !$hasMayaQr) {
                return redirect()->back()->withInput()->with('error', 'Maya is enabled but incomplete. Both a Maya Account Number and a QR Code are required.');
            }
            $hasCompletePayment = true;
        }

        if (!$hasCompletePayment) {
            return redirect()->back()->withInput()->with('error', 'Please enable at least one complete payment method with both a mobile number and a QR code.');
        }

        $selectedSizes = $request->sizes ?? [];
        $sizeStocks = is_array($request->size_stocks) ? array_filter($request->size_stocks, function($key) use ($selectedSizes) {
            return in_array($key, $selectedSizes);
        }, ARRAY_FILTER_USE_KEY) : [];

        $totalStock = array_sum(array_map('intval', $sizeStocks));
        if ($totalStock <= 0) {
            return redirect()->back()->withInput()->with('error', 'Please assign a stock quantity greater than 0 for at least one selected size.');
        }

        $oldSizeStocks = is_array($product->size_stocks) ? $product->size_stocks : [];
        $oldTotalStock = (int) $product->stock;
        $wasApproved   = ($product->status === 'approved');

        $product->name         = $request->name;
        $product->description  = $request->description;
        $product->fabric_type  = $request->input('fabric_type', '100% Piña');
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

        // Custom Size Guide Image & Measurements
        if ($request->hasFile('size_guide_image')) {
            $file = $request->file('size_guide_image');
            $filename = time() . '_sizeguide_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/sizeguides'), $filename);
            $product->size_guide_image = 'uploads/sizeguides/' . $filename;
        }

        $sizeMeasurements = $request->input('size_guide_measurements', []);
        if (is_array($sizeMeasurements)) {
            $cleanMeasurements = array_filter($sizeMeasurements, function($m) {
                return !empty($m['size']) && (!empty($m['chest']) || !empty($m['shoulder']) || !empty($m['length']) || !empty($m['sleeves']) || !empty($m['width']));
            });
            $product->size_guide_measurements = array_values($cleanMeasurements);
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

        // Handle new image uploads (prepend new images so the newest photo becomes primary thumbnail, skip duplicates)
        if ($request->hasFile('images')) {
            $newImages = [];
            $existingHashes = [];

            foreach ($currentImages as $existingImgPath) {
                $cleanPath = preg_replace('/^(storage|uploads)\//', '', str_replace('\\', '/', $existingImgPath));
                $cleanPath = ltrim($cleanPath, '/');
                $fullPath = public_path('uploads/' . $cleanPath);
                if (!file_exists($fullPath)) {
                    $fullPath = public_path('uploads/products/' . $cleanPath);
                }
                if (file_exists($fullPath) && is_file($fullPath)) {
                    $existingHashes[] = md5_file($fullPath);
                }
            }

            foreach ($request->file('images') as $image) {
                $hash = md5_file($image->getRealPath());
                if (in_array($hash, $existingHashes)) {
                    continue; // Skip duplicate image file
                }
                $existingHashes[] = $hash;

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

        // Auto-add restocked wishlisted items to customer cart & send email notification if approved
        if ($wasApproved && $product->status === 'approved') {
            try {
                \App\Services\WishlistService::handleProductRestocked($product, $oldSizeStocks, $oldTotalStock);
            } catch (\Throwable $we) {
                \Illuminate\Support\Facades\Log::warning('Wishlist restock handling error on seller product update: ' . $we->getMessage());
            }
        }

        return redirect()->route('seller.products.index')->with('success', 'Product updated and pending review.');
    }

    public function destroy(string $id)
    {
        $product = Product::where('id', $id)->where('sellerId', Auth::id())->firstOrFail();

        // Archive product before deletion
        try {
            \App\Models\ArchivedRecord::archive('product', $product, 'Deleted by seller from catalogue', Auth::user()->name);
        } catch (\Throwable $ae) {
            \Illuminate\Support\Facades\Log::warning('Archive error on seller product destroy: ' . $ae->getMessage());
        }

        $product->delete();

        return redirect()->route('seller.products.index')->with('success', 'Product listing deleted and archived successfully.');
    }

    public function updateSizeGuides(Request $request)
    {
        $request->validate([
            'target_group'     => 'required|in:Men,Women,Kids',
            'size_guide_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'size_guide_image.required' => 'Please select a size guide image file to upload.',
            'size_guide_image.image'    => 'The file must be a valid image (JPEG, PNG, WEBP).',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $targetGroup = $request->input('target_group');
        $sizeGuides = is_array($user->size_guides) ? $user->size_guides : [];

        if ($request->hasFile('size_guide_image')) {
            $file = $request->file('size_guide_image');
            $filename = time() . '_sg_' . strtolower($targetGroup) . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/sizeguides'), $filename);
            $sizeGuides[$targetGroup] = 'uploads/sizeguides/' . $filename;
        }

        $user->size_guides = $sizeGuides;
        $user->save();

        return redirect()->back()->with('success', 'Size guide for ' . $targetGroup . ' updated successfully!');
    }

    public function deleteSizeGuide(string $targetGroup)
    {
        if (!in_array($targetGroup, ['Men', 'Women', 'Kids'])) {
            return redirect()->back()->with('error', 'Invalid target group.');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $sizeGuides = is_array($user->size_guides) ? $user->size_guides : [];

        if (isset($sizeGuides[$targetGroup])) {
            $filePath = public_path($sizeGuides[$targetGroup]);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            unset($sizeGuides[$targetGroup]);
            $user->size_guides = $sizeGuides;
            $user->save();
        }

        return redirect()->back()->with('success', 'Size guide for ' . $targetGroup . ' removed.');
    }
}

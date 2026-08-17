<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AdminBannerController extends Controller
{
    public function index()
    {
        // One-time cleanup for legacy placeholder subtitles
        Banner::where('subtitle', 'like', '%macapagal%')->update(['subtitle' => 'LumBarong Shop']);

        // Ensure order indexes are clean 1, 2, 3...
        $this->normalizeOrderIndexes();

        // All banners ordered by display order
        $banners = Banner::with('user')
            ->orderBy('order_index', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Seller-submitted banner requests (for "Seller Requests" tab)
        $sellerBanners = Banner::with('user')
            ->whereNotNull('userId')
            ->orderByRaw("FIELD(status,'pending','approved','rejected')")
            ->orderBy('created_at', 'desc')
            ->get();

        $pendingCount = Banner::whereNotNull('userId')->where('status', 'pending')->count();

        // Data for Dynamic Action Destination Pickers
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        
        $sellers = User::where('role', 'seller')
            ->where('isVerified', true)
            ->with(['products' => function($q) {
                $q->where('status', 'approved')->select('id', 'name', 'image', 'sellerId', 'price');
            }])
            ->select('id', 'name', 'shopName')
            ->orderBy('shopName')
            ->get()
            ->map(function (User $s) {
                return [
                    'id'        => (string)$s->id,
                    'name'      => $s->name,
                    'shop_name' => $s->shopName ?: $s->name,
                    'products'  => $s->products->map(function (Product $p) {
                        return [
                            'id'        => (string)$p->id,
                            'name'      => $p->name,
                            'price'     => (float)$p->price,
                            'image_url' => $p->getImageUrl(),
                        ];
                    }),
                ];
            });

        $allProducts = Product::where('status', 'approved')
            ->with('seller:id,name,shopName')
            ->select('id', 'name', 'image', 'sellerId', 'price')
            ->orderBy('name')
            ->get()
            ->map(function (Product $p) {
                return [
                    'id'        => (string)$p->id,
                    'name'      => $p->name,
                    'price'     => (float)$p->price,
                    'image_url' => $p->getImageUrl(),
                    'seller_id' => (string)$p->sellerId,
                    'shop_name' => $p->seller?->shopName ?: $p->seller?->name ?: 'Artisan Shop',
                ];
            });

        return view('admin.banners.index', compact(
            'banners',
            'sellerBanners',
            'pendingCount',
            'categories',
            'sellers',
            'allProducts'
        ));
    }

    public function searchDestinations(Request $request)
    {
        $type = $request->input('type', 'product');
        $q = trim($request->input('q', ''));

        if ($type === 'product') {
            $results = Product::where('status', 'approved')
                ->when($q, function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%");
                })
                ->select('id', 'name')
                ->limit(25)
                ->get()
                ->map(function (Product $p) {
                    return [
                        'id'    => (string)$p->id,
                        'title' => $p->name,
                        'url'   => '/products/' . $p->id,
                    ];
                });
            return response()->json($results);
        }

        if ($type === 'seller') {
            $results = User::where('role', 'seller')
                ->where('isVerified', true)
                ->when($q, function ($query) use ($q) {
                    $query->where(function ($sq) use ($q) {
                        $sq->where('shopName', 'like', "%{$q}%")
                           ->orWhere('name', 'like', "%{$q}%");
                    });
                })
                ->select('id', 'name', 'shopName')
                ->limit(25)
                ->get()
                ->map(function ($s) {
                    $label = $s->shopName ?: $s->name;
                    return [
                        'id'    => (string)$s->id,
                        'title' => $label,
                        'url'   => '/shops/' . $s->id,
                    ];
                });
            return response()->json($results);
        }

        if ($type === 'category') {
            $results = Category::when($q, function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%");
                })
                ->select('id', 'name')
                ->get()
                ->map(function ($c) {
                    return [
                        'id'    => (string)$c->id,
                        'title' => $c->name,
                        'url'   => '/?category=' . $c->id . '#catalogue-section',
                    ];
                });
            return response()->json($results);
        }

        return response()->json([]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'preset_image_url' => 'nullable|string',
            'title'            => 'nullable|string|max:60',
            'subtitle'         => 'nullable|string|max:100',
            'button_text_1'    => 'nullable|string|max:50',
            'button_url_1'     => 'nullable|string|max:255',
            'button_text_2'    => 'nullable|string|max:50',
            'button_url_2'     => 'nullable|string|max:255',
            'order_index'      => 'nullable|integer|min:1',
            'start_date'       => 'nullable|date',
            'end_date'         => 'nullable|date|after_or_equal:start_date',
            'is_active'        => 'nullable|boolean',
        ]);

        $imagePath = '';
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Ensure folder exists
            $destinationPath = public_path('uploads/banners');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }

            $file->move($destinationPath, $filename);
            $imagePath = 'uploads/banners/' . $filename;
        } elseif ($request->filled('preset_image_url')) {
            $imagePath = $request->preset_image_url;
        }

        if (!$imagePath) {
            return redirect()->back()->withErrors(['image' => 'Please upload a promotion image or select a product to use its image.']);
        }

        // Determine 1-based order index safely
        $targetOrder = (int) ($request->order_index ?: (Banner::max('order_index') + 1));
        if ($targetOrder < 1) {
            $targetOrder = 1;
        }

        // Shift existing banners to prevent duplicates
        Banner::where('order_index', '>=', $targetOrder)->increment('order_index');

        Banner::create([
            'image_path'    => $imagePath,
            'title'         => $request->title,
            'subtitle'      => $request->subtitle,
            'button_text_1' => $request->button_text_1,
            'button_url_1'  => $request->button_url_1,
            'button_text_2' => $request->button_text_2,
            'button_url_2'  => $request->button_url_2,
            'order_index'   => $targetOrder,
            'start_date'    => $request->start_date ? date('Y-m-d H:i:s', strtotime($request->start_date)) : null,
            'end_date'      => $request->end_date ? date('Y-m-d H:i:s', strtotime($request->end_date)) : null,
            'is_active'     => $request->has('is_active') ? (bool)$request->is_active : true,
        ]);

        $this->normalizeOrderIndexes();

        return redirect()->back()->with('success', 'Hero Promotion created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'title'         => 'nullable|string|max:60',
            'subtitle'      => 'nullable|string|max:100',
            'button_text_1' => 'nullable|string|max:50',
            'button_url_1'  => 'nullable|string|max:255',
            'button_text_2' => 'nullable|string|max:50',
            'button_url_2'  => 'nullable|string|max:255',
            'order_index'   => 'nullable|integer|min:1',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'is_active'     => 'nullable|boolean',
        ]);

        $targetOrder = (int) ($request->order_index ?: $banner->order_index);
        if ($targetOrder < 1) {
            $targetOrder = 1;
        }

        $data = [
            'title'         => $request->title,
            'subtitle'      => $request->subtitle,
            'button_text_1' => $request->button_text_1,
            'button_url_1'  => $request->button_url_1,
            'button_text_2' => $request->button_text_2,
            'button_url_2'  => $request->button_url_2,
            'order_index'   => $targetOrder,
            'start_date'    => $request->start_date ? date('Y-m-d H:i:s', strtotime($request->start_date)) : null,
            'end_date'      => $request->end_date ? date('Y-m-d H:i:s', strtotime($request->end_date)) : null,
            'is_active'     => $request->has('is_active') ? (bool)$request->is_active : false,
        ];

        if ($request->hasFile('image')) {
            // Delete old image if not default
            if ($banner->image_path && !str_contains($banner->image_path, 'default')) {
                $oldPath = public_path($banner->image_path);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }

            // Upload new image
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Ensure folder exists
            $destinationPath = public_path('uploads/banners');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }

            $file->move($destinationPath, $filename);
            $data['image_path'] = 'uploads/banners/' . $filename;
        }

        $banner->update($data);
        $this->normalizeOrderIndexes();

        return redirect()->back()->with('success', 'Hero Promotion updated successfully.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ordered_ids'   => 'required|array',
            'ordered_ids.*' => 'exists:banners,id',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->ordered_ids as $index => $id) {
                Banner::where('id', $id)->update(['order_index' => $index + 1]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Promotions display order updated successfully.',
        ]);
    }

    public function destroy(string $id)
    {
        $banner = Banner::findOrFail($id);

        // Delete image file
        if ($banner->image_path && !str_contains($banner->image_path, 'default')) {
            $filePath = public_path($banner->image_path);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        $banner->delete();
        $this->normalizeOrderIndexes();

        return redirect()->back()->with('success', 'Promotion deleted successfully.');
    }

    public function toggleActive(string $id)
    {
        $banner = Banner::findOrFail($id);
        $banner->is_active = !$banner->is_active;
        $banner->save();

        return redirect()->back()->with('success', 'Promotion visibility updated.');
    }

    public function approve(string $id)
    {
        $banner = Banner::findOrFail($id);
        $banner->update([
            'status'           => 'approved',
            'is_active'        => true,
            'rejection_reason' => null,
        ]);

        if ($banner->userId) {
            Notification::create([
                'userId'     => $banner->userId,
                'title'      => 'Hero Banner Approved',
                'message'    => 'Your requested hero banner "' . ($banner->title ?: 'Untitled') . '" has been approved and is now live on the homepage!',
                'targetRole' => 'seller',
                'isRead'     => false,
                'link'       => '/seller/banners',
            ]);
        }

        return redirect()->back()->with('success', 'Banner request approved successfully.');
    }

    public function reject(Request $request, string $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $banner = Banner::findOrFail($id);
        $banner->update([
            'status'           => 'rejected',
            'is_active'        => false,
            'rejection_reason' => $request->rejection_reason,
        ]);

        if ($banner->userId) {
            Notification::create([
                'userId'     => $banner->userId,
                'title'      => 'Hero Banner Rejected',
                'message'    => 'Your requested hero banner "' . ($banner->title ?: 'Untitled') . '" was rejected. Reason: ' . $request->rejection_reason,
                'targetRole' => 'seller',
                'isRead'     => false,
                'link'       => '/seller/banners',
            ]);
        }

        return redirect()->back()->with('success', 'Banner request rejected.');
    }

    /**
     * Atomically compacts display order indices so they run 1, 2, 3, ... without gaps.
     */
    private function normalizeOrderIndexes(): void
    {
        $banners = Banner::orderBy('order_index', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($banners as $index => $banner) {
            $properIndex = $index + 1;
            if ($banner->order_index !== $properIndex) {
                $banner->update(['order_index' => $properIndex]);
            }
        }
    }
}

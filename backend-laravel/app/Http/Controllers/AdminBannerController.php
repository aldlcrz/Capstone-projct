<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminBannerController extends Controller
{
    public function index()
    {
        // All banners (for "All Banners" tab)
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

        return view('admin.banners.index', compact('banners', 'sellerBanners', 'pendingCount'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:1000',
            'button_text_1' => 'nullable|string|max:50',
            'button_url_1' => 'nullable|string|max:255',
            'button_text_2' => 'nullable|string|max:50',
            'button_url_2' => 'nullable|string|max:255',
            'order_index' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
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
        }

        Banner::create([
            'image_path' => $imagePath,
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'button_text_1' => $request->button_text_1,
            'button_url_1' => $request->button_url_1,
            'button_text_2' => $request->button_text_2,
            'button_url_2' => $request->button_url_2,
            'order_index' => $request->order_index ?? 0,
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : true,
        ]);

        return redirect()->back()->with('success', 'Banner created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:1000',
            'button_text_1' => 'nullable|string|max:50',
            'button_url_1' => 'nullable|string|max:255',
            'button_text_2' => 'nullable|string|max:50',
            'button_url_2' => 'nullable|string|max:255',
            'order_index' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data = [
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'button_text_1' => $request->button_text_1,
            'button_url_1' => $request->button_url_1,
            'button_text_2' => $request->button_text_2,
            'button_url_2' => $request->button_url_2,
            'order_index' => $request->order_index ?? 0,
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : false,
        ];

        if ($request->hasFile('image')) {
            // Delete old image
            if ($banner->image_path) {
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

        return redirect()->back()->with('success', 'Banner updated successfully.');
    }

    public function destroy(string $id)
    {
        $banner = Banner::findOrFail($id);

        // Delete image file
        if ($banner->image_path) {
            $filePath = public_path($banner->image_path);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        $banner->delete();

        return redirect()->back()->with('success', 'Banner deleted successfully.');
    }

    public function toggleActive(string $id)
    {
        $banner = Banner::findOrFail($id);
        $banner->is_active = !$banner->is_active;
        $banner->save();

        return redirect()->back()->with('success', 'Banner visibility updated.');
    }

    public function approve(string $id)
    {
        $banner = Banner::findOrFail($id);
        $banner->update([
            'status' => 'approved',
            'is_active' => true,
            'rejection_reason' => null
        ]);

        if ($banner->userId) {
            \App\Models\Notification::create([
                'userId' => $banner->userId,
                'title' => 'Hero Banner Approved',
                'message' => 'Your requested hero banner "' . ($banner->title ?: 'Untitled') . '" has been approved and is now live on the homepage!',
                'targetRole' => 'seller',
                'isRead' => false,
                'link' => '/seller/banners',
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
            'status' => 'rejected',
            'is_active' => false,
            'rejection_reason' => $request->rejection_reason,
        ]);

        if ($banner->userId) {
            \App\Models\Notification::create([
                'userId' => $banner->userId,
                'title' => 'Hero Banner Rejected',
                'message' => 'Your requested hero banner "' . ($banner->title ?: 'Untitled') . '" was rejected. Reason: ' . $request->rejection_reason,
                'targetRole' => 'seller',
                'isRead' => false,
                'link' => '/seller/banners',
            ]);
        }

        return redirect()->back()->with('success', 'Banner request rejected.');
    }
}

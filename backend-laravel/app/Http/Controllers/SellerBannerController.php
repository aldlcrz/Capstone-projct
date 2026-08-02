<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class SellerBannerController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isPremium = $user->isPremiumActive();
        
        $banners = collect();
        if ($isPremium) {
            $banners = Banner::where('userId', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }
        
        return view('seller.banners.index', compact('banners', 'isPremium'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isPremiumActive()) {
            return redirect()->back()->with('error', 'This feature is only available for Premium Sellers.');
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:1000',
            'order_index' => 'nullable|integer|min:0',
            'button_text_1' => 'nullable|string|max:50',
            'button_url_1' => 'nullable|string|max:255',
            'button_text_2' => 'nullable|string|max:50',
            'button_url_2' => 'nullable|string|max:255',
        ]);

        $imagePath = '';
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            $destinationPath = public_path('uploads/banners');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }
            
            $file->move($destinationPath, $filename);
            $imagePath = 'uploads/banners/' . $filename;
        }

        Banner::create([
            'userId' => $user->id,
            'image_path' => $imagePath,
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'button_text_1' => $request->button_text_1,
            'button_url_1' => $request->button_url_1,
            'button_text_2' => $request->button_text_2,
            'button_url_2' => $request->button_url_2,
            'order_index' => $request->filled('order_index') ? (int)$request->order_index : 99,
            'is_active' => false,
            'status' => 'pending',
        ]);

        // Notify admins about the new hero banner request
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'userId' => $admin->id,
                'title' => 'New Hero Banner Request',
                'message' => 'Seller ' . ($user->shopName ?: $user->name) . ' has submitted a hero banner request for review.',
                'targetRole' => 'admin',
                'isRead' => false,
                'link' => '/admin/banners',
            ]);
        }

        return redirect()->route('seller.banners.index')->with('success', 'Your hero banner request has been submitted successfully and is now under review by Admin.');
    }

    public function destroy(string $id)
    {
        $banner = Banner::where('id', $id)->where('userId', Auth::id())->firstOrFail();

        // Delete the physical file
        if ($banner->image_path) {
            $filePath = public_path($banner->image_path);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        $banner->delete();

        return redirect()->route('seller.banners.index')->with('success', 'Hero banner request deleted.');
    }
}

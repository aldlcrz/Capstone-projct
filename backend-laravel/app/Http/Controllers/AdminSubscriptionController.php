<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SellerSubscription;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AdminSubscriptionController extends Controller
{
    public function index()
    {
        $pending = SellerSubscription::with('user')
            ->where('status', 'pending')
            ->orderBy('createdAt', 'desc')
            ->get();

        $history = SellerSubscription::with('user')
            ->where('status', '!=', 'pending')
            ->orderBy('createdAt', 'desc')
            ->paginate(15);

        $admin = User::where('role', 'admin')->first();

        return view('admin.subscriptions.index', compact('pending', 'history', 'admin'));
    }

    public function approve(string $id)
    {
        $subscription = SellerSubscription::findOrFail($id);

        if ($subscription->status !== 'pending') {
            return redirect()->back()->with('error', 'This subscription request is no longer pending.');
        }

        $startsAt = Carbon::now();
        $endsAt = Carbon::now()->addDays(30);

        $subscription->update([
            'status' => 'active',
            'startsAt' => $startsAt,
            'endsAt' => $endsAt,
        ]);

        $user = User::findOrFail($subscription->userId);
        $user->update([
            'isPremium' => true,
            'premiumEndsAt' => $endsAt,
        ]);

        Notification::create([
            'userId' => $user->id,
            'title' => 'Premium Subscription Approved',
            'message' => 'Congratulations! Your Premium subscription has been approved. Enjoy premium perks until ' . $endsAt->format('F d, Y') . '.',
            'targetRole' => 'seller',
            'isRead' => false,
        ]);

        return redirect()->route('admin.subscriptions.index')->with('success', 'Premium subscription approved successfully.');
    }

    public function reject(Request $request, string $id)
    {
        $request->validate([
            'rejectionReason' => 'required|string|max:500',
        ]);

        $subscription = SellerSubscription::findOrFail($id);

        if ($subscription->status !== 'pending') {
            return redirect()->back()->with('error', 'This subscription request is no longer pending.');
        }

        $subscription->update([
            'status' => 'rejected',
            'rejectionReason' => $request->rejectionReason,
        ]);

        $user = User::findOrFail($subscription->userId);

        Notification::create([
            'userId' => $user->id,
            'title' => 'Premium Subscription Rejected',
            'message' => 'Your Premium subscription request was rejected. Reason: ' . $request->rejectionReason,
            'targetRole' => 'seller',
            'isRead' => false,
        ]);

        return redirect()->route('admin.subscriptions.index')->with('success', 'Premium subscription request rejected.');
    }

    public function updateSettings(Request $request)
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            return redirect()->back()->with('error', 'No administrator account found.');
        }

        $request->validate([
            'gcashNumber' => 'nullable|string|max:50',
            'mayaNumber' => 'nullable|string|max:50',
            'gcashQrCode' => 'nullable|image|max:4096',
            'mayaQrCode' => 'nullable|image|max:4096',
        ]);

        $admin->isGcashAvailable = $request->has('isGcashAvailable');
        $admin->isMayaAvailable = $request->has('isMayaAvailable');
        $admin->gcashNumber = $request->gcashNumber;
        $admin->mayaNumber = $request->mayaNumber;

        if ($request->hasFile('gcashQrCode')) {
            if ($admin->gcashQrCode) {
                Storage::disk('public')->delete($admin->gcashQrCode);
            }
            $admin->gcashQrCode = $request->file('gcashQrCode')->store('qrcodes', 'public');
        }

        if ($request->hasFile('mayaQrCode')) {
            if ($admin->mayaQrCode) {
                Storage::disk('public')->delete($admin->mayaQrCode);
            }
            $admin->mayaQrCode = $request->file('mayaQrCode')->store('qrcodes', 'public');
        }

        $admin->save();

        return redirect()->route('admin.subscriptions.index')->with('success', 'Payment account settings updated successfully.');
    }
}

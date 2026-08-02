<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SellerSubscription;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SellerSubscriptionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Ensure any expired subscription is self-healed
        $user->isPremiumActive();

        // Get the latest subscription record
        $latestSubscription = SellerSubscription::where('userId', $user->id)
            ->orderBy('createdAt', 'desc')
            ->first();

        // Get the admin user to show payment details (GCash/Maya number and QR code)
        $admin = User::where('role', 'admin')->first();

        return view('seller.subscription.index', compact('user', 'latestSubscription', 'admin'));
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'paymentMethod' => 'required|string|in:GCash,Maya',
            'paymentReference' => 'required|string|max:100',
            'paymentProof' => 'required|image|max:4096', // Max 4MB proof image
        ]);

        $user = Auth::user();

        // If user already has a pending subscription, block them from spamming
        $pending = SellerSubscription::where('userId', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return redirect()->back()->with('error', 'You already have a pending subscription request under review.');
        }

        $proofPath = null;
        if ($request->hasFile('paymentProof')) {
            $proofPath = $request->file('paymentProof')->store('proofs', 'public');
        }

        SellerSubscription::create([
            'userId' => $user->id,
            'status' => 'pending',
            'planName' => 'Premium Tier',
            'amount' => 299.00, // Proposed subscription cost
            'paymentMethod' => $request->paymentMethod,
            'paymentReference' => $request->paymentReference,
            'paymentProof' => $proofPath,
        ]);

        // Notify admins about a new subscription request
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'userId' => $admin->id,
                'title' => 'New Premium Subscription Request',
                'message' => 'Seller ' . ($user->shopName ?: $user->name) . ' has submitted a premium subscription payment for review.',
                'targetRole' => 'admin',
                'isRead' => false,
            ]);
        }

        return redirect()->route('seller.subscription.index')->with('success', 'Your subscription request has been submitted successfully and is now under review by Admin.');
    }
}

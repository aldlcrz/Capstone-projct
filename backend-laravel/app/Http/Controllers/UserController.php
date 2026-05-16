<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Get user profile.
     */
    public function getProfile(Request $request)
    {
        $user = User::with(['addresses' => function ($q) {
            $q->where('isDefault', true);
        }])->find($request->user()->id);

        $userData = $user->toArray();
        if ($user->addresses->isNotEmpty()) {
            $defaultAddr = $user->addresses->first();
            $userData['defaultCity'] = $defaultAddr->city;
            $userData['defaultProvince'] = $defaultAddr->province;
            $userData['defaultPostalCode'] = $defaultAddr->postalCode;
            $userData['defaultAddress'] = $defaultAddr;
            
            if (!$user->mobileNumber && $defaultAddr->phone) {
                $userData['mobileNumber'] = $defaultAddr->phone;
            }
        }

        return response()->json(['user' => $userData]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $user = User::find($request->user()->id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'mobileNumber' => 'sometimes|nullable|string',
            'username' => 'sometimes|string|unique:users,username,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
        }

        $user->update($request->all());

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()
        ]);
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currentPassword' => 'required',
            'newPassword' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
        }

        $user = User::find($request->user()->id);

        if (!Hash::check($request->currentPassword, $user->password)) {
            return response()->json(['message' => 'Incorrect current password'], 400);
        }

        $user->password = Hash::make($request->newPassword);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }

    /**
     * Get seller info for public view.
     */
    public function getSellerInfo($id)
    {
        $seller = User::find($id);
        
        if (!$seller || $seller->role !== 'seller') {
            return response()->json(['message' => 'Seller not found'], 404);
        }

        $productCount = Product::where('sellerId', $id)->count();
        $avgRating = Review::join('products', 'reviews.productId', '=', 'products.id')
            ->where('products.sellerId', $id)
            ->avg('rating') ?: 0;
        
        $reviewCount = Review::join('products', 'reviews.productId', '=', 'products.id')
            ->where('products.sellerId', $id)
            ->count();

        $joined = $seller->createdAt ? $seller->createdAt->diffForHumans() : "Unknown";

        return response()->json([
            'id' => $seller->id,
            'shopName' => $seller->name,
            'location' => $seller->shopCity ? "{$seller->shopCity}, {$seller->shopProvince}" : "Lumban, Laguna",
            'rating' => number_format($avgRating, 1),
            'reviewCount' => $reviewCount,
            'productCount' => $productCount,
            'joined' => $joined,
            'isVerified' => (bool)$seller->isVerified,
            'profilePhoto' => $seller->profileImage,
        ]);
    }

    /**
     * Toggle follow status.
     */
    public function toggleFollow(Request $request, $id)
    {
        $customerId = $request->user()->id;
        if ($id === $customerId) {
            return response()->json(['message' => 'You cannot follow yourself'], 400);
        }

        $seller = User::find($id);
        $customer = User::find($customerId);

        if (!$seller || $seller->role !== 'seller') {
            return response()->json(['message' => 'Seller not found'], 404);
        }

        $followers = $seller->followers ?? [];
        $following = $customer->following ?? [];

        $isFollowing = in_array($customerId, $followers);

        if ($isFollowing) {
            $followers = array_values(array_filter($followers, fn($uid) => $uid !== $customerId));
            $following = array_values(array_filter($following, fn($uid) => $uid !== $id));
        } else {
            $followers[] = $customerId;
            $following[] = $id;
        }

        $seller->followers = $followers;
        $customer->following = $following;

        $seller->save();
        $customer->save();

        return response()->json(['isFollowing' => !$isFollowing]);
    }

    /**
     * Get customer stats.
     */
    public function getCustomerStats(Request $request)
    {
        $activeOrders = Order::where('customerId', $request->user()->id)
            ->whereNotIn('status', ['Cancelled', 'Completed', 'Delivered'])
            ->count();

        return response()->json(['activeOrders' => $activeOrders]);
    }
}

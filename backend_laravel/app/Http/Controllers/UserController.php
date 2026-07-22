<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\ArtisanBadge;
use App\Utils\SocketUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    private function toPublicImageUrl(mixed $url)
    {
        if (!$url) return null;
        if (!is_string($url)) return null;
        $url = trim($url);
        if (!$url) return null;
        if (preg_match('/^https?:\/\//i', $url) || str_starts_with($url, 'data:') || str_starts_with($url, 'blob:') || str_starts_with($url, '/images/')) {
            return $url;
        }
        $normalized = str_replace('\\', '/', $url);
        $normalized = ltrim($normalized, './');
        if (str_starts_with($normalized, 'uploads/')) {
            return url($normalized);
        }
        return str_starts_with($normalized, '/') ? $normalized : '/' . $normalized;
    }

    public function getProfile()
    {
        try {
            $user = User::with(['addresses' => function ($q) {
                $q->where('isDefault', true);
            }])->find(Auth::id());

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $userData = $user->toArray();
            if (isset($userData['addresses']) && count($userData['addresses']) > 0) {
                $defaultAddr = $userData['addresses'][0];
                $userData['defaultCity'] = $defaultAddr['city'];
                $userData['defaultProvince'] = $defaultAddr['province'];
                $userData['defaultPostalCode'] = $defaultAddr['postalCode'];
                $userData['defaultAddress'] = $defaultAddr;

                if (empty($userData['mobileNumber']) && isset($defaultAddr['phone'])) {
                    $userData['mobileNumber'] = $defaultAddr['phone'];
                }
            }

            return response()->json(['user' => $userData]);
        } catch (\Exception $err) {
            return response()->json(['message' => 'Error fetching profile', 'error' => $err->getMessage()], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $inputs = $request->only([
                'name', 'mobileNumber', 'gcashNumber', 'gcashQrCode', 'mayaNumber', 'mayaQrCode', 'profilePhoto',
                'facebookLink', 'instagramLink', 'tiktokLink', 'youtubeLink', 'socialLinks',
                'shopHouseNo', 'shopStreet', 'shopAddress', 'shopBarangay', 'shopCity', 'shopProvince', 'shopPostalCode', 'shopLatitude', 'shopLongitude',
                'username', 'gender', 'birthday'
            ]);

            // Filter out null inputs if they are not explicitly sent or validate them
            $updateData = [];
            foreach ($inputs as $key => $val) {
                if ($val !== null) {
                    $updateData[$key] = $val;
                }
            }

            $user->update($updateData);

            $updatedUser = User::find($user->id);
            return response()->json(['message' => 'Profile updated successfully', 'user' => $updatedUser]);

        } catch (\Exception $err) {
            return response()->json(['message' => $err->getMessage()], 500);
        }
    }

    public function getAddresses()
    {
        try {
            $addresses = Address::where('userId', Auth::id())
                ->orderBy('isDefault', 'DESC')
                ->orderBy('createdAt', 'DESC')
                ->get();
            return response()->json($addresses);
        } catch (\Exception $err) {
            return response()->json(['message' => 'Error fetching addresses', 'error' => $err->getMessage()], 500);
        }
    }

    public function createAddress(Request $request)
    {
        try {
            $userId = Auth::id();
            $isDefault = $request->input('isDefault', false);
            $phone = $request->input('phone');

            if ($isDefault) {
                Address::where('userId', $userId)->update(['isDefault' => false]);
                User::where('id', $userId)->update(['mobileNumber' => $phone]);
            }

            $address = Address::create([
                'userId' => $userId,
                'recipientName' => $request->input('recipientName'),
                'phone' => $phone,
                'houseNo' => $request->input('houseNo'),
                'street' => $request->input('street'),
                'barangay' => $request->input('barangay'),
                'city' => $request->input('city'),
                'province' => $request->input('province'),
                'postalCode' => $request->input('postalCode'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'isDefault' => $isDefault
            ]);

            SocketUtility::emitDashboardUpdate();
            return response()->json($address);
        } catch (\Exception $err) {
            return response()->json(['message' => 'Error creating address', 'error' => $err->getMessage()], 500);
        }
    }

    public function updateAddress(Request $request, string $id)
    {
        try {
            $userId = Auth::id();
            $address = Address::where('id', $id)->where('userId', $userId)->first();
            if (!$address) {
                return response()->json(['message' => 'Address not found'], 404);
            }

            $isDefault = $request->input('isDefault', false);
            $phone = $request->input('phone') ?? $address->phone;

            if ($isDefault) {
                Address::where('userId', $userId)->update(['isDefault' => false]);
                User::where('id', $userId)->update(['mobileNumber' => $phone]);
            }

            $address->update([
                'recipientName' => $request->input('recipientName') ?? $address->recipientName,
                'phone' => $phone,
                'houseNo' => $request->input('houseNo') ?? $address->houseNo,
                'street' => $request->input('street') ?? $address->street,
                'barangay' => $request->input('barangay') ?? $address->barangay,
                'city' => $request->input('city') ?? $address->city,
                'province' => $request->input('province') ?? $address->province,
                'postalCode' => $request->input('postalCode') ?? $address->postalCode,
                'latitude' => $request->input('latitude') ?? $address->latitude,
                'longitude' => $request->input('longitude') ?? $address->longitude,
                'isDefault' => $isDefault
            ]);

            SocketUtility::emitDashboardUpdate();
            return response()->json($address);
        } catch (\Exception $err) {
            return response()->json(['message' => 'Error updating address', 'error' => $err->getMessage()], 500);
        }
    }

    public function setDefaultAddress(string $id)
    {
        try {
            $userId = Auth::id();
            Address::where('userId', $userId)->update(['isDefault' => false]);
            $address = Address::where('id', $id)->where('userId', $userId)->first();
            if (!$address) {
                return response()->json(['message' => 'Address not found'], 404);
            }

            $address->update(['isDefault' => true]);
            User::where('id', $userId)->update(['mobileNumber' => $address->phone]);

            return response()->json(['message' => 'Default address updated']);
        } catch (\Exception $err) {
            return response()->json(['message' => 'Error setting default address', 'error' => $err->getMessage()], 500);
        }
    }

    public function deleteAddress(string $id)
    {
        try {
            Address::where('id', $id)->where('userId', Auth::id())->delete();
            SocketUtility::emitDashboardUpdate();
            return response()->json(['message' => 'Address removed successfully']);
        } catch (\Exception $err) {
            return response()->json(['message' => 'Error deleting address', 'error' => $err->getMessage()], 500);
        }
    }

    public function updateFcmToken(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
            $user->update(['fcmToken' => $request->input('fcmToken')]);
            return response()->json(['message' => 'Push token updated successfully']);
        } catch (\Exception $err) {
            return response()->json(['message' => 'Error updating push token', 'error' => $err->getMessage()], 500);
        }
    }

    public function getCustomerStats()
    {
        try {
            $activeOrders = Order::where('customerId', Auth::id())
                ->whereNotIn('status', ['Cancelled', 'Completed', 'Delivered'])
                ->count();
            return response()->json(['activeOrders' => $activeOrders]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $currentPassword = $request->input('currentPassword');
            $newPassword = $request->input('newPassword');

            if (!$currentPassword || !$newPassword) {
                return response()->json(['message' => 'Current password and new password are required'], 400);
            }

            if (strlen($newPassword) < 8) {
                return response()->json(['message' => 'New password must be at least 8 characters long'], 400);
            }

            $user = User::find(Auth::id());
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            if (!Hash::check($currentPassword, $user->password)) {
                return response()->json(['message' => 'Incorrect current password'], 400);
            }

            $user->update([
                'password' => Hash::make($newPassword),
                'passwordChangedAt' => now()
            ]);

            return response()->json(['message' => 'Password changed successfully']);
        } catch (\Exception $err) {
            return response()->json(['message' => 'Error changing password', 'error' => $err->getMessage()], 500);
        }
    }

    public function getSellerInfo(string $id)
    {
        try {
            $seller = User::find($id);
            if (!$seller || $seller->role !== 'seller') {
                return response()->json(['message' => 'Seller not found or user is not a seller'], 404);
            }

            $productCount = Product::where('sellerId', $id)->count();

            // Calculate Average Rating & Reviews
            $reviewData = Review::whereHas('product', function ($q) use ($id) {
                $q->where('sellerId', $id);
            })
            ->selectRaw('AVG(rating) as avgRating, COUNT(id) as reviewCount')
            ->first();

            $avgRating = number_format((float) ($reviewData->avgRating ?? 0), 1);
            $reviewCount = (int) ($reviewData->reviewCount ?? 0);

            $monthsJoined = $seller->createdAt 
                ? (int) now()->diffInMonths($seller->createdAt)
                : 0;

            $establishedDate = $seller->createdAt 
                ? $seller->createdAt->format('F Y')
                : "March 2026";

            $badges = ArtisanBadge::where('seller_id', $id)->get()->map(function($b) {
                return [
                    'badge_type' => $b->badge_type,
                    'issued_at'  => $b->issued_at,
                ];
            });

            return response()->json([
                'id' => $seller->id,
                'shopName' => $seller->name ?: "Lumban Artisan",
                'location' => $seller->shopCity ? "{$seller->shopCity}, {$seller->shopProvince}" : "Lumban, Laguna",
                'rating' => $avgRating,
                'reviewCount' => $reviewCount,
                'productCount' => $productCount,
                'joined' => ($monthsJoined < 1 ? "Just Joined" : "{$monthsJoined} Months Ago"),
                'establishedOn' => $establishedDate,
                'indigencyStatus' => $seller->indigencyCertificate ? "Active Support Level" : "Basic Artisan",
                'responseRate' => "98%",
                'isVerified' => (bool) $seller->isVerified,
                'profilePhoto' => $this->toPublicImageUrl($seller->profilePhoto),
                'facebookLink' => $seller->facebookLink,
                'instagramLink' => $seller->instagramLink,
                'tiktokLink' => $seller->tiktokLink,
                'youtubeLink' => $seller->youtubeLink,
                'socialLinks' => $seller->socialLinks ?: [],
                'shopLatitude' => $seller->shopLatitude,
                'shopLongitude' => $seller->shopLongitude,
                'artisanBadges' => $badges,
            ]);
        } catch (\Exception $err) {
            return response()->json(['message' => 'Error fetching seller info', 'error' => $err->getMessage()], 500);
        }
    }

    public function toggleFollow(string $id)
    {
        try {
            $customerId = Auth::id();
            if ($id === $customerId) {
                return response()->json(['message' => "You cannot follow yourself"], 400);
            }

            $seller = User::find($id);
            $customer = User::find($customerId);

            if (!$seller || $seller->role !== 'seller') {
                return response()->json(['message' => 'Seller not found'], 404);
            }

            $sellerFollowers = $seller->followers ?: [];
            $customerFollowing = $customer->following ?: [];

            $isFollowing = in_array($customerId, $sellerFollowers);

            if ($isFollowing) {
                $sellerFollowers = array_values(array_diff($sellerFollowers, [$customerId]));
                $customerFollowing = array_values(array_diff($customerFollowing, [$id]));
            } else {
                $sellerFollowers[] = $customerId;
                $customerFollowing[] = $id;
            }

            $seller->update(['followers' => $sellerFollowers]);
            $customer->update(['following' => $customerFollowing]);

            return response()->json(['isFollowing' => !$isFollowing]);
        } catch (\Exception $err) {
            return response()->json(['message' => 'Error toggling follow status', 'error' => $err->getMessage()], 500);
        }
    }
}

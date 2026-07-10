<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function getAddresses()
    {
        try {
            $userId = Auth::id();
            $addresses = Address::where('userId', $userId)
                ->orderBy('isDefault', 'DESC')
                ->orderBy('createdAt', 'DESC')
                ->get();

            return response()->json($addresses);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch addresses'], 500);
        }
    }

    public function createAddress(Request $request)
    {
        try {
            $userId = Auth::id();
            $isDefault = $request->input('isDefault', false);

            if ($isDefault) {
                Address::where('userId', $userId)->update(['isDefault' => false]);
            }

            $address = Address::create([
                'recipientName' => $request->input('recipientName'),
                'phone' => $request->input('phone'),
                'houseNo' => $request->input('houseNo'),
                'street' => $request->input('street'),
                'barangay' => $request->input('barangay'),
                'city' => $request->input('city'),
                'province' => $request->input('province'),
                'region' => $request->input('region'),
                'postalCode' => $request->input('postalCode'),
                'userId' => $userId,
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'isDefault' => $isDefault
            ]);

            return response()->json($address, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
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

            $isDefault = $request->input('isDefault');

            if ($isDefault && !$address->isDefault) {
                Address::where('userId', $userId)->update(['isDefault' => false]);
            }

            $address->update([
                'recipientName' => $request->input('recipientName') ?? $address->recipientName,
                'phone' => $request->input('phone') ?? $address->phone,
                'houseNo' => $request->input('houseNo') ?? $address->houseNo,
                'street' => $request->input('street') ?? $address->street,
                'barangay' => $request->input('barangay') ?? $address->barangay,
                'city' => $request->input('city') ?? $address->city,
                'province' => $request->input('province') ?? $address->province,
                'region' => $request->input('region') ?? $address->region,
                'postalCode' => $request->input('postalCode') ?? $address->postalCode,
                'latitude' => $request->input('latitude') ?? $address->latitude,
                'longitude' => $request->input('longitude') ?? $address->longitude,
                'isDefault' => $isDefault ?? $address->isDefault,
            ]);

            return response()->json($address);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteAddress(string $id)
    {
        try {
            $userId = Auth::id();
            $address = Address::where('id', $id)->where('userId', $userId)->first();

            if (!$address) {
                return response()->json(['message' => 'Address not found'], 404);
            }

            $address->delete();
            return response()->json(['message' => 'Address deleted']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete address'], 500);
        }
    }

    public function setDefaultAddress(string $id)
    {
        try {
            $userId = Auth::id();
            Address::where('userId', $userId)->update(['isDefault' => false]);
            Address::where('id', $id)->where('userId', $userId)->update(['isDefault' => true]);

            return response()->json(['message' => 'Default address updated']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update default address'], 500);
        }
    }
}

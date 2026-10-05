<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated user.
     */
    public function index()
    {
        $userId = Auth::id();

        // Purge any legacy dummy auto-generated addresses from previous onboarding bug
        try {
            Address::where('userId', $userId)
                ->where('phone', '09000000000')
                ->where(function($q) {
                    $q->whereIn('houseNo', ['National Highway', 'Unit', ''])
                      ->orWhere('street', 'National Highway')
                      ->orWhere('city', 'Lumban');
                })
                ->delete();
        } catch (\Throwable $e) {
            Log::warning('Failed to purge legacy dummy address for user', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
        }

        // Ensure user has at least one default address if addresses exist
        $totalCount = Address::where('userId', $userId)->count();
        if ($totalCount > 0) {
            $hasDefault = Address::where('userId', $userId)->where('isDefault', true)->exists();
            if (!$hasDefault) {
                // Elect the most recent address as default
                $firstAddr = Address::where('userId', $userId)->orderByDesc('createdAt')->first();
                if ($firstAddr) {
                    $firstAddr->update(['isDefault' => true]);
                }
            }
        }

        $addresses = Address::where('userId', $userId)
            ->orderBy('isDefault', 'desc')
            ->orderBy('createdAt', 'desc')
            ->get();
            
        return response()->json($addresses);
    }

    /**
     * Create a new address.
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipientName' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\.\,\'\-]+$/'],
            'phone' => ['required', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'houseNo' => 'required|string|max:255',
            'street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'postalCode' => ['nullable', 'string', 'regex:/^\d{4}$/'],
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'isDefault' => 'nullable',
        ], [
            'recipientName.regex' => 'Recipient name can only contain letters, spaces, hyphens, and periods (no numbers allowed).',
            'phone.regex' => 'Phone number must be a valid 11-digit mobile number starting with 09 (e.g., 09123456789).',
            'postalCode.regex' => 'Postal code must contain exactly 4 numeric digits (e.g., 4103).',
        ]);

        $userId = Auth::id();
        $isFirst = !Address::where('userId', $userId)->exists();
        $isDefault = $isFirst || $request->boolean('isDefault');

        $address = DB::transaction(function () use ($request, $userId, $isDefault) {
            if ($isDefault) {
                Address::where('userId', $userId)->update(['isDefault' => false]);
            }

            return Address::create([
                'userId' => $userId,
                'recipientName' => trim($request->recipientName),
                'phone' => trim($request->phone),
                'houseNo' => trim($request->houseNo),
                'street' => trim($request->street ?? ''),
                'barangay' => trim($request->barangay ?? ''),
                'city' => trim($request->city),
                'province' => trim($request->province),
                'region' => trim($request->region ?? ''),
                'postalCode' => trim($request->postalCode ?? ''),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'isDefault' => $isDefault,
            ]);
        });

        return response()->json($address, 201);
    }

    /**
     * Update an existing address.
     */
    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        $address = Address::where('id', $id)->where('userId', $userId)->firstOrFail();

        $validated = $request->validate([
            'recipientName' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\.\,\'\-]+$/'],
            'phone' => ['required', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'houseNo' => 'required|string|max:255',
            'street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'postalCode' => ['nullable', 'string', 'regex:/^\d{4}$/'],
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'isDefault' => 'nullable',
        ], [
            'recipientName.regex' => 'Recipient name can only contain letters, spaces, hyphens, and periods (no numbers allowed).',
            'phone.regex' => 'Phone number must be a valid 11-digit mobile number starting with 09 (e.g., 09123456789).',
            'postalCode.regex' => 'Postal code must contain exactly 4 numeric digits (e.g., 4103).',
        ]);

        $totalAddresses = Address::where('userId', $userId)->count();
        $isDefault = $request->has('isDefault') 
            ? $request->boolean('isDefault') 
            : (bool) $address->isDefault;

        // If user has only one address, it MUST stay as default
        if ($totalAddresses <= 1) {
            $isDefault = true;
        }

        DB::transaction(function () use ($address, $userId, $isDefault, $validated, $request) {
            if ($isDefault) {
                Address::where('userId', $userId)
                    ->where('id', '!=', $address->id)
                    ->update(['isDefault' => false]);
            } else if ($address->isDefault) {
                // If unchecking the default address, make another address default so there's always one
                $another = Address::where('userId', $userId)
                    ->where('id', '!=', $address->id)
                    ->orderByDesc('createdAt')
                    ->first();
                if ($another) {
                    $another->update(['isDefault' => true]);
                } else {
                    $isDefault = true; // Fallback if no other address exists
                }
            }

            $validated['street'] = trim($request->street ?? '');
            $validated['barangay'] = trim($request->barangay ?? '');
            $validated['region'] = trim($request->region ?? '');
            $validated['postalCode'] = trim($request->postalCode ?? '');
            $validated['latitude'] = $request->latitude;
            $validated['longitude'] = $request->longitude;
            $validated['isDefault'] = $isDefault;

            $address->update($validated);
        });

        return response()->json($address->fresh());
    }

    /**
     * Delete an address.
     */
    public function destroy(string $id)
    {
        $userId = Auth::id();
        $address = Address::where('id', $id)->where('userId', $userId)->firstOrFail();
        $wasDefault = (bool) $address->isDefault;

        DB::transaction(function () use ($address, $userId, $wasDefault) {
            $address->delete();

            // If the deleted address was default, promote another address to default
            if ($wasDefault) {
                $replacement = Address::where('userId', $userId)
                    ->orderByDesc('createdAt')
                    ->first();
                if ($replacement) {
                    $replacement->update(['isDefault' => true]);
                }
            }
        });

        return response()->json(['message' => 'Address deleted', 'success' => true]);
    }

    /**
     * Set an address as default.
     */
    public function setDefault(string $id)
    {
        $userId = Auth::id();
        $address = Address::where('id', $id)->where('userId', $userId)->firstOrFail();

        DB::transaction(function () use ($address, $userId) {
            Address::where('userId', $userId)->update(['isDefault' => false]);
            $address->update(['isDefault' => true]);
        });

        $allAddresses = Address::where('userId', $userId)
            ->orderBy('isDefault', 'desc')
            ->orderBy('createdAt', 'desc')
            ->get();

        return response()->json([
            'message'   => 'Default address updated',
            'success'   => true,
            'address'   => $address->fresh(),
            'addresses' => $allAddresses,
        ]);
    }
}

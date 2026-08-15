<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated user.
     */
    public function index()
    {
        $addresses = Address::where('userId', Auth::id())
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
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'postalCode' => ['nullable', 'string', 'regex:/^\d{4}$/'],
        ], [
            'recipientName.regex' => 'Recipient name can only contain letters, spaces, hyphens, and periods (no numbers allowed).',
            'phone.regex' => 'Phone number must be a valid 11-digit mobile number starting with 09 (e.g., 09123456789).',
            'postalCode.regex' => 'Postal code must contain exactly 4 numeric digits (e.g., 4103).',
        ]);

        if ($request->isDefault) {
            Address::where('userId', Auth::id())->update(['isDefault' => false]);
        }

        $address = Address::create([
            'userId' => Auth::id(),
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
            'isDefault' => $request->isDefault ?? false,
        ]);

        return response()->json($address, 201);
    }

    /**
     * Update an existing address.
     */
    public function update(Request $request, string $id)
    {
        $address = Address::where('id', $id)->where('userId', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'recipientName' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\.\,\'\-]+$/'],
            'phone' => ['required', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'houseNo' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'postalCode' => ['nullable', 'string', 'regex:/^\d{4}$/'],
        ], [
            'recipientName.regex' => 'Recipient name can only contain letters, spaces, hyphens, and periods (no numbers allowed).',
            'phone.regex' => 'Phone number must be a valid 11-digit mobile number starting with 09 (e.g., 09123456789).',
            'postalCode.regex' => 'Postal code must contain exactly 4 numeric digits (e.g., 4103).',
        ]);

        if ($request->isDefault && !$address->isDefault) {
            Address::where('userId', Auth::id())->update(['isDefault' => false]);
        }

        $address->update($validated);

        return response()->json($address);
    }

    /**
     * Delete an address.
     */
    public function destroy(string $id)
    {
        $address = Address::where('id', $id)->where('userId', Auth::id())->firstOrFail();
        $address->delete();

        return response()->json(['message' => 'Address deleted']);
    }

    /**
     * Set an address as default.
     */
    public function setDefault(string $id)
    {
        Address::where('userId', Auth::id())->update(['isDefault' => false]);
        Address::where('id', $id)->where('userId', Auth::id())->update(['isDefault' => true]);

        return response()->json(['message' => 'Default address updated']);
    }
}

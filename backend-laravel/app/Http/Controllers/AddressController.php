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
            'recipientName' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'houseNo' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'postalCode' => 'nullable|string|max:20',
        ]);

        if ($request->isDefault) {
            Address::where('userId', Auth::id())->update(['isDefault' => false]);
        }

        $address = Address::create([
            'userId' => Auth::id(),
            'recipientName' => $request->recipientName,
            'phone' => $request->phone,
            'houseNo' => $request->houseNo,
            'street' => $request->street,
            'barangay' => $request->barangay,
            'city' => $request->city,
            'province' => $request->province,
            'region' => $request->region,
            'postalCode' => $request->postalCode,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'isDefault' => $request->isDefault ?? false,
        ]);

        return response()->json($address, 201);
    }

    /**
     * Update an existing address.
     */
    public function update(Request $request, $id)
    {
        $address = Address::where('id', $id)->where('userId', Auth::id())->firstOrFail();

        if ($request->isDefault && !$address->isDefault) {
            Address::where('userId', Auth::id())->update(['isDefault' => false]);
        }

        $address->update($request->all());

        return response()->json($address);
    }

    /**
     * Delete an address.
     */
    public function destroy($id)
    {
        $address = Address::where('id', $id)->where('userId', Auth::id())->firstOrFail();
        $address->delete();

        return response()->json(['message' => 'Address deleted']);
    }

    /**
     * Set an address as default.
     */
    public function setDefault($id)
    {
        Address::where('userId', Auth::id())->update(['isDefault' => false]);
        Address::where('id', $id)->where('userId', Auth::id())->update(['isDefault' => true]);

        return response()->json(['message' => 'Default address updated']);
    }
}

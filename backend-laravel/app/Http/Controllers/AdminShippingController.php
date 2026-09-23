<?php

namespace App\Http\Controllers;

use App\Models\ShippingProvider;
use App\Models\ShippingZone;
use App\Models\ShippingZoneArea;
use App\Models\ShippingRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminShippingController extends Controller
{
    public function index()
    {
        $providers = ShippingProvider::withCount('rates')->orderBy('name')->get();
        $zones = ShippingZone::withCount('areas')->orderBy('code')->get();
        $zoneAreas = ShippingZoneArea::with('zone')->orderBy('province')->orderBy('city')->paginate(20, ['*'], 'areas_page');
        $rates = ShippingRate::with(['provider', 'originZone', 'destinationZone'])
            ->orderBy('provider_id')
            ->orderBy('origin_zone_id')
            ->orderBy('destination_zone_id')
            ->orderBy('min_weight')
            ->paginate(25, ['*'], 'rates_page');

        return view('admin.shipping.index', compact('providers', 'zones', 'zoneAreas', 'rates'));
    }

    public function toggleProvider($id)
    {
        $provider = ShippingProvider::findOrFail($id);
        $provider->is_active = !$provider->is_active;
        $provider->save();

        return redirect()->route('admin.shipping.index')->with('success', "Logistics provider {$provider->name} status updated.");
    }

    public function updateProvider(Request $request, $id)
    {
        $provider = ShippingProvider::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'volumetric_divisor' => 'required|integer|min:1',
            'tracking_url_template' => 'nullable|string|max:255',
            'is_active' => 'required|boolean'
        ]);

        $provider->update($validated);

        return redirect()->route('admin.shipping.index')->with('success', "Provider {$provider->name} updated successfully.");
    }

    public function storeRate(Request $request)
    {
        $validated = $request->validate([
            'provider_id' => 'required|string|exists:shipping_providers,id',
            'origin_zone_id' => 'required|integer|exists:shipping_zones,id',
            'destination_zone_id' => 'required|integer|exists:shipping_zones,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'nullable|numeric|gt:min_weight',
            'base_rate' => 'required|numeric|min:0',
            'additional_weight_rate' => 'required|numeric|min:0',
            'estimated_days_min' => 'required|integer|min:1',
            'estimated_days_max' => 'required|integer|gte:estimated_days_min',
            'is_active' => 'required|boolean'
        ]);

        ShippingRate::create($validated);

        return redirect()->route('admin.shipping.index')->with('success', 'Shipping rate bracket created successfully.');
    }

    public function updateRate(Request $request, $id)
    {
        $rate = ShippingRate::findOrFail($id);
        $validated = $request->validate([
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'nullable|numeric|gt:min_weight',
            'base_rate' => 'required|numeric|min:0',
            'additional_weight_rate' => 'required|numeric|min:0',
            'estimated_days_min' => 'required|integer|min:1',
            'estimated_days_max' => 'required|integer|gte:estimated_days_min',
            'is_active' => 'required|boolean'
        ]);

        $rate->update($validated);

        return redirect()->route('admin.shipping.index')->with('success', 'Shipping rate updated successfully.');
    }

    public function destroyRate($id)
    {
        $rate = ShippingRate::findOrFail($id);
        $rate->delete();

        return redirect()->route('admin.shipping.index')->with('success', 'Rate bracket deleted successfully.');
    }

    public function storeArea(Request $request)
    {
        $validated = $request->validate([
            'zone_id' => 'required|integer|exists:shipping_zones,id',
            'province' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'postal_code_prefix' => 'nullable|string|max:10',
        ]);

        ShippingZoneArea::create($validated);

        return redirect()->route('admin.shipping.index')->with('success', 'Geographic area mapping added successfully.');
    }

    public function destroyArea($id)
    {
        $area = ShippingZoneArea::findOrFail($id);
        $area->delete();

        return redirect()->route('admin.shipping.index')->with('success', 'Geographic area mapping removed.');
    }
}

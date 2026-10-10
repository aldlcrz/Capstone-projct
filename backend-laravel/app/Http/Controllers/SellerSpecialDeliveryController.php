<?php

namespace App\Http\Controllers;

use App\Models\SellerShippingProvider;
use App\Models\SellerSpecialDeliveryRate;
use App\Models\ShippingProvider;
use App\Services\Shipping\LagunaMunicipalityCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SellerSpecialDeliveryController extends Controller
{
    /**
     * Display the Special Delivery Settings management page.
     */
    public function index(Request $request)
    {
        $seller = $request->user();

        // Ensure seller role
        if ($seller->role !== 'seller') {
            abort(403, 'Unauthorized access.');
        }

        $sdProvider = ShippingProvider::where('code', 'seller_direct')->first();
        if (!$sdProvider) {
            $sdProvider = ShippingProvider::create([
                'id'                  => (string) Str::uuid(),
                'name'                => 'Special Delivery (Local Artisan Rider)',
                'code'                => 'seller_direct',
                'description'         => 'Direct courier delivery within selected Laguna municipalities using local artisan rider.',
                'tracking_url_format' => null,
                'is_active'           => true,
            ]);
        }

        $sellerProvider = SellerShippingProvider::where('seller_id', $seller->id)
            ->where('provider_id', $sdProvider->id)
            ->first();

        $isEnabled = $sellerProvider ? (bool) $sellerProvider->is_enabled : false;
        $baseFee   = ($sellerProvider && $sellerProvider->custom_fee !== null) ? (float) $sellerProvider->custom_fee : 50.00;

        // Fetch saved municipality rates
        $savedRates = SellerSpecialDeliveryRate::where('seller_id', $seller->id)
            ->where('is_enabled', true)
            ->get()
            ->keyBy('municipality_key');

        // Prepare full municipality list
        $allMunicipalities = LagunaMunicipalityCatalog::all();
        $municipalitiesData = [];

        foreach ($allMunicipalities as $key => $data) {
            $isCovered = $savedRates->has($key);
            $surcharge = $isCovered ? (float) $savedRates[$key]->surcharge : 0.00;

            $municipalitiesData[$key] = [
                'key'         => $key,
                'name'        => $data['name'],
                'postal_code' => $data['postal_code'],
                'selected'    => $isCovered,
                'surcharge'   => $surcharge,
                'final_fee'   => round($baseFee + $surcharge, 2),
            ];
        }

        return view('seller.special-delivery.index', compact(
            'seller',
            'isEnabled',
            'baseFee',
            'municipalitiesData'
        ));
    }

    /**
     * Update the Special Delivery settings and Laguna municipality coverage.
     */
    public function update(Request $request)
    {
        $seller = $request->user();

        if ($seller->role !== 'seller') {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'special_delivery_enabled' => 'required',
            'base_delivery_fee'        => 'required|numeric|min:0|max:10000',
            'municipalities'           => 'nullable|array',
            'municipalities.*'         => 'string',
            'surcharges'               => 'nullable|array',
            'surcharges.*'             => 'nullable|numeric|min:0|max:10000',
        ]);

        $isEnabled = filter_var($validated['special_delivery_enabled'], FILTER_VALIDATE_BOOLEAN);
        $baseFee   = round((float) $validated['base_delivery_fee'], 2);

        $sdProvider = ShippingProvider::where('code', 'seller_direct')->first();
        if (!$sdProvider) {
            $sdProvider = ShippingProvider::create([
                'id'                  => (string) Str::uuid(),
                'name'                => 'Special Delivery (Local Artisan Rider)',
                'code'                => 'seller_direct',
                'description'         => 'Direct courier delivery within selected Laguna municipalities using local artisan rider.',
                'tracking_url_format' => null,
                'is_active'           => true,
            ]);
        }

        DB::transaction(function () use ($seller, $sdProvider, $isEnabled, $baseFee, $request) {
            // 1. Update or create SellerShippingProvider configuration
            SellerShippingProvider::updateOrCreate(
                ['seller_id' => $seller->id, 'provider_id' => $sdProvider->id],
                [
                    'is_enabled' => $isEnabled,
                    'custom_fee' => $baseFee,
                ]
            );

            // 2. Process Laguna Municipalities Selection
            $selectedKeys = (array) $request->input('municipalities', []);
            $validSelectedKeys = [];

            foreach ($selectedKeys as $rawKey) {
                $canonicalKey = LagunaMunicipalityCatalog::resolveKey($rawKey);
                if ($canonicalKey && LagunaMunicipalityCatalog::isValidKey($canonicalKey)) {
                    $validSelectedKeys[] = $canonicalKey;
                }
            }
            $validSelectedKeys = array_unique($validSelectedKeys);

            // Delete removed municipalities
            SellerSpecialDeliveryRate::where('seller_id', $seller->id)
                ->whereNotIn('municipality_key', $validSelectedKeys)
                ->delete();

            // Insert or update covered municipalities
            $rawSurcharges = (array) $request->input('surcharges', []);

            foreach ($validSelectedKeys as $key) {
                $surcharge = isset($rawSurcharges[$key]) ? round((float) $rawSurcharges[$key], 2) : 0.00;
                if ($surcharge < 0) {
                    $surcharge = 0.00;
                }

                SellerSpecialDeliveryRate::updateOrCreate(
                    ['seller_id' => $seller->id, 'municipality_key' => $key],
                    [
                        'municipality_name' => LagunaMunicipalityCatalog::getName($key),
                        'surcharge'         => $surcharge,
                        'is_enabled'        => true,
                    ]
                );
            }
        });

        return redirect()->route('seller.special-delivery.index')
            ->with('success', 'Special Delivery settings and Laguna coverage updated successfully.');
    }
}

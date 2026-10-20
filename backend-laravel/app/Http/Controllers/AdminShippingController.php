<?php

namespace App\Http\Controllers;

use App\Models\ShippingProvider;
use App\Models\ShippingZone;
use App\Models\ShippingZoneArea;
use App\Models\ShippingRate;
use App\Services\CourierTrackingService;
use App\Services\ShippingZoneResolverService;
use App\Services\Courier\Contracts\CourierRateProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminShippingController extends Controller
{
    public function index(Request $request)
    {
        $providers = ShippingProvider::withCount('rates')
            ->whereNotIn('code', ['store_pickup', 'seller_direct'])
            ->orderBy('name')
            ->get();

        $zones = ShippingZone::withCount('areas')->orderBy('code')->get();
        $zoneAreas = ShippingZoneArea::with('zone')->orderBy('province')->orderBy('city')->paginate(20, ['*'], 'areas_page');

        $ratesQuery = ShippingRate::with(['provider', 'originZone', 'destinationZone']);

        if ($request->filled('filter_provider_id')) {
            $ratesQuery->where('provider_id', $request->filter_provider_id);
        }
        if ($request->filled('filter_zone_id')) {
            $ratesQuery->where(function ($q) use ($request) {
                $q->where('origin_zone_id', $request->filter_zone_id)
                  ->orWhere('destination_zone_id', $request->filter_zone_id);
            });
        }

        $rates = $ratesQuery
            ->orderBy('provider_id')
            ->orderBy('origin_zone_id')
            ->orderBy('destination_zone_id')
            ->orderBy('min_weight')
            ->paginate(25, ['*'], 'rates_page')
            ->withQueryString();

        // Courier Live API Statuses
        $courierApiStatus = [
            'jnt' => [
                'has_credentials'    => !empty(config('services.jtexpress.api_key')) && !empty(config('services.jtexpress.customer_code')),
                'api_url'            => config('services.jtexpress.api_url', 'https://openapi.jtexpress.ph'),
                'customer_code_mask' => config('services.jtexpress.customer_code') ? substr(config('services.jtexpress.customer_code'), 0, 4) . '****' : null,
                'rate_endpoint'      => '/web/freight/inquiry',
                'tracking_endpoint'  => '/web/logistics/trace',
            ],
            'flash' => [
                'has_credentials'    => !empty(config('services.flashexpress.mch_id')) && !empty(config('services.flashexpress.sign_key')),
                'api_url'            => config('services.flashexpress.api_url', 'https://open-api.flashexpress.ph'),
                'customer_code_mask' => config('services.flashexpress.mch_id') ? substr(config('services.flashexpress.mch_id'), 0, 4) . '****' : null,
                'rate_endpoint'      => '/open/v1/orders/fee',
                'tracking_endpoint'  => '/open/v1/orders/track',
            ],
            'lbc' => [
                'has_credentials'    => !empty(config('services.lbc.partner_token')),
                'api_url'            => config('services.lbc.api_url', 'https://api.lbcexpress.com'),
                'customer_code_mask' => config('services.lbc.account_no') ? substr(config('services.lbc.account_no'), 0, 4) . '****' : null,
                'rate_endpoint'      => '/v1/rates/calculate',
                'tracking_endpoint'  => '/v1/trackings/{trackingNumber}',
            ],
        ];

        return view('admin.shipping.index', compact('providers', 'zones', 'zoneAreas', 'rates', 'courierApiStatus'));
    }

    public function toggleProvider(Request $request, $id)
    {
        $provider = ShippingProvider::findOrFail($id);
        $provider->is_active = !$provider->is_active;
        $provider->save();

        return redirect()->back()->with('success', "Logistics provider {$provider->name} status updated.");
    }

    public function updateProvider(Request $request, $id)
    {
        $provider = ShippingProvider::findOrFail($id);
        $validated = $request->validate([
            'name'                       => 'required|string|max:100',
            'default_volumetric_divisor' => 'required|integer|min:1',
            'is_active'                  => 'required|boolean'
        ]);

        $provider->update($validated);

        return redirect()->back()->with('success', "Provider {$provider->name} updated successfully.");
    }

    public function storeRate(Request $request)
    {
        $validated = $request->validate([
            'provider_id'            => 'required|string|exists:shipping_providers,id',
            'origin_zone_id'         => 'required|string|exists:shipping_zones,id',
            'destination_zone_id'    => 'required|string|exists:shipping_zones,id',
            'min_weight'             => 'required|numeric|min:0',
            'max_weight'             => 'nullable|numeric|gt:min_weight',
            'base_rate'              => 'required|numeric|min:0',
            'additional_weight_rate' => 'required|numeric|min:0',
            'estimated_days_min'     => 'required|integer|min:1',
            'estimated_days_max'     => 'required|integer|gte:estimated_days_min',
            'is_active'              => 'required|boolean'
        ]);

        ShippingRate::create($validated);

        return redirect()->back()->with('success', 'Shipping rate bracket created successfully.');
    }

    public function updateRate(Request $request, $id)
    {
        $rate = ShippingRate::findOrFail($id);
        $validated = $request->validate([
            'min_weight'             => 'required|numeric|min:0',
            'max_weight'             => 'nullable|numeric|gt:min_weight',
            'base_rate'              => 'required|numeric|min:0',
            'additional_weight_rate' => 'required|numeric|min:0',
            'estimated_days_min'     => 'required|integer|min:1',
            'estimated_days_max'     => 'required|integer|gte:estimated_days_min',
            'is_active'              => 'required|boolean'
        ]);

        $rate->update($validated);

        return redirect()->back()->with('success', 'Shipping rate updated successfully.');
    }

    public function destroyRate(Request $request, $id)
    {
        $rate = ShippingRate::findOrFail($id);
        $isReferenced = \App\Models\OrderShipping::where('shipping_rate_id', $id)->exists();
        if ($isReferenced) {
            $rate->is_active = false;
            $rate->save();
            return redirect()->back()->with('success', 'Rate bracket is associated with historical orders and has been deactivated instead of deleted.');
        }

        $rate->delete();
        return redirect()->back()->with('success', 'Rate bracket deleted successfully.');
    }

    public function storeArea(Request $request)
    {
        $validated = $request->validate([
            'zone_id'            => 'required|string|exists:shipping_zones,id',
            'province'           => 'required|string|max:100',
            'city'               => 'nullable|string|max:100',
            'barangay'           => 'nullable|string|max:100',
            'postal_code'        => 'nullable|string|max:20',
            'postal_code_prefix' => 'nullable|string|max:10',
        ]);

        ShippingZoneArea::create($validated);

        return redirect()->back()->with('success', 'Geographic area mapping added successfully.');
    }

    public function destroyArea(Request $request, $id)
    {
        $area = ShippingZoneArea::findOrFail($id);
        $area->delete();

        return redirect()->back()->with('success', 'Geographic area mapping removed.');
    }

    /**
     * Test live rate query between origin and destination.
     */
    public function testRate(Request $request)
    {
        $validated = $request->validate([
            'dest_province'    => 'required|string|max:100',
            'dest_city'        => 'required|string|max:100',
            'dest_postal_code' => 'nullable|string|max:20',
            'weight_kg'        => 'required|numeric|min:0.1',
            'length_cm'        => 'nullable|numeric|min:1',
            'width_cm'         => 'nullable|numeric|min:1',
            'height_cm'        => 'nullable|numeric|min:1',
        ]);

        $trackingService = app(CourierTrackingService::class);
        $zoneResolver = app(ShippingZoneResolverService::class);

        $originZone = $zoneResolver->resolve('Laguna', 'Lumban', null, '4014');
        $destZone = $zoneResolver->resolve(
            $validated['dest_province'],
            $validated['dest_city'],
            null,
            $validated['dest_postal_code'] ?? null
        );

        $providers = ShippingProvider::whereNotIn('code', ['store_pickup', 'seller_direct'])->get();
        $results = [];

        foreach ($providers as $p) {
            $carrierCode = $trackingService->getCarrierCode($p->code . ' ' . $p->name);
            $adapter = $carrierCode ? $trackingService->getProvider($carrierCode) : null;

            $liveQuote = null;
            $start = microtime(true);
            if ($adapter instanceof CourierRateProviderInterface) {
                try {
                    $liveQuote = $adapter->calculateRate(
                        '4014',
                        $validated['dest_province'],
                        $validated['dest_city'],
                        $validated['dest_postal_code'] ?? null,
                        (float) $validated['weight_kg'],
                        (float) ($validated['length_cm'] ?? 35),
                        (float) ($validated['width_cm'] ?? 25),
                        (float) ($validated['height_cm'] ?? 5)
                    );
                } catch (\Throwable $e) {
                    $liveQuote = null;
                }
            }
            $latencyMs = round((microtime(true) - $start) * 1000, 1);

            $dbRate = null;
            if ($originZone && $destZone) {
                $divisor = $p->default_volumetric_divisor ?: 3500;
                $volWeight = round((($validated['length_cm'] ?? 35) * ($validated['width_cm'] ?? 25) * ($validated['height_cm'] ?? 5)) / $divisor, 2);
                $chargeable = max((float)$validated['weight_kg'], $volWeight);

                $match = ShippingRate::where('provider_id', $p->id)
                    ->where('origin_zone_id', $originZone->id)
                    ->where('destination_zone_id', $destZone->id)
                    ->where('is_active', true)
                    ->where('min_weight', '<=', $chargeable)
                    ->where(function ($q) use ($chargeable) {
                        $q->whereNull('max_weight')->orWhere('max_weight', '>=', $chargeable);
                    })
                    ->first();

                if ($match) {
                    $fee = (float) $match->base_rate;
                    if ($match->max_weight === null && $match->additional_weight_rate > 0) {
                        $extra = ceil(max(0, $chargeable - $match->min_weight));
                        $fee += ($extra * (float) $match->additional_weight_rate);
                    }
                    $dbRate = [
                        'fee'  => round($fee, 2),
                        'days' => "{$match->estimated_days_min}–{$match->estimated_days_max} days",
                    ];
                }
            }

            $results[] = [
                'provider_name' => $p->name,
                'code'          => $p->code,
                'is_active'     => $p->is_active,
                'live_quote'    => $liveQuote,
                'db_rate'       => $dbRate,
                'latency_ms'    => $latencyMs,
            ];
        }

        return response()->json([
            'success'          => true,
            'origin_zone'      => $originZone ? $originZone->name : 'Unresolved',
            'destination_zone' => $destZone ? $destZone->name : 'Unresolved Route',
            'results'          => $results,
        ]);
    }
}

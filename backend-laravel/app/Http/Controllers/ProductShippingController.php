<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Product;
use App\Services\ShippingCalculatorService;
use App\Services\ShippingZoneResolverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductShippingController extends Controller
{
    protected ShippingCalculatorService $shippingCalculator;
    protected ShippingZoneResolverService $zoneResolver;

    public function __construct(
        ShippingCalculatorService $shippingCalculator,
        ShippingZoneResolverService $zoneResolver
    ) {
        $this->shippingCalculator = $shippingCalculator;
        $this->zoneResolver = $zoneResolver;
    }

    /**
     * Informational shipping estimator for single product detail page.
     * Supports authenticated users with address_id and guest visitors with location inputs.
     *
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     */
    public function getQuote(Request $request, Product $product): JsonResponse
    {
        $quantity = max(1, (int) $request->input('quantity', 1));
        $seller = $product->seller;

        if (!$seller) {
            return response()->json([
                'status'  => 'unserviceable',
                'message' => 'Artisan seller profile is unavailable.',
            ], 422);
        }

        // 1. Resolve Destination Location
        $destinationAddress = null;

        if (Auth::check()) {
            $addressId = $request->input('address_id');
            if ($addressId) {
                $destinationAddress = Address::where('id', $addressId)
                    ->where('userId', Auth::id())
                    ->first();
            } else {
                // Fallback to default or first saved address of user
                $destinationAddress = Address::where('userId', Auth::id())
                    ->orderByDesc('is_default')
                    ->first();
            }

            if (!$destinationAddress) {
                return response()->json([
                    'status'  => 'needs_address',
                    'message' => 'Please add a delivery address to view shipping estimates.',
                ]);
            }
        } else {
            // Guest mode: Requires manual location inputs
            $province   = $request->input('province');
            $city       = $request->input('city');
            $barangay   = $request->input('barangay');
            $postalCode = $request->input('postal_code');

            if (empty($province) && empty($city) && empty($postalCode)) {
                return response()->json([
                    'status'  => 'location_required',
                    'message' => 'Select your province or city to estimate shipping fees.',
                ]);
            }

            $destinationAddress = [
                'province'   => $province,
                'city'       => $city,
                'barangay'   => $barangay,
                'postalCode' => $postalCode,
            ];
        }

        // 2. Prepare single-item cart payload
        $cartItems = [
            [
                'id'       => $product->id,
                'quantity' => $quantity,
            ]
        ];

        try {
            $preferredProvider = $this->shippingCalculator->getSellerPreferredProvider($seller);
            if (!$preferredProvider) {
                return response()->json([
                    'status'  => 'unserviceable',
                    'message' => 'No active logistics providers configured for this artisan.',
                ]);
            }

            $quotes = $this->shippingCalculator->calculateQuotes(
                $seller,
                $destinationAddress,
                $cartItems,
                $preferredProvider->id
            );

            $quote = $quotes[0] ?? null;

            if (!$quote) {
                return response()->json([
                    'status'  => 'unserviceable',
                    'message' => 'Delivery is currently not available for your selected area.',
                ]);
            }

            return response()->json([
                'status'                    => 'available',
                'provider_id'               => $quote['provider_id'],
                'provider_name'             => $quote['provider_name'],
                'shipping_fee'              => (float) $quote['shipping_fee'],
                'shipping_fee_formatted'    => '₱' . number_format($quote['shipping_fee'], 2),
                'estimated_days_min'        => (int) $quote['estimated_days_min'],
                'estimated_days_max'        => (int) $quote['estimated_days_max'],
                'delivery_estimate_display' => $quote['delivery_estimate_display'],
                'destination_zone_name'     => $quote['destination_zone_name'],
                'origin_zone_name'          => $quote['origin_zone_name'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'unserviceable',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

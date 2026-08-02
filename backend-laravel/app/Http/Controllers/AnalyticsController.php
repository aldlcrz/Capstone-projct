<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function getSellerAnalytics(Request $request)
    {
        try {
            $sellerId = $request->user()->id;
            $startDate = $request->query('startDate');
            $endDate = $request->query('endDate');

            $productIds = Product::where('sellerId', $sellerId)->pluck('id')->toArray();

            if (empty($productIds)) {
                return response()->json([
                    'revenue' => 0,
                    'totalOrders' => 0,
                    'deliveredOrders' => 0,
                    'inquiryCount' => 0,
                    'bestSellers' => [],
                    'monthlyTrends' => [],
                    'orderStatusDistribution' => [
                        'pending' => 0, 'processing' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0
                    ]
                ]);
            }

            // Inquiry Count
            $inquiryCount = DB::table('messages')
                ->where('senderId', $sellerId)
                ->orWhere('receiverId', $sellerId)
                ->distinct('senderId')
                ->count();

            // Orders
            $query = Order::whereHas('items', function ($q) use ($productIds) {
                $q->whereIn('productId', $productIds);
            });

            if ($startDate && $endDate) {
                $query->whereBetween('createdAt', [$startDate, $endDate]);
            }

            $orders = $query->with(['items' => function ($q) use ($productIds) {
                $q->whereIn('productId', $productIds);
            }])->get();

            $totalRevenue = 0;
            $deliveredOrdersCount = 0;
            $productStats = [];
            $statusDistribution = ['pending' => 0, 'processing' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0];

            foreach ($orders as $order) {
                $isValidForRevenue = !in_array($order->status, ['Cancelled', 'Cancellation Requested']);
                
                foreach ($order->items as $item) {
                    $itemRevenue = $item->price * $item->quantity;
                    if ($isValidForRevenue) {
                        $totalRevenue += $itemRevenue;
                    }

                    if (!isset($productStats[$item->productId])) {
                        $productStats[$item->productId] = ['name' => $item->product_name ?? 'Product', 'qty' => 0, 'revenue' => 0];
                    }
                    $productStats[$item->productId]['qty'] += $item->quantity;
                    if ($isValidForRevenue) {
                        $productStats[$item->productId]['revenue'] += $itemRevenue;
                    }
                }

                if (in_array($order->status, ['Completed', 'Delivered'])) {
                    $deliveredOrdersCount++;
                }

                $statusKey = strtolower($order->status);
                if (isset($statusDistribution[$statusKey])) {
                    $statusDistribution[$statusKey]++;
                }
            }

            $bestSellers = collect($productStats)->sortByDesc('revenue')->take(5)->values()->all();

            return response()->json([
                'revenue' => $totalRevenue,
                'totalOrders' => $orders->count(),
                'deliveredOrders' => $deliveredOrdersCount,
                'inquiryCount' => $inquiryCount,
                'bestSellers' => $bestSellers,
                'orderStatusDistribution' => $statusDistribution
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}

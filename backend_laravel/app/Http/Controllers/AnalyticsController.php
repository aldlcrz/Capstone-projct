<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Message;
use App\Models\User;
use App\Models\ProductView;
use App\Models\SellerFunnelEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    private function parseStoredList(mixed $value)
    {
        if (is_array($value)) return $value;
        if (!is_string($value)) return [];
        $trimmed = trim($value);
        if (!$trimmed) return [];
        try {
            $parsed = json_decode($trimmed, true);
            if (is_array($parsed)) return $parsed;
        } catch (\Exception $e) {}
        return [];
    }

    private function getSellerInquiryCount(string|int $sellerId): int
    {
        $messages = Message::where('senderId', $sellerId)
            ->orWhere('receiverId', $sellerId)
            ->get(['senderId', 'receiverId']);

        $uniqueInquirers = [];
        foreach ($messages as $msg) {
            if ($msg->senderId !== $sellerId) $uniqueInquirers[] = $msg->senderId;
            if ($msg->receiverId !== $sellerId) $uniqueInquirers[] = $msg->receiverId;
        }
        return count(array_unique($uniqueInquirers));
    }

    private function processOrdersStats(iterable $orders, string|int $sellerId): array
    {
        $totalRevenue = 0;
        $deliveredOrdersCount = 0;
        $productStats = [];
        $monthlyData = [];

        foreach ($orders as $order) {
            $isValidForRevenue = !in_array($order->status, ['Cancelled', 'Cancellation Requested']);

            foreach ($order->items as $item) {
                if ($item->product && $item->product->sellerId === $sellerId) {
                    $itemRevenue = (float) $item->price * $item->quantity;

                    if ($isValidForRevenue) {
                        $totalRevenue += $itemRevenue;
                    }

                    $pid = $item->productId;
                    if (!isset($productStats[$pid])) {
                        $productStats[$pid] = ['name' => $item->product->name, 'qty' => 0, 'revenue' => 0];
                    }
                    $productStats[$pid]['qty'] += $item->quantity;
                    if ($isValidForRevenue) {
                        $productStats[$pid]['revenue'] += $itemRevenue;
                    }
                }
            }

            if (in_array($order->status, ['Completed', 'Delivered'])) {
                $deliveredOrdersCount++;
            }

            // Monthly trends
            $monthKey = $order->createdAt->format('Y-m');
            if (!isset($monthlyData[$monthKey])) {
                $monthlyData[$monthKey] = ['revenue' => 0, 'orders' => 0];
            }
            $monthlyData[$monthKey]['orders']++;

            if ($isValidForRevenue) {
                $sellerOrderRevenue = 0;
                foreach ($order->items as $item) {
                    if ($item->product && $item->product->sellerId === $sellerId) {
                        $sellerOrderRevenue += ((float) $item->price * $item->quantity);
                    }
                }
                $monthlyData[$monthKey]['revenue'] += $sellerOrderRevenue;
            }
        }

        usort($productStats, function ($a, $b) {
            return $b['revenue'] - $a['revenue'];
        });
        $bestSellers = array_slice($productStats, 0, 5);

        krsort($monthlyData);
        $sortedMonths = array_slice(array_keys($monthlyData), 0, 6);
        sort($sortedMonths);
        $monthlyTrends = [];
        foreach ($sortedMonths as $month) {
            $monthlyTrends[] = [
                'month' => $month,
                'revenue' => $monthlyData[$month]['revenue'],
                'orders' => $monthlyData[$month]['orders']
            ];
        }

        return [$totalRevenue, $deliveredOrdersCount, $bestSellers, $monthlyTrends];
    }

    private function calculateCohorts(iterable $orders, string|int $sellerId): array
    {
        $userCohorts = [];
        foreach ($orders as $o) {
            $isValid = !in_array($o->status, ['Cancelled', 'Cancellation Requested']);
            if (!$isValid || !$o->customerId) continue;
            $mKey = $o->createdAt->format('Y-m');
            if (!isset($userCohorts[$o->customerId]) || $userCohorts[$o->customerId] > $mKey) {
                $userCohorts[$o->customerId] = $mKey;
            }
        }

        $cohortStats = [];
        foreach ($orders as $o) {
            $isValid = !in_array($o->status, ['Cancelled', 'Cancellation Requested']);
            if (!$isValid || !$o->customerId) continue;

            $cohortMonth = $userCohorts[$o->customerId] ?? null;
            if (!$cohortMonth) continue;

            if (!isset($cohortStats[$cohortMonth])) {
                $cohortStats[$cohortMonth] = ['users' => [], 'revenuePerMonth' => [], 'retainedUsersPerMonth' => []];
            }
            if (!in_array($o->customerId, $cohortStats[$cohortMonth]['users'])) {
                $cohortStats[$cohortMonth]['users'][] = $o->customerId;
            }

            $cParts = explode('-', $cohortMonth);
            $cYear = (int) $cParts[0];
            $cM = (int) $cParts[1];
            $oYear = $o->createdAt->year;
            $oM = $o->createdAt->month;
            $monthsSince = ($oYear - $cYear) * 12 + ($oM - $cM);

            if ($monthsSince < 0) continue;

            $sRev = 0;
            foreach ($o->items as $item) {
                if ($item->product && $item->product->sellerId === $sellerId) {
                    $sRev += ((float) $item->price * $item->quantity);
                }
            }

            if (!isset($cohortStats[$cohortMonth]['revenuePerMonth'][$monthsSince])) {
                $cohortStats[$cohortMonth]['revenuePerMonth'][$monthsSince] = 0;
                $cohortStats[$cohortMonth]['retainedUsersPerMonth'][$monthsSince] = [];
            }
            $cohortStats[$cohortMonth]['revenuePerMonth'][$monthsSince] += $sRev;
            if (!in_array($o->customerId, $cohortStats[$cohortMonth]['retainedUsersPerMonth'][$monthsSince])) {
                $cohortStats[$cohortMonth]['retainedUsersPerMonth'][$monthsSince][] = $o->customerId;
            }
        }

        $cohortDataRaw = [];
        ksort($cohortStats);
        foreach ($cohortStats as $month => $stats) {
            $totalUsers = count($stats['users']);

            $cParts = explode('-', $month);
            $cYear = (int) $cParts[0];
            $cM = (int) $cParts[1];
            $nYear = now()->year;
            $nM = now()->month;
            $maxMonthsForThisCohort = max(0, ($nYear - $cYear) * 12 + ($nM - $cM));

            $ltv = [];
            $retention = [];
            $cumulativeRev = 0;

            for ($i = 0; $i <= $maxMonthsForThisCohort; $i++) {
                $revThisMonth = $stats['revenuePerMonth'][$i] ?? 0;
                $cumulativeRev += $revThisMonth;
                $ltv[] = $totalUsers > 0 ? ($cumulativeRev / $totalUsers) : 0;

                $retainedCount = isset($stats['retainedUsersPerMonth'][$i]) ? count($stats['retainedUsersPerMonth'][$i]) : 0;
                $retention[] = $totalUsers > 0 ? round(($retainedCount / $totalUsers) * 100, 1) : 0;
            }

            $cohortDataRaw[] = ['cohort' => $month, 'ltv' => $ltv, 'retention' => $retention];
        }

        return array_slice($cohortDataRaw, -6);
    }

    public function getSellerAnalytics(Request $request)
    {
        try {
            $startDate = $request->query('startDate');
            $endDate = $request->query('endDate');
            $sellerId = Auth::id();

            if (!$sellerId) {
                return response()->json(['message' => 'User ID missing from token'], 401);
            }

            // Products list
            $sellerProducts = Product::where('sellerId', $sellerId)->get(['id', 'name', 'price']);
            $productIds = $sellerProducts->pluck('id')->toArray();

            if (count($productIds) === 0) {
                return response()->json([
                    'revenue' => 0,
                    'totalOrders' => 0,
                    'deliveredOrders' => 0,
                    'inquiryCount' => 0,
                    'bestSellers' => [],
                    'monthlyTrends' => [],
                    'recentActivity' => [],
                    'orderStatusDistribution' => [
                        'pending' => 0,
                        'processing' => 0,
                        'shipped' => 0,
                        'completed' => 0,
                        'cancelled' => 0
                    ]
                ]);
            }

            // Inquiry Count
            $inquiryCount = $this->getSellerInquiryCount($sellerId);

            // Date filtering
            $query = Order::query();
            if ($startDate && $endDate) {
                $start = \Carbon\Carbon::parse($startDate)->startOfDay();
                $end = \Carbon\Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('createdAt', [$start, $end]);
            }

            // Orders containing these products
            $orders = $query->whereHas('items', function ($q) use ($productIds) {
                $q->whereIn('productId', $productIds);
            })
            ->with(['items' => function ($q) use ($productIds) {
                $q->whereIn('productId', $productIds)->with('product:id,name,sellerId');
            }])
            ->orderBy('createdAt', 'DESC')
            ->get();

            // Process stats
            [$totalRevenue, $deliveredOrdersCount, $bestSellers, $monthlyTrends] = $this->processOrdersStats($orders, $sellerId);

            // Cohort Analysis (LTV & Retention)
            $cohortData = $this->calculateCohorts($orders, $sellerId);

            $orderStatuses = [
                'pending' => $orders->filter(fn($o) => $o->status === 'Pending')->count(),
                'processing' => $orders->filter(fn($o) => in_array($o->status, ['Processing', 'To Ship']))->count(),
                'shipped' => $orders->filter(fn($o) => $o->status === 'Shipped')->count(),
                'completed' => $orders->filter(fn($o) => in_array($o->status, ['Completed', 'Delivered']))->count(),
                'cancelled' => $orders->filter(fn($o) => $o->status === 'Cancelled')->count()
            ];

            return response()->json([
                'revenue' => $totalRevenue,
                'totalOrders' => $orders->count(),
                'deliveredOrders' => $deliveredOrdersCount,
                'inquiryCount' => $inquiryCount,
                'bestSellers' => $bestSellers,
                'monthlyTrends' => $monthlyTrends,
                'cohortData' => $cohortData,
                'orderStatusDistribution' => $orderStatuses
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error fetching analytics', 'error' => $e->getMessage()], 500);
        }
    }

    public function exportSellerAnalytics(Request $request)
    {
        try {
            $startDate = $request->query('startDate');
            $endDate = $request->query('endDate');
            $sellerId = Auth::id();

            $dateFilter = [];
            if ($startDate && $endDate) {
                $start = \Carbon\Carbon::parse($startDate)->startOfDay();
                $end = \Carbon\Carbon::parse($endDate)->endOfDay();
                $dateFilter = [$start, $end];
            }

            $sellerProducts = Product::where('sellerId', $sellerId)->get(['id', 'name']);
            $productIds = $sellerProducts->pluck('id')->toArray();

            $orders = [];
            if (count($productIds) > 0) {
                $query = Order::query();
                if (count($dateFilter) > 0) {
                    $query->whereBetween('createdAt', $dateFilter);
                }
                $orders = $query->whereHas('items', function ($q) use ($productIds) {
                    $q->whereIn('productId', $productIds);
                })
                ->with(['items' => function ($q) use ($productIds) {
                    $q->whereIn('productId', $productIds)->with('product:id,name');
                }])
                ->orderBy('createdAt', 'DESC')
                ->get();
            }

            $csv = "Order ID,Date,Product,Quantity,Price,Total,Status\n";
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    $prodName = $item->product ? $item->product->name : 'N/A';
                    $total = (float) $item->price * $item->quantity;
                    $csv .= "{$order->id},\"" . $order->createdAt->format('Y-m-d') . "\",\"{$prodName}\",{$item->quantity}," . number_format($item->price, 2) . "," . number_format($total, 2) . ",{$order->status}\n";
                }
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="seller_analytics_' . date('Y-m-d') . '.csv"',
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error exporting analytics', 'error' => $e->getMessage()], 500);
        }
    }

    public function getPrescriptiveAnalytics()
    {
        try {
            $user = Auth::user();
            $prescriptions = [];
            $thirtyDaysAgo = now()->subDays(30);

            if ($user->role === 'admin') {
                // Admin: Inactive verified sellers
                $inactiveSellers = User::where('role', 'seller')
                    ->where('isVerified', true)
                    ->whereDoesntHave('sellerProducts')
                    ->get();

                if ($inactiveSellers->count() > 0) {
                    $prescriptions[] = [
                        'id' => 'admin_onboarding',
                        'priority' => 'high',
                        'type' => 'outreach',
                        'title' => 'Boost Artisan Activation',
                        'message' => "{$inactiveSellers->count()} verified artisans have 0 products listed.",
                        'prescription' => 'Launch an "Onboarding Webinar" or send a "Quick Start Guide" email to these sellers.',
                        'impact' => 'Increases platform inventory and variety.'
                    ];
                }

                // Trending categories
                $topCategoryItem = OrderItem::select('productId', DB::raw('COUNT(id) as orderCount'))
                    ->where('createdAt', '>', $thirtyDaysAgo)
                    ->groupBy('productId')
                    ->with('product:id,categories')
                    ->orderBy('orderCount', 'DESC')
                    ->first();

                if ($topCategoryItem && $topCategoryItem->product) {
                    $cats = $this->parseStoredList($topCategoryItem->product->categories);
                    $catName = count($cats) > 0 ? $cats[0] : 'Artisan Products';
                    $prescriptions[] = [
                        'id' => 'admin_trending',
                        'priority' => 'medium',
                        'type' => 'marketing',
                        'title' => 'Strategic Banner Placement',
                        'message' => "Sales in Category \"{$catName}\" are up 40% this month.",
                        'prescription' => 'Promote this category on the home page banner for the next 72 hours.',
                        'impact' => 'Capitalizes on current consumer trends.'
                    ];
                }

            } elseif ($user->role === 'seller') {
                // Seller: High views, low sales
                $myProducts = Product::where('sellerId', $user->id)->get();
                foreach ($myProducts as $product) {
                    $views = ProductView::where('productId', $product->id)->where('createdAt', '>', $thirtyDaysAgo)->count();
                    $sales = OrderItem::where('productId', $product->id)->where('createdAt', '>', $thirtyDaysAgo)->count();

                    if ($views > 20 && $sales === 0) {
                        $prescriptions[] = [
                            'id' => "seller_discount_{$product->id}",
                            'productId' => $product->id,
                            'priority' => 'high',
                            'type' => 'pricing',
                            'title' => "Unlock Sales for {$product->name}",
                            'message' => "Your product has {$views} views but 0 sales in 30 days.",
                            'prescription' => 'Apply a 10% limited-time discount to convert these high-intent visitors.',
                            'impact' => 'Potential to clear stagnant inventory.'
                        ];
                    }

                    if ($product->stock < 5 && $product->stock > 0) {
                        $prescriptions[] = [
                            'id' => "seller_stock_{$product->id}",
                            'productId' => $product->id,
                            'priority' => 'urgent',
                            'type' => 'inventory',
                            'title' => 'Stockout Imminent',
                            'message' => "You only have {$product->stock} units left of {$product->name}.",
                            'prescription' => 'Restock at least 15 units now to satisfy projected demand for next week.',
                            'impact' => 'Prevents loss of potential revenue.'
                        ];
                    }
                }

                $abandonments = SellerFunnelEvent::where('sellerId', $user->id)
                    ->where('eventType', 'add_to_cart')
                    ->where('createdAt', '>', $thirtyDaysAgo)
                    ->count();

                if ($abandonments > 5) {
                    $prescriptions[] = [
                        'id' => 'seller_cart_recovery',
                        'priority' => 'medium',
                        'type' => 'conversion',
                        'title' => 'Recover Lost Carts',
                        'message' => "{$abandonments} customers added items to their cart but didn't finish.",
                        'prescription' => 'Enable "Free Shipping" for orders over ₱2,000 to reduce checkout friction.',
                        'impact' => 'Increases conversion rate by addressing shipping costs.'
                    ];
                }
            }

            usort($prescriptions, function ($a, $b) {
                $p = ['urgent' => 3, 'high' => 2, 'medium' => 1];
                return $p[$b['priority']] - $p[$a['priority']];
            });

            return response()->json([
                'count' => count($prescriptions),
                'prescriptions' => $prescriptions
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error generating prescriptions', 'error' => $e->getMessage()], 500);
        }
    }
}

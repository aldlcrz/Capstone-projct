<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Message;
use App\Models\SellerFunnelEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function sellerDashboard(Request $request)
    {
        $response = $this->getSellerDashboardSummary($request);
        $data = json_decode($response->getContent(), true);

        if ($response->getStatusCode() !== 200) {
            return view('seller.dashboard', [
                'summary' => ['revenue' => 0, 'orders' => 0, 'customers' => 0, 'conversionRate' => '0%'],
                'inventoryHealth' => ['total' => 0, 'healthy' => 0, 'lowStock' => 0, 'outOfStock' => 0],
                'statusDistribution' => ['pending' => 0, 'processing' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0],
                'prescriptions' => [],
                'recentActivity' => [],
                'error' => $data['message'] ?? 'Unable to load dashboard data.'
            ]);
        }

        return view('seller.dashboard', $data);
    }

    public function getSellerDashboardSummary(Request $request)
    {
        try {
            $sellerId = $request->user()->id;
            $startDate = $request->query('startDate');
            $endDate = $request->query('endDate');

            // Date filtering
            $dateFilter = [];
            if ($startDate && $endDate) {
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
                $dateFilter = [$start, $end];
            }

            // 1. Orders
            $ordersQuery = Order::where('sellerId', $sellerId);
            if (!empty($dateFilter)) {
                $ordersQuery->whereBetween('createdAt', $dateFilter);
            }
            $orders = $ordersQuery->orderBy('createdAt', 'desc')->get();

            // 2. Products
            $products = Product::where('sellerId', $sellerId)
                ->select('id', 'name', 'price', 'stock', 'status')
                ->get();

            // 3. Message Inquiries (Unique Customers)
            $uniqueCustomersCount = Message::where('receiverId', $sellerId)
                ->where('senderId', '!=', $sellerId)
                ->distinct('senderId')
                ->count();

            // 4. Funnel Events
            $funnelEventsQuery = SellerFunnelEvent::where('seller_id', $sellerId);
            if (!empty($dateFilter)) {
                $funnelEventsQuery->whereBetween('created_at', $dateFilter);
            }
            $funnelEvents = $funnelEventsQuery->get();

            // Process Data
            $totalRevenue = 0;
            $statusDistribution = [
                'pending' => 0, 'processing' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0
            ];

            foreach ($orders as $order) {
                if ($order->status !== 'cancelled') {
                    $totalRevenue += (float)$order->totalAmount;
                }

                $status = strtolower($order->status);
                if ($status === 'pending') $statusDistribution['pending']++;
                elseif (in_array($status, ['confirmed', 'packed'])) $statusDistribution['processing']++;
                elseif ($status === 'shipped') $statusDistribution['shipped']++;
                elseif ($status === 'delivered' || $status === 'completed') $statusDistribution['completed']++;
                elseif ($status === 'cancelled') $statusDistribution['cancelled']++;
            }

            $lowStockProducts = $products->filter(fn($p) => $p->stock > 0 && $p->stock <= 5);
            $outOfStockProducts = $products->filter(fn($p) => $p->stock === 0);
            
            $inventoryHealth = [
                'total' => $products->count(),
                'lowStock' => $lowStockProducts->count(),
                'outOfStock' => $outOfStockProducts->count(),
                'healthy' => $products->count() - $lowStockProducts->count() - $outOfStockProducts->count()
            ];

            $uniqueVisitors = $funnelEvents->unique('visitor_session_id')->count() ?: 1;
            $conversionRate = number_format(($orders->count() / $uniqueVisitors) * 100, 1);

            $prescriptions = [];
            if ($inventoryHealth['lowStock'] > 0) {
                $prescriptions[] = [
                    'type' => 'inventory',
                    'priority' => 'urgent',
                    'title' => 'Restock Alert',
                    'message' => "You have {$inventoryHealth['lowStock']} items running low on stock.",
                    'action' => 'Visit your inventory to restock these items.'
                ];
            }

            return response()->json([
                'summary' => [
                    'revenue' => $totalRevenue,
                    'orders' => $orders->count(),
                    'customers' => $uniqueCustomersCount,
                    'conversionRate' => "{$conversionRate}%",
                ],
                'inventoryHealth' => $inventoryHealth,
                'statusDistribution' => $statusDistribution,
                'prescriptions' => array_slice($prescriptions, 0, 3),
                'recentActivity' => $orders->take(5)->map(fn($o) => [
                    'id' => $o->id,
                    'status' => $o->status,
                    'amount' => $o->totalAmount,
                    'date' => $o->createdAt
                ])
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error aggregating dashboard data', 'error' => $e->getMessage()], 500);
        }
    }

    public function sellerOrders(Request $request)
    {
        $orders = Order::where('sellerId', $request->user()->id)
            ->with(['customer:id,name,email', 'items.product'])
            ->orderBy('createdAt', 'desc')
            ->get();
        return view('seller.orders.index', compact('orders'));
    }

    public function sellerProfile(Request $request)
    {
        $user = $request->user();
        $stats = [
            'products' => Product::where('sellerId', $user->id)->count(),
            'orders'   => Order::where('sellerId', $user->id)->count(),
            'revenue'  => Order::where('sellerId', $user->id)->whereNotIn('status', ['Cancelled'])->sum('totalAmount'),
        ];
        return view('seller.profile.index', compact('user', 'stats'));
    }

    public function updateSellerProfile(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'name'            => 'required|string|max:255',
            'mobileNumber'    => 'nullable|string|max:20',
            'shopName'        => 'nullable|string|max:100',
            'shopDescription' => 'nullable|string|max:500',
        ]);
        $user->name            = $request->name;
        $user->mobileNumber    = $request->mobileNumber;
        $user->shopName        = $request->shopName;
        $user->shopDescription = $request->shopDescription;
        $user->save();
        return redirect()->route('seller.profile')->with('success', 'Profile updated successfully.');
    }
}

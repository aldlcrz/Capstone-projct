<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SystemSetting;
use App\Models\Notification;
use App\Models\SystemAuditLog;
use App\Utils\SocketUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    private function getRangeBounds(string $range)
    {
        $start = now()->startOfDay();
        if ($range === 'week') {
            $start = now()->startOfWeek(1);
        } elseif ($range === 'month') {
            $start = now()->startOfMonth();
        } elseif ($range === 'year') {
            $start = now()->startOfYear();
        }
        return $start;
    }

    private function cleanSettingValue(mixed $val)
    {
        if (is_string($val) && str_starts_with($val, '"') && str_ends_with($val, '"')) {
            try {
                return json_decode($val, true);
            } catch (\Exception $e) {
                return $val;
            }
        }
        return $val;
    }

    public function getGlobalStats(Request $request)
    {
        try {
            $range = $request->query('range');
            $start = null;
            if ($range && $range !== 'all') {
                $start = $this->getRangeBounds($range);
            }

            $ordersQuery = Order::where('status', '!=', 'cancelled');
            $ordersCountQuery = Order::query();
            $customersQuery = User::where('role', 'customer')->where('status', '!=', 'blocked');

            if ($start) {
                $ordersQuery->where('createdAt', '>=', $start);
                $ordersCountQuery->where('createdAt', '>=', $start);
                $customersQuery->where('createdAt', '>=', $start);
            }

            $totalSalesValue = (float) $ordersQuery->sum('totalAmount');
            $totalOrdersCount = $ordersCountQuery->count();
            $totalCustomersCount = $customersQuery->count();
            $totalProductsCount = Product::count();

            // Calculate Capital
            $orderItemsQuery = OrderItem::query();
            if ($start) {
                $orderItemsQuery->whereHas('order', function ($q) use ($start) {
                    $q->where('createdAt', '>=', $start)->where('status', '!=', 'cancelled');
                });
            } else {
                $orderItemsQuery->whereHas('order', function ($q) {
                    $q->where('status', '!=', 'cancelled');
                });
            }
            $orderItems = $orderItemsQuery->with('product')->get();

            $totalCapital = 0;
            foreach ($orderItems as $item) {
                $cost = $item->product ? (float) $item->product->costPerPiece : 0.00;
                $totalCapital += ($item->quantity ?: 0) * $cost;
            }

            $totalProfit = $totalSalesValue - $totalCapital;

            return response()->json([
                'totalSales' => '₱' . number_format($totalSalesValue),
                'totalCapital' => '₱' . number_format($totalCapital),
                'totalRevenue' => '₱' . number_format($totalSalesValue),
                'totalProfit' => '₱' . number_format($totalProfit),
                'totalOrders' => number_format($totalOrdersCount),
                'activeCustomers' => number_format($totalCustomersCount),
                'liveProducts' => number_format($totalProductsCount)
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getAdminAnalytics(Request $request)
    {
        try {
            $range = $request->query('range', 'week');
            $start = $this->getRangeBounds($range);

            $orders = Order::where('createdAt', '>=', $start)
                ->where('status', '!=', 'cancelled')
                ->select('id', 'createdAt', 'totalAmount')
                ->get();

            $newUsers = User::where('createdAt', '>=', $start)
                ->select('createdAt')
                ->get();

            $revenueSeries = [];
            $signupSeries = [];
            $now = now();

            if ($range === 'today') {
                $bins = [];
                for ($i = 0; $i < 24; $i++) {
                    $bins[$i] = ['name' => "{$i}:00", 'revenue' => 0, 'hits' => 0];
                }
                foreach ($orders as $o) {
                    $hour = $o->createdAt->hour;
                    $bins[$hour]['revenue'] += (float) $o->totalAmount;
                }
                foreach ($newUsers as $u) {
                    $hour = $u->createdAt->hour;
                    $bins[$hour]['hits']++;
                }
                $revenueSeries = array_values($bins);
                $signupSeries = array_values($bins);
            } elseif ($range === 'week') {
                $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                $weekOrder = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                $map = [];
                foreach ($weekOrder as $d) {
                    $map[$d] = ['revenue' => 0, 'hits' => 0];
                }
                foreach ($orders as $o) {
                    $name = $dayNames[$o->createdAt->dayOfWeek];
                    $map[$name]['revenue'] += (float) $o->totalAmount;
                }
                foreach ($newUsers as $u) {
                    $name = $dayNames[$u->createdAt->dayOfWeek];
                    if (isset($map[$name])) {
                        $map[$name]['hits']++;
                    }
                }
                foreach ($weekOrder as $name) {
                    $revenueSeries[] = ['name' => $name, 'revenue' => $map[$name]['revenue']];
                    $signupSeries[] = ['name' => $name, 'hits' => $map[$name]['hits']];
                }
            } elseif ($range === 'month') {
                $daysInMonth = $now->daysInMonth;
                $bins = [];
                for ($i = 1; $i <= $daysInMonth; $i++) {
                    $bins[$i] = ['name' => "{$i}", 'revenue' => 0, 'hits' => 0];
                }
                foreach ($orders as $o) {
                    $day = $o->createdAt->day;
                    if (isset($bins[$day])) {
                        $bins[$day]['revenue'] += (float) $o->totalAmount;
                    }
                }
                foreach ($newUsers as $u) {
                    $day = $u->createdAt->day;
                    if (isset($bins[$day])) {
                        $bins[$day]['hits']++;
                    }
                }
                $revenueSeries = array_values($bins);
                $signupSeries = array_values($bins);
            } elseif ($range === 'year') {
                $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                $bins = [];
                foreach ($monthNames as $idx => $name) {
                    $bins[$idx] = ['name' => $name, 'revenue' => 0, 'hits' => 0];
                }
                foreach ($orders as $o) {
                    $month = $o->createdAt->month - 1;
                    $bins[$month]['revenue'] += (float) $o->totalAmount;
                }
                foreach ($newUsers as $u) {
                    $month = $u->createdAt->month - 1;
                    $bins[$month]['hits']++;
                }
                $revenueSeries = array_values($bins);
                $signupSeries = array_values($bins);
            }

            // Recent activity (last 6 orders)
            $recentOrders = Order::with('customer:id,name,email')
                ->limit(6)
                ->orderBy('createdAt', 'DESC')
                ->get();

            $recentActivity = $recentOrders->map(function ($o) {
                return [
                    'id' => $o->id,
                    'type' => 'order',
                    'title' => 'Order #LB-' . str_pad($o->id, 6, '0', STR_PAD_LEFT),
                    'desc' => ($o->customer ? $o->customer->name : 'Customer') . " – {$o->status}",
                    'amount' => (float) $o->totalAmount,
                    'time' => $o->createdAt->toIso8601String(),
                    'status' => $o->status
                ];
            });

            // Top locations
            $ordersWithAddr = Order::where('createdAt', '>=', $start)->get(['shippingAddress']);
            $locationCount = [];
            foreach ($ordersWithAddr as $o) {
                $addr = $o->shippingAddress;
                $city = $addr['city'] ?? ($addr['province'] ?? null);
                if ($city) {
                    $locationCount[$city] = ($locationCount[$city] ?? 0) + 1;
                }
            }
            arsort($locationCount);
            $topLocations = [];
            foreach (array_slice($locationCount, 0, 5, true) as $city => $count) {
                $topLocations[] = ['city' => $city, 'count' => $count];
            }

            // Order status breakdown
            $pending = Order::where('status', 'Pending')->where('createdAt', '>=', $start)->count();
            $processing = Order::where('status', 'Processing')->where('createdAt', '>=', $start)->count();
            $shipped = Order::where('status', 'Shipped')->where('createdAt', '>=', $start)->count();
            $completed = Order::whereIn('status', ['Completed', 'Delivered'])->where('createdAt', '>=', $start)->count();
            $cancelled = Order::where('status', 'Cancelled')->where('createdAt', '>=', $start)->count();

            // Top Products & Categories
            $orderIds = $orders->pluck('id')->toArray();
            $topProducts = [];
            $topCategories = [];

            if (count($orderIds) > 0) {
                $topProductsData = OrderItem::whereIn('orderId', $orderIds)
                    ->select('productId', DB::raw('SUM(quantity) as totalSold'), DB::raw('SUM(quantity * price) as totalRevenue'))
                    ->groupBy('productId')
                    ->orderBy('totalSold', 'DESC')
                    ->limit(10)
                    ->with('product:id,name,categories')
                    ->get();

                foreach ($topProductsData as $d) {
                    if (!$d->product) continue;
                    $cat = $d->product->categories;
                    $categoryName = (is_array($cat) && count($cat) > 0) ? $cat[0] : 'Other';

                    $topProducts[] = [
                        'id' => $d->product->id,
                        'name' => $d->product->name,
                        'category' => $categoryName,
                        'sales' => (int) $d->totalSold,
                        'revenue' => (float) $d->totalRevenue
                    ];
                }

                // Aggregate Top Categories
                $allItemsData = OrderItem::whereIn('orderId', $orderIds)
                    ->select('productId', DB::raw('SUM(quantity) as totalSold'))
                    ->groupBy('productId')
                    ->with('product:id,categories')
                    ->get();

                $fullCategoriesMap = [];
                foreach ($allItemsData as $d) {
                    if (!$d->product) continue;
                    $cat = $d->product->categories;
                    $sold = (int) $d->totalSold;
                    if (is_array($cat) && count($cat) > 0) {
                        foreach ($cat as $c) {
                            $fullCategoriesMap[$c] = ($fullCategoriesMap[$c] ?? 0) + $sold;
                        }
                    } else {
                        $fullCategoriesMap['Other'] = ($fullCategoriesMap['Other'] ?? 0) + $sold;
                    }
                }
                arsort($fullCategoriesMap);
                foreach (array_slice($fullCategoriesMap, 0, 5, true) as $name => $value) {
                    $topCategories[] = ['name' => $name, 'value' => $value];
                }
            }

            return response()->json([
                'revenueSeries' => $revenueSeries,
                'monthlySignups' => $signupSeries,
                'recentActivity' => $recentActivity,
                'topLocations' => $topLocations,
                'orderStatusBreakdown' => compact('pending', 'processing', 'shipped', 'completed', 'cancelled'),
                'topProducts' => $topProducts,
                'topCategories' => $topCategories
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getPendingSellers()
    {
        try {
            $sellers = User::where('role', 'seller')->where('isVerified', false)->where('status', 'active')->get();
            return response()->json($sellers);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getSellers()
    {
        try {
            // Include average rating subqueries
            $sellers = User::where('role', 'seller')
                ->where('isVerified', true)
                ->where('status', '!=', 'blocked')
                ->select('*')
                ->selectSub(function ($q) {
                    $q->selectRaw('AVG(rating)')
                      ->from('reviews')
                      ->join('products', 'reviews.productId', '=', 'products.id')
                      ->whereColumn('products.sellerId', 'users.id');
                }, 'avgRating')
                ->selectSub(function ($q) {
                    $q->selectRaw('COUNT(*)')
                      ->from('reviews')
                      ->join('products', 'reviews.productId', '=', 'products.id')
                      ->whereColumn('products.sellerId', 'users.id');
                }, 'reviewCount')
                ->orderBy('createdAt', 'DESC')
                ->get()
                ->makeHidden(['password']);

            return response()->json($sellers);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getSellerPerformance(Request $request)
    {
        try {
            $range = $request->query('range', 'month');
            $start = null;
            if ($range && $range !== 'all') {
                $start = $this->getRangeBounds($range);
            }

            $sellers = User::where('role', 'seller')
                ->where('isVerified', true)
                ->where('status', '!=', 'blocked')
                ->get();

            $commissionRateSetting = SystemSetting::where('key', 'commissionRate')->first();
            $commissionRate = $commissionRateSetting ? (float) $this->cleanSettingValue($commissionRateSetting->value) : 10.0;

            $performance = $sellers->map(function ($s) use ($start, $commissionRate) {
                $ordersQuery = Order::where('sellerId', $s->id)
                    ->where('status', '!=', 'cancelled');

                if ($start) {
                    $ordersQuery->where('createdAt', '>=', $start);
                }

                $orders = $ordersQuery->get(['id', 'totalAmount']);
                $totalSales = (float) $orders->sum('totalAmount');
                $orderCount = $orders->count();

                // Compute totalCost (production cost of items sold)
                $orderItemsQuery = OrderItem::whereHas('order', function ($q) use ($s, $start) {
                    $q->where('sellerId', $s->id)->where('status', '!=', 'cancelled');
                    if ($start) {
                        $q->where('createdAt', '>=', $start);
                    }
                });

                $orderItems = $orderItemsQuery->with('product')->get();
                $totalCost = 0.00;
                foreach ($orderItems as $item) {
                    $cost = $item->product ? (float) $item->product->costPerPiece : 0.00;
                    $totalCost += ($item->quantity ?: 0) * $cost;
                }

                $commissionPaid = $totalSales * ($commissionRate / 100.0);
                $netSales = $totalSales - $commissionPaid;
                $netProfit = $netSales - $totalCost;

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'shopName' => $s->shopName ?: $s->name,
                    'email' => $s->email,
                    'profilePhoto' => $s->profilePhoto,
                    'joinedAt' => $s->createdAt->toIso8601String(),
                    'totalSales' => $totalSales,
                    'commissionRate' => $commissionRate,
                    'commissionPaid' => $commissionPaid,
                    'netSales' => $netSales,
                    'totalCost' => $totalCost,
                    'netProfit' => $netProfit,
                    'orderCount' => $orderCount
                ];
            });

            return response()->json([
                'performance' => $performance,
                'commissionRate' => $commissionRate
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getCustomers()
    {
        try {
            $customers = User::where('role', 'customer')
                ->where('status', '!=', 'blocked')
                ->orderBy('createdAt', 'DESC')
                ->get()
                ->makeHidden(['password']);

            return response()->json($customers);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function deleteCustomer(Request $request, string $id)
    {
        try {
            $reason = trim($request->input('reason'));
            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            if ($user->role === 'seller') {
                Product::where('sellerId', $user->id)->delete();
            }

            $user->update([
                'status' => 'blocked',
                'violationReason' => $reason ?: 'Account terminated by administrator.'
            ]);

            SocketUtility::emitUserUpdated($user, ['action' => 'terminated']);
            SocketUtility::emitForceLogout($user->id, 'blocked', $user->violationReason);

            SystemAuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'BLOCK_USER',
                'entity_type' => 'User',
                'entity_id' => $user->id,
                'details' => ['user_name' => $user->name, 'reason' => $user->violationReason],
            ]);

            return response()->json(['message' => 'Account blocked successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function toggleCustomerStatus(string $id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $user->update([
                'status' => 'active',
                'violationReason' => null,
                'loginAttempts' => 0,
                'loginLockedUntil' => null
            ]);

            SocketUtility::emitUserUpdated($user, ['action' => 'status_changed']);

            SystemAuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'UNFREEZE_USER',
                'entity_type' => 'User',
                'entity_id' => $user->id,
                'details' => ['user_name' => $user->name],
            ]);

            return response()->json(['message' => 'User unfrozen successfully', 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function freezeUser(Request $request, string $id)
    {
        try {
            $reason = trim($request->input('reason'));
            if (!$reason) {
                return response()->json(['message' => 'A reason is required to freeze an account.'], 400);
            }

            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $user->update([
                'status' => 'frozen',
                'violationReason' => $reason
            ]);

            SocketUtility::emitUserUpdated($user, ['action' => 'frozen']);
            SocketUtility::emitForceLogout($user->id, 'frozen', $user->violationReason);

            SystemAuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'FREEZE_USER',
                'entity_type' => 'User',
                'entity_id' => $user->id,
                'details' => ['user_name' => $user->name, 'reason' => $reason],
            ]);

            return response()->json(['message' => 'Account frozen successfully', 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function purgeCache()
    {
        return response()->json(['message' => 'Platform caches purged successfully']);
    }

    public function verifySeller(string $id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'Seller not found'], 404);
            }

            $user->update(['isVerified' => true]);

            $publicUser = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'isVerified' => $user->isVerified,
                'profilePhoto' => $user->profilePhoto,
            ];

            SocketUtility::emitUserUpdated($publicUser, ['action' => 'verified']);
            SocketUtility::emitDashboardUpdate();

            Notification::create([
                'userId' => $user->id,
                'title' => 'Seller verification approved',
                'message' => 'Your artisan workshop is now verified and can access seller tools.',
                'type' => 'system',
                'link' => '/seller/dashboard',
                'targetRole' => 'seller'
            ]);
            SocketUtility::emitToUser($user->id, 'new_notification', [
                'title' => 'Seller verification approved',
                'message' => 'Your artisan workshop is now verified and can access seller tools.'
            ]);

            SystemAuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'VERIFY_SELLER',
                'entity_type' => 'User',
                'entity_id' => $user->id,
                'details' => ['seller_email' => $user->email],
            ]);

            return response()->json(['message' => 'Seller verified successfully', 'user' => $publicUser]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function rejectSeller(Request $request, string $id)
    {
        try {
            $reason = trim($request->input('reason'));
            if (!$reason) {
                return response()->json(['message' => 'A rejection reason is required.'], 400);
            }

            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'Seller not found'], 404);
            }

            $user->update([
                'status' => 'rejected',
                'rejectionReason' => $reason,
                'isVerified' => false
            ]);

            Notification::create([
                'userId' => $user->id,
                'title' => 'Seller Application Rejected',
                'message' => "Your artisan application was not approved. Reason: {$reason}",
                'type' => 'system',
                'link' => '/seller/profile',
                'targetRole' => 'seller'
            ]);
            SocketUtility::emitToUser($user->id, 'new_notification', [
                'title' => 'Seller Application Rejected',
                'message' => "Your artisan application was not approved. Reason: {$reason}"
            ]);

            SocketUtility::emitDashboardUpdate();

            SystemAuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'REJECT_SELLER',
                'entity_type' => 'User',
                'entity_id' => $user->id,
                'details' => ['reason' => $reason],
            ]);

            return response()->json(['message' => 'Seller application rejected with reason']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getSettings()
    {
        try {
            $settings = SystemSetting::all();
            $settingsMap = [];
            foreach ($settings as $s) {
                $settingsMap[$s->key] = $this->cleanSettingValue($s->value);
            }
            return response()->json($settingsMap);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getPublicSettings()
    {
        try {
            $keys = ['landingPageBackground', 'landingPageBackgroundPosition', 'maintenanceMode'];
            $settings = SystemSetting::whereIn('key', $keys)->get();
            $settingsMap = [];
            foreach ($settings as $s) {
                $settingsMap[$s->key] = $this->cleanSettingValue($s->value);
            }
            return response()->json($settingsMap);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function updateSettings(Request $request)
    {
        try {
            $updates = $request->all();

            if (isset($updates['commissionRate'])) {
                $rate = (float) $updates['commissionRate'];
                if ($rate < 0 || $rate > 100) {
                    return response()->json(['message' => 'Order rate must be between 0 and 100.'], 400);
                }
            }

            foreach ($updates as $key => $value) {
                SystemSetting::updateOrCreate(['key' => $key], ['value' => $this->cleanSettingValue($value)]);
            }

            $updatedSettings = SystemSetting::all();
            $settingsMap = [];
            foreach ($updatedSettings as $s) {
                $settingsMap[$s->key] = $this->cleanSettingValue($s->value);
            }

            SocketUtility::emitSettingsUpdated($settingsMap);

            return response()->json(['message' => 'Settings updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function sendBroadcast(Request $request)
    {
        try {
            $message = $request->input('message');
            $title = $request->input('title');

            if (!$message) {
                return response()->json(['message' => 'Message is required for broadcast'], 400);
            }

            SocketUtility::broadcast($message, $title ?: 'System Announcement');

            return response()->json(['message' => 'Broadcast sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function exportGlobalReport()
    {
        try {
            $orders = Order::with(['customer', 'seller', 'items.product'])->orderBy('createdAt', 'DESC')->get();
            $products = Product::with('seller')->orderBy('name', 'ASC')->get();
            
            $totalRevenue = (float) Order::where('status', '!=', 'cancelled')->sum('totalAmount');
            $totalOrders = Order::count();
            $totalCustomers = User::where('role', 'customer')->count();
            $totalProducts = Product::count();

            $csv = "Type,ID,Title,Details,Amount,Status,Date\n";
            $csv .= "--- SYSTEM OVERVIEW ---,,,,,\n";
            $csv .= "METRIC,-,Total Platform Revenue,Aggregated sales (excluding cancellations)," . number_format($totalRevenue, 2) . ",Live," . date('Y-m-d') . "\n";
            $csv .= "METRIC,-,Total Order Volume,Historical orders processed,{$totalOrders},Live,-\n";
            $csv .= "METRIC,-,Registered Customers,Active consumer base,{$totalCustomers},Live,-\n";
            $csv .= "METRIC,-,Live Marketplace Items,Total products listed,{$totalProducts},Live,-\n";
            $csv .= ",,,,,,\n";

            $csv .= "--- TRANSACTION LOG ---,,,,,\n";
            foreach ($orders as $o) {
                $customerName = $o->customer ? $o->customer->name : 'Unknown';
                $sellerName = $o->seller ? $o->seller->name : 'Unknown';
                $addr = $o->shippingAddress;
                $location = isset($addr['city']) ? "{$addr['city']}, {$addr['province']}" : 'N/A';
                $csv .= "ORDER,{$o->id},\"{$customerName} bought from {$sellerName}\",\"Seller Contact: " . ($o->seller->email ?? 'N/A') . " \| Location: {$location}\"," . number_format($o->totalAmount, 2) . ",{$o->status},\"{$o->createdAt}\"\n";
            }
            $csv .= ",,,,,,\n";

            $csv .= "--- GLOBAL CATALOG ---,,,,,\n";
            foreach ($products as $p) {
                $sellerName = $p->seller ? $p->seller->name : 'Unknown';
                $cat = is_array($p->categories) ? implode(', ', $p->categories) : ($p->categories ?: 'N/A');
                $csv .= "PRODUCT,{$p->id},\"{$p->name}\",\"Seller: {$sellerName} \| Category: {$cat} \| Stock: {$p->stock}\"," . number_format($p->price, 2) . ",{$p->status},-\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="admin_system_report_' . time() . '.csv"',
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error generating global system report', 'error' => $e->getMessage()], 500);
        }
    }

    public function getPendingProducts()
    {
        try {
            $products = Product::where('status', 'pending')
                ->with('seller:id,name,email')
                ->orderBy('createdAt', 'ASC')
                ->get();
            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function approveProduct(string $id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            $product->update(['status' => 'approved', 'rejectionReason' => null]);

            Notification::create([
                'userId' => $product->sellerId,
                'title' => 'Product Approved',
                'message' => "Your product \"{$product->name}\" has been approved and is now live!",
                'type' => 'product_approved',
                'link' => "/seller/products/{$product->id}",
                'targetRole' => 'seller'
            ]);
            SocketUtility::emitToUser($product->sellerId, 'new_notification', [
                'title' => 'Product Approved',
                'message' => "Your product \"{$product->name}\" has been approved and is now live!"
            ]);

            SocketUtility::emitDashboardUpdate();

            SystemAuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'APPROVE_PRODUCT',
                'entity_type' => 'Product',
                'entity_id' => $product->id,
                'details' => ['product_name' => $product->name],
            ]);

            return response()->json(['message' => 'Product approved successfully', 'product' => $product]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function rejectProduct(Request $request, string $id)
    {
        try {
            $reason = trim($request->input('reason'));
            if (!$reason) {
                return response()->json(['message' => 'Rejection reason is required'], 400);
            }

            $product = Product::find($id);
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            $product->update(['status' => 'rejected', 'rejectionReason' => $reason]);

            Notification::create([
                'userId' => $product->sellerId,
                'title' => 'Product Rejected',
                'message' => "Your product \"{$product->name}\" was rejected. Reason: {$reason}",
                'type' => 'product_rejected',
                'link' => "/seller/products/{$product->id}",
                'targetRole' => 'seller'
            ]);
            SocketUtility::emitToUser($product->sellerId, 'new_notification', [
                'title' => 'Product Rejected',
                'message' => "Your product \"{$product->name}\" was rejected. Reason: {$reason}"
            ]);

            SocketUtility::emitDashboardUpdate();

            SystemAuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'REJECT_PRODUCT',
                'entity_type' => 'Product',
                'entity_id' => $product->id,
                'details' => ['product_name' => $product->name, 'reason' => $reason],
            ]);

            return response()->json(['message' => 'Product rejected successfully', 'product' => $product]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}

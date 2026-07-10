<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Models\ProductView;
use App\Models\Review;
use App\Models\Notification;
use App\Utils\SocketUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\OrderItem;

class ProductController extends Controller
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
            if (is_string($parsed) && trim($parsed)) return [$parsed];
        } catch (\Exception $e) {}
        if (str_starts_with($trimmed, '[') || str_ends_with($trimmed, ']')) return [];
        return [$trimmed];
    }

    private function getClientIp(Request $request)
    {
        return $request->ip();
    }

    private function getVisitorSessionId(Request $request)
    {
        $val = $request->header('x-visitor-session') ?: $request->input('visitorSessionId');
        return is_string($val) ? substr(trim($val), 0, 128) : null;
    }

    private function shouldTrackCustomerAnalytics(mixed $user)
    {
        return !$user || $user->role === 'customer';
    }

    private function toPublicImageUrl(mixed $url)
    {
        if (!$url) return null;
        if (is_array($url) && isset($url['url'])) {
            $url['url'] = $this->toPublicImageUrl($url['url']);
            return $url;
        }
        if (!is_string($url)) return null;
        $url = trim($url);
        if (!$url) return null;
        if (preg_match('/^https?:\/\//i', $url) || str_starts_with($url, 'data:') || str_starts_with($url, 'blob:') || str_starts_with($url, '/images/')) {
            return $url;
        }
        $normalized = str_replace('\\', '/', $url);
        $normalized = ltrim($normalized, './');
        if (str_starts_with($normalized, 'uploads/')) {
            return url($normalized);
        }
        return str_starts_with($normalized, '/') ? $normalized : '/' . $normalized;
    }

    private function serializeReview(mixed $review)
    {
        if (!$review) return null;
        $data = is_array($review) ? $review : $review->toArray();
        $images = $this->parseStoredList($data['images'] ?? '[]');
        $publicImages = [];
        foreach ($images as $img) {
            $publicImages[] = $this->toPublicImageUrl($img);
        }
        $data['images'] = array_filter($publicImages);
        if (isset($review->customer)) {
            $data['customer'] = [
                'id' => $review->customer->id,
                'name' => $review->customer->name,
                'profilePhoto' => $this->toPublicImageUrl($review->customer->profilePhoto),
            ];
        }
        return $data;
    }

    private function serializeProduct(mixed $product)
    {
        $data = is_array($product) ? $product : $product->toArray();
        $images = $this->parseStoredList($data['image'] ?? '[]');
        $publicImages = [];
        foreach ($images as $img) {
            $publicImages[] = $this->toPublicImageUrl($img);
        }
        $data['image'] = array_filter($publicImages);
        $data['sizes'] = array_filter($this->parseStoredList($data['sizes'] ?? '[]'));
        $data['categories'] = array_filter($this->parseStoredList($data['categories'] ?? '[]'));
        $data['tags'] = array_filter($this->parseStoredList($data['tags'] ?? '[]'));

        if (isset($product->seller)) {
            $data['artisan'] = $product->seller->name;
            $data['seller'] = [
                'id' => $product->seller->id,
                'name' => $product->seller->name,
                'gcashNumber' => $product->seller->gcashNumber,
                'gcashQrCode' => $this->toPublicImageUrl($product->seller->gcashQrCode),
                'mayaNumber' => $product->seller->mayaNumber,
                'mayaQrCode' => $this->toPublicImageUrl($product->seller->mayaQrCode),
                'isVerified' => $product->seller->isVerified,
                'bio' => $product->seller->bio,
                'profilePhoto' => $this->toPublicImageUrl($product->seller->profilePhoto),
            ];
        }

        if (isset($product->reviews)) {
            $serializedReviews = [];
            foreach ($product->reviews as $rev) {
                $serializedReviews[] = $this->serializeReview($rev);
            }
            $data['reviews'] = array_filter($serializedReviews);
        }

        $data['rating'] = number_format((float) ($product->avgRating ?? 0), 1);
        $data['reviewCount'] = (int) ($product->reviewCount ?? 0);
        $data['soldCount'] = (int) ($product->soldCount ?? 0);

        return $data;
    }

    private function getProductLookup(string $id)
    {
        $user = Auth::user();
        if ($user && $user->role === 'admin') {
            return Product::find($id);
        }
        return Product::where('id', $id)->where('sellerId', $user->id)->first();
    }

    public function getAllProducts(Request $request)
    {
        try {
            $category = $request->query('category');
            $search = $request->query('search');
            $seller = $request->query('seller');
            $style = $request->query('style');

            $query = Product::where('status', 'approved');

            if ($category && $category !== 'All') {
                $query->where('categories', 'like', "%{$category}%");
            }

            if ($style) {
                $query->where('description', 'like', "%{$style}%");
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('fabric_type', 'like', "%{$search}%")
                      ->orWhere('artisan_region', 'like', "%{$search}%");
                });
            }

            if ($seller) {
                $query->where('sellerId', $seller);
            }

            // Subqueries for statistics
            $query->select('*')
                ->selectSub(function ($q) {
                    $q->selectRaw('AVG(rating)')->from('reviews')->whereColumn('productId', 'products.id');
                }, 'avgRating')
                ->selectSub(function ($q) {
                    $q->selectRaw('COUNT(*)')->from('reviews')->whereColumn('productId', 'products.id');
                }, 'reviewCount')
                ->selectSub(function ($q) {
                    $q->selectRaw('COALESCE(SUM(quantity), 0)')
                      ->from('order_items')
                      ->join('orders', 'order_items.orderId', '=', 'orders.id')
                      ->whereColumn('order_items.productId', 'products.id')
                      ->whereIn('orders.status', ['Delivered', 'Completed']);
                }, 'soldCount');

            $products = $query->with('seller:id,name,gcashNumber,gcashQrCode,mayaNumber,mayaQrCode,isVerified,bio,profilePhoto')
                ->orderBy('createdAt', 'DESC')
                ->get();

            $serialized = $products->map(fn($p) => $this->serializeProduct($p));
            return response()->json($serialized);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getSellerProducts()
    {
        try {
            $user = Auth::user();
            $products = Product::where('sellerId', $user->id)
                ->orderBy('createdAt', 'DESC')
                ->get();

            $serialized = $products->map(fn($p) => $this->serializeProduct($p));
            return response()->json($serialized);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getProductById(Request $request, string $id)
    {
        try {
            $query = Product::where('id', $id);
            
            $query->select('*')
                ->selectSub(function ($q) {
                    $q->selectRaw('AVG(rating)')->from('reviews')->whereColumn('productId', 'products.id');
                }, 'avgRating')
                ->selectSub(function ($q) {
                    $q->selectRaw('COUNT(*)')->from('reviews')->whereColumn('productId', 'products.id');
                }, 'reviewCount')
                ->selectSub(function ($q) {
                    $q->selectRaw('COALESCE(SUM(quantity), 0)')
                      ->from('order_items')
                      ->join('orders', 'order_items.orderId', '=', 'orders.id')
                      ->whereColumn('order_items.productId', 'products.id')
                      ->whereIn('orders.status', ['Delivered', 'Completed']);
                }, 'soldCount');

            $product = $query->with([
                'seller:id,name,gcashNumber,gcashQrCode,mayaNumber,mayaQrCode,createdAt,isVerified,bio,profilePhoto',
                'reviews' => function ($q) {
                    $q->with('customer:id,name,profilePhoto')->orderBy('createdAt', 'DESC');
                }
            ])->first();

            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            $user = auth('api')->user();

            if ($product->status !== 'approved' && (!$user || ($user->role !== 'admin' && $user->id !== $product->sellerId))) {
                return response()->json(['message' => 'This product is currently under review or has been rejected.'], 403);
            }

            if ($this->shouldTrackCustomerAnalytics($user)) {
                try {
                    $product->increment('views');

                    ProductView::create([
                        'productId' => $product->id,
                        'sellerId' => $product->sellerId,
                        'customerId' => ($user && $user->role === 'customer') ? $user->id : null,
                        'visitorSessionId' => $this->getVisitorSessionId($request),
                        'ipAddress' => $this->getClientIp($request),
                    ]);

                    SocketUtility::emitStatsUpdate(['type' => 'view', 'sellerId' => $product->sellerId]);
                } catch (\Exception $analyticsEx) {
                    logger()->error('View tracking error: ' . $analyticsEx->getMessage());
                }
            }

            return response()->json($this->serializeProduct($product));

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function createProduct(Request $request)
    {
        try {
            $user = Auth::user();
            $name = trim($request->input('name'));
            $description = trim($request->input('description'));
            $price = $request->input('price');
            $costPerPiece = $request->input('costPerPiece', 0);
            $sizes = $request->input('sizes');
            $categories = $request->input('categories');
            $tags = $request->input('tags');
            $stock = $request->input('stock');
            $variationNames = $request->input('variationNames');
            $shippingFee = $request->input('shippingFee', 0);
            $shippingDays = $request->input('shippingDays', 3);
            $sku = $request->input('sku');
            $fabric_type = $request->input('fabric_type');
            $collar_type = $request->input('collar_type');
            $artisan_region = $request->input('artisan_region');

            if (!$name || !$price || $stock === null) {
                return response()->json(['message' => 'Name, price, and stock are required'], 400);
            }

            if ($price <= 0 || $price > 1000000) {
                return response()->json(['message' => 'Price must be between 1 and 1,000,000 PHP'], 400);
            }

            if ($stock < 0) {
                return response()->json(['message' => 'Stock cannot be negative'], 400);
            }

            // Handle uploads — convert each image to a base64 data URI stored in the DB
            $images = [];
            if ($request->hasFile('images')) {
                $labels = is_array($variationNames) ? $variationNames : json_decode($variationNames ?: '[]', true);
                $files = $request->file('images');
                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $index => $file) {
                    $mimeType  = $file->getMimeType();
                    $imageData = base64_encode(file_get_contents($file->getRealPath()));
                    $dataUri   = "data:{$mimeType};base64,{$imageData}";
                    $images[] = [
                        'url'       => $dataUri,
                        'variation' => $labels[$index] ?? "Variation " . ($index + 1)
                    ];
                }
            }

            $product = Product::create([
                'name' => $name,
                'description' => $description ?: null,
                'price' => $price,
                'costPerPiece' => $costPerPiece,
                'sizes' => is_array($sizes) ? $sizes : json_decode($sizes ?: '[]', true),
                'categories' => is_array($categories) ? $categories : json_decode($categories ?: '[]', true),
                'tags' => is_array($tags) ? $tags : json_decode($tags ?: '[]', true),
                'stock' => $stock,
                'shippingFee' => $shippingFee,
                'shippingDays' => $shippingDays,
                'sellerId' => $user->id,
                'image' => $images,
                'status' => 'pending',
                'sku' => $sku ?: null,
                'fabric_type' => $fabric_type ?: null,
                'collar_type' => $collar_type ?: null,
                'artisan_region' => $artisan_region ?: null
            ]);

            SocketUtility::emitInventoryUpdated($product, ['action' => 'created']);

            // Notify followers
            if ($user->followers) {
                $followers = is_array($user->followers) ? $user->followers : json_decode($user->followers ?: '[]', true);
                if (is_array($followers)) {
                    foreach ($followers as $followerId) {
                        Notification::create([
                            'userId' => $followerId,
                            'title' => 'New Product Alert!',
                            'message' => "{$user->name} has just uploaded a new product: {$product->name}. Check it out!",
                            'type' => 'system',
                            'link' => "/products?id={$product->id}",
                            'targetRole' => 'customer'
                        ]);
                        SocketUtility::emitToUser($followerId, 'new_notification', [
                            'title' => 'New Product Alert!',
                            'message' => "{$user->name} has just uploaded a new product: {$product->name}.",
                        ]);
                    }
                }
            }

            // Notify admins
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'userId' => $admin->id,
                    'title' => 'New Product Posted',
                    'message' => "Seller {$user->name} posted a new product: {$product->name}",
                    'type' => 'system',
                    'link' => "/admin/products?search=" . urlencode($product->name),
                    'targetRole' => 'admin'
                ]);
                SocketUtility::emitToUser($admin->id, 'new_notification', [
                    'title' => 'New Product Posted',
                    'message' => "Seller {$user->name} posted a new product: {$product->name}"
                ]);
            }

            return response()->json($this->serializeProduct($product), 201);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function updateProduct(Request $request, string $id)
    {
        try {
            $product = $this->getProductLookup($id);
            if (!$product) {
                return response()->json(['message' => 'Product not found or access denied'], 404);
            }

            $name = $request->input('name');
            $description = $request->input('description');
            $price = $request->input('price');
            $costPerPiece = $request->input('costPerPiece');
            $sizes = $request->input('sizes');
            $categories = $request->input('categories');
            $tags = $request->input('tags');
            $stock = $request->input('stock');
            $variationNames = $request->input('variationNames');
            $existingImages = $request->input('existingImages');
            $shippingFee = $request->input('shippingFee');
            $shippingDays = $request->input('shippingDays');
            $sku = $request->input('sku');
            $fabric_type = $request->input('fabric_type');
            $collar_type = $request->input('collar_type');
            $artisan_region = $request->input('artisan_region');

            if ($price !== null && $price <= 0) {
                return response()->json(['message' => 'Price must be positive'], 400);
            }

            $images = $product->image ?: [];
            if ($existingImages) {
                $images = is_array($existingImages) ? $existingImages : json_decode($existingImages, true);
            }

            if ($request->hasFile('images')) {
                $labels = is_array($variationNames) ? $variationNames : json_decode($variationNames ?: '[]', true);
                $files = $request->file('images');
                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $index => $file) {
                    $mimeType  = $file->getMimeType();
                    $imageData = base64_encode(file_get_contents($file->getRealPath()));
                    $dataUri   = "data:{$mimeType};base64,{$imageData}";
                    $images[] = [
                        'url'       => $dataUri,
                        'variation' => $labels[$index] ?? "Variation " . (count($images) + 1)
                    ];
                }
            }

            $product->update([
                'name' => $name ?? $product->name,
                'description' => $description ?? $product->description,
                'price' => $price ?? $product->price,
                'costPerPiece' => $costPerPiece ?? $product->costPerPiece,
                'sizes' => $sizes ? (is_array($sizes) ? $sizes : json_decode($sizes, true)) : $product->sizes,
                'categories' => $categories ? (is_array($categories) ? $categories : json_decode($categories, true)) : $product->categories,
                'tags' => $tags !== null ? (is_array($tags) ? $tags : json_decode($tags, true)) : $product->tags,
                'stock' => $stock ?? $product->stock,
                'shippingFee' => $shippingFee ?? $product->shippingFee,
                'shippingDays' => $shippingDays ?? $product->shippingDays,
                'image' => $images,
                'sku' => $sku ?? $product->sku,
                'fabric_type' => $fabric_type ?? $product->fabric_type,
                'collar_type' => $collar_type ?? $product->collar_type,
                'artisan_region' => $artisan_region ?? $product->artisan_region,
            ]);

            SocketUtility::emitInventoryUpdated($product, ['action' => 'updated']);

            return response()->json($this->serializeProduct($product));

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function deleteProduct(string $id)
    {
        try {
            $product = $this->getProductLookup($id);
            if (!$product) {
                return response()->json(['message' => 'Product not found or access denied'], 404);
            }

            $product->delete();
            SocketUtility::emitInventoryUpdated($product, ['action' => 'deleted']);

            return response()->json(['message' => 'Product deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

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

    private function getSellerPerformanceData(string $range, iterable $ordersTrend): array
    {
        $performance = [];
        $now = now();
        if ($range === 'today') {
            $bins = [];
            for ($i = 0; $i < 24; $i++) {
                $bins[$i] = ['name' => "{$i}:00", 'sales' => 0];
            }
            foreach ($ordersTrend as $o) {
                $hour = $o->createdAt->hour;
                $bins[$hour]['sales'] += (float) $o->totalAmount;
            }
            $performance = array_values($bins);
        } elseif ($range === 'week') {
            $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            $weekOrder = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $map = [];
            foreach ($weekOrder as $d) {
                $map[$d] = 0;
            }
            foreach ($ordersTrend as $o) {
                $name = $dayNames[$o->createdAt->dayOfWeek];
                if (isset($map[$name])) {
                    $map[$name] += (float) $o->totalAmount;
                }
            }
            foreach ($weekOrder as $name) {
                $performance[] = ['name' => $name, 'sales' => $map[$name]];
            }
        } elseif ($range === 'month') {
            $daysInMonth = $now->daysInMonth;
            $bins = [];
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $bins[$i] = ['name' => "{$i}", 'sales' => 0];
            }
            foreach ($ordersTrend as $o) {
                $day = $o->createdAt->day;
                if (isset($bins[$day])) {
                    $bins[$day]['sales'] += (float) $o->totalAmount;
                }
            }
            $performance = array_values($bins);
        } elseif ($range === 'year') {
            $monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            $bins = [];
            foreach ($monthNames as $idx => $name) {
                $bins[$idx] = ['name' => $name, 'sales' => 0];
            }
            foreach ($ordersTrend as $o) {
                $month = $o->createdAt->month - 1;
                $bins[$month]['sales'] += (float) $o->totalAmount;
            }
            $performance = array_values($bins);
        }
        return $performance;
    }

    private function getSellerTopProductsAndRetention(string $sellerId, $start, array $qualifyingOrders): array
    {
        $topProducts = [];
        $topCategories = [];
        $retentionRate = 0;

        if (count($qualifyingOrders) > 0) {
            $topProductsData = OrderItem::whereIn('orderId', $qualifyingOrders)
                ->select('productId', DB::raw('SUM(quantity) as totalSold'))
                ->groupBy('productId')
                ->orderBy('totalSold', 'DESC')
                ->limit(5)
                ->with('product:id,name,price')
                ->get();

            foreach ($topProductsData as $d) {
                if (!$d->product) continue;
                $topProducts[] = [
                    'productId' => $d->productId,
                    'name' => $d->product->name,
                    'qty' => (int) $d->totalSold,
                    'revenue' => ((float) $d->product->price) * $d->totalSold
                ];
            }

            // Suki Retention Calculation
            $totalCustomers = Order::where('sellerId', $sellerId)
                ->where('status', '!=', 'Cancelled')
                ->distinct('customerId')
                ->count('customerId');

            $repeatCustomers = Order::where('sellerId', $sellerId)
                ->where('status', '!=', 'Cancelled')
                ->select('customerId', DB::raw('COUNT(id) as orderCount'))
                ->groupBy('customerId')
                ->having('orderCount', '>', 1)
                ->get()
                ->count();

            $retentionRate = $totalCustomers > 0 ? ($repeatCustomers / $totalCustomers) * 100 : 0;

            // Top Categories
            $allItems = OrderItem::whereIn('orderId', $qualifyingOrders)
                ->with('product:id,categories,price')
                ->get();

            $catMap = [];
            foreach ($allItems as $item) {
                if (!$item->product) continue;
                $cats = $this->parseStoredList($item->product->categories);
                $primary = count($cats) > 0 ? $cats[0] : 'Other';

                if (!isset($catMap[$primary])) {
                    $catMap[$primary] = ['name' => $primary, 'value' => 0, 'revenue' => 0];
                }
                $catMap[$primary]['value'] += $item->quantity;
                $catMap[$primary]['revenue'] += $item->quantity * (float) $item->product->price;
            }

            usort($catMap, fn($a, $b) => $b['revenue'] - $a['revenue']);
            $topCategories = array_slice($catMap, 0, 5);
        }

        return [$topProducts, $topCategories, $retentionRate];
    }

    public function getSellerStats(Request $request)
    {
        try {
            $sellerId = Auth::id();
            if (!$sellerId) {
                return response()->json(['message' => 'Seller ID missing from token'], 401);
            }

            $range = $request->query('range', 'month');
            $start = $this->getRangeBounds($range);

            // KPI Counts within Range
            $totalRevenue = (float) Order::where('sellerId', $sellerId)
                ->where('createdAt', '>=', $start)
                ->where('status', '!=', 'Cancelled')
                ->sum('totalAmount');

            $totalOrders = Order::where('sellerId', $sellerId)
                ->where('createdAt', '>=', $start)
                ->count();

            $completedOrders = Order::where('sellerId', $sellerId)
                ->where('createdAt', '>=', $start)
                ->whereIn('status', ['Delivered', 'Completed'])
                ->count();

            $contactLeadCustomers = \App\Models\Message::where('receiverId', $sellerId)
                ->where('senderId', '!=', $sellerId)
                ->where('createdAt', '>=', $start)
                ->distinct('senderId')
                ->count('senderId');

            // Global counts
            $totalInventory = Product::where('sellerId', $sellerId)->count();
            $lowStock = Product::where('sellerId', $sellerId)->where('stock', '<', 5)->count();
            $totalViews = ProductView::where('sellerId', $sellerId)->count();

            // Strict Unique Metrics for Funnel
            $viewIds = ProductView::where('sellerId', $sellerId)
                ->where('createdAt', '>=', $start)
                ->select(DB::raw("COALESCE(CAST(customerId AS CHAR), visitorSessionId, ipAddress) as uid"))
                ->pluck('uid')->toArray();

            $msgIds = \App\Models\Message::where('receiverId', $sellerId)
                ->where('createdAt', '>=', $start)
                ->select('senderId as uid')
                ->pluck('uid')->toArray();

            $cartIds = \App\Models\SellerFunnelEvent::where('sellerId', $sellerId)
                ->where('eventType', 'add_to_cart')
                ->where('createdAt', '>=', $start)
                ->select(DB::raw("COALESCE(CAST(customerId AS CHAR), visitorSessionId, ipAddress) as uid"))
                ->pluck('uid')->toArray();

            $orderIds = Order::where('sellerId', $sellerId)
                ->where('createdAt', '>=', $start)
                ->select('customerId as uid')
                ->pluck('uid')->toArray();

            $visitorsSet = array_filter(array_unique(array_merge($viewIds, $msgIds, $cartIds, $orderIds)));
            $rangeVisitors = count($visitorsSet);
            $rangeViewsUnique = count(array_filter(array_unique($viewIds)));

            $addedToCart = \App\Models\SellerFunnelEvent::where('sellerId', $sellerId)
                ->where('eventType', 'add_to_cart')
                ->where('createdAt', '>=', $start)
                ->select(DB::raw("COALESCE(CAST(customerId AS CHAR), visitorSessionId, ipAddress) as uid"))
                ->distinct()
                ->count();

            // Performance trend series
            $ordersTrend = Order::where('sellerId', $sellerId)
                ->where('status', '!=', 'Cancelled')
                ->where('createdAt', '>=', $start)
                ->orderBy('createdAt', 'ASC')
                ->get(['totalAmount', 'createdAt']);

            $performance = $this->getSellerPerformanceData($range, $ordersTrend);

            // Top sold products & Suki retention
            $qualifyingOrders = Order::where('sellerId', $sellerId)
                ->where('status', '!=', 'Cancelled')
                ->where('createdAt', '>=', $start)
                ->pluck('id')
                ->toArray();

            [$topProducts, $topCategories, $retentionRate] = $this->getSellerTopProductsAndRetention($sellerId, $start, $qualifyingOrders);

            return response()->json([
                'revenue' => $totalRevenue,
                'orders' => $totalOrders,
                'inquiries' => $contactLeadCustomers,
                'products' => $totalInventory,
                'lowStock' => $lowStock,
                'performance' => $performance,
                'topProducts' => $topProducts,
                'topCategories' => $topCategories,
                'retention' => round($retentionRate, 1),
                'funnel' => [
                    'visitors' => $rangeVisitors,
                    'contacts' => $contactLeadCustomers,
                    'leads' => $rangeViewsUnique,
                    'addedToCart' => $addedToCart,
                    'checkout' => $totalOrders,
                    'completed' => $completedOrders
                ],
                'global' => [
                    'views' => $totalViews
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function trackProductFunnelEvent(Request $request, string $id)
    {
        try {
            $eventType = $request->input('eventType');
            if ($eventType !== 'add_to_cart') {
                return response()->json(['message' => 'Unsupported funnel event type'], 400);
            }

            $user = Auth::user();
            if ($user && $user->role !== 'customer') {
                return response()->json(null, 204);
            }

            $product = Product::find($id);
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            \App\Models\SellerFunnelEvent::create([
                'sellerId' => $product->sellerId,
                'productId' => $product->id,
                'customerId' => ($user && $user->role === 'customer') ? $user->id : null,
                'visitorSessionId' => $this->getVisitorSessionId($request),
                'ipAddress' => $this->getClientIp($request),
                'eventType' => $eventType
            ]);

            SocketUtility::emitStatsUpdate(['type' => $eventType, 'sellerId' => $product->sellerId, 'productId' => $product->id]);

            return response()->json(['success' => true], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}

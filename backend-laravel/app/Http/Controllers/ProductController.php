<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Models\ProductView;
use App\Models\SellerFunnelEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Serialize a product to match frontend expectations.
     */
    private function serializeProduct(Request $request, $product)
    {
        $data = is_array($product) ? $product : $product->toArray();

        // Handle image URLs
        $images = $data['image'] ?? [];
        if (is_string($images)) {
            $images = json_decode($images, true) ?? [];
        }

        $formattedImages = array_map(function ($img) use ($request) {
            $url = is_array($img) ? ($img['url'] ?? null) : $img;
            if (!$url) return null;

            if (preg_match('/^https?:\/\//i', $url) || str_starts_with($url, 'data:') || str_starts_with($url, 'blob:')) {
                return $url;
            }

            $normalized = str_replace('\\', '/', $url);
            $normalized = ltrim($normalized, './');

            if (str_starts_with($normalized, 'uploads/')) {
                return $request->getSchemeAndHttpHost() . '/' . $normalized;
            }

            return str_starts_with($normalized, '/') ? $normalized : '/' . $normalized;
        }, $images);

        $data['image'] = array_values(array_filter($formattedImages));

        // Handle seller
        if (isset($data['seller'])) {
            $data['seller']['profilePhoto'] = $this->toPublicUrl($request, $data['seller']['profileImage'] ?? null);
            $data['seller']['gcashQrCode'] = $this->toPublicUrl($request, $data['seller']['gcashQrCode'] ?? null);
            unset($data['seller']['profileImage']); // Match frontend which uses profilePhoto
        }

        $data['rating'] = number_format($product->avgRating ?? 0, 1);
        $data['reviewCount'] = (int)($product->reviewCount ?? 0);
        $data['soldCount'] = (int)($product->soldCount ?? 0);

        return $data;
    }

    private function toPublicUrl(Request $request, ?string $path)
    {
        if (!$path) return null;
        if (preg_match('/^https?:\/\//i', $path)) return $path;
        
        $normalized = str_replace('\\', '/', $path);
        $normalized = ltrim($normalized, './');

        if (str_starts_with($normalized, 'uploads/')) {
            return $request->getSchemeAndHttpHost() . '/' . $normalized;
        }

        return str_starts_with($normalized, '/') ? $normalized : '/' . $normalized;
    }

    /**
     * Get all approved products with filters.
     */
    public function getAllProducts(Request $request)
    {
        $query = Product::where('status', 'approved')
            ->with(['seller:id,name,gcashNumber,isVerified,profileImage'])
            ->withAvg('reviews as avgRating', 'rating')
            ->withCount('reviews as reviewCount');

        // soldCount subquery
        $query->addSelect(['soldCount' => function ($q) {
            $q->selectRaw('SUM(quantity)')
                ->from('order_items')
                ->join('orders', 'order_items.orderId', '=', 'orders.id')
                ->whereColumn('order_items.productId', 'products.id')
                ->whereIn('orders.status', ['Delivered', 'Completed']);
        }]);

        if ($request->has('category') && $request->category !== 'All') {
            $query->where('CategoryId', $request->category);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->has('seller')) {
            $query->where('sellerId', $request->seller);
        }

        $products = $query->orderBy('createdAt', 'desc')->get();

        return response()->json($products->map(fn($p) => $this->serializeProduct($request, $p)));
    }

    /**
     * Get products for the authenticated seller.
     */
    public function getSellerProducts(Request $request)
    {
        $products = Product::where('sellerId', $request->user()->id)
            ->orderBy('createdAt', 'desc')
            ->get();

        return response()->json($products->map(fn($p) => $this->serializeProduct($request, $p)));
    }

    /**
     * Get a product by ID.
     */
    public function getProductById(Request $request, string $id)
    {
        $product = Product::with(['seller', 'reviews.customer:id,name,profileImage'])
            ->withAvg('reviews as avgRating', 'rating')
            ->withCount('reviews as reviewCount')
            ->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Restriction: Only owner or admin can see unapproved products
        if ($product->status !== 'approved' && (!$request->user() || ($request->user()->role !== 'admin' && $request->user()->id !== $product->sellerId))) {
            return response()->json(['message' => 'This product is currently under review or has been rejected.'], 403);
        }

        // Increment views (simple)
        $product->increment('views');

        // Analytics tracking (match Node logic)
        try {
            ProductView::create([
                'product_id' => $product->id,
                'seller_id' => $product->sellerId,
                'customer_id' => ($request->user() && $request->user()->role === 'customer') ? $request->user()->id : null,
                'visitor_session_id' => $request->header('X-Visitor-Session') ?? $request->visitorSessionId,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            \Log::error('View tracking error: ' . $e->getMessage());
        }

        return response()->json($this->serializeProduct($request, $product));
    }

    /**
     * Track funnel events (e.g. add_to_cart).
     */
    public function trackProductFunnelEvent(Request $request, string $id)
    {
        try {
            $product = Product::find($id);
            if (!$product) return response()->json(['message' => 'Product not found'], 404);

            SellerFunnelEvent::create([
                'product_id' => $id,
                'seller_id' => $product->sellerId,
                'customer_id' => ($request->user() && $request->user()->role === 'customer') ? $request->user()->id : null,
                'visitor_session_id' => $request->header('X-Visitor-Session') ?? $request->visitorSessionId,
                'ip_address' => $request->ip(),
                'event_type' => $request->eventType ?? 'add_to_cart'
            ]);

            return response()->json(['message' => 'Event tracked successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new product.
     */
    public function createProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:1',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
        }

        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/products'), $filename);
                $images[] = ['url' => '/uploads/products/' . $filename];
            }
        }

        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'costPerPiece' => $request->costPerPiece ?? 0,
            'stock' => $request->stock,
            'sellerId' => $request->user()->id,
            'image' => $images,
            'status' => 'pending',
            'CategoryId' => $request->CategoryId,
            'availableColors' => $request->availableColors ?? [],
            'availableDesigns' => $request->availableDesigns ?? [],
            'shippingFee' => $request->shippingFee ?? 0,
            'shippingDays' => $request->shippingDays ?? 3,
        ]);

        return response()->json($this->serializeProduct($request, $product), 201);
    }

    /**
     * Update a product.
     */
    public function updateProduct(Request $request, string $id)
    {
        $product = Product::where('id', $id)
            ->where('sellerId', $request->user()->id)
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found or access denied'], 404);
        }

        $product->update($request->only([
            'name', 'description', 'price', 'costPerPiece', 'stock', 
            'shippingFee', 'shippingDays', 'CategoryId', 
            'availableColors', 'availableDesigns'
        ]));

        return response()->json($this->serializeProduct($request, $product));
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(Request $request, string $id)
    {
        $product = Product::where('id', $id);
        
        if ($request->user()->role !== 'admin') {
            $product->where('sellerId', $request->user()->id);
        }

        $product = $product->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    /**
     * Get seller statistics.
     */
    public function getSellerStats(Request $request)
    {
        $sellerId = $request->user()->id;

        $totalRevenue = DB::table('orders')
            ->where('sellerId', $sellerId)
            ->where('status', '!=', 'Cancelled')
            ->sum('totalAmount');

        $totalOrders = DB::table('orders')
            ->where('sellerId', $sellerId)
            ->count();

        $totalInventory = Product::where('sellerId', $sellerId)->count();
        $lowStock = Product::where('sellerId', $sellerId)->where('stock', '<', 5)->count();

        return response()->json([
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'totalInventory' => $totalInventory,
            'lowStock' => $lowStock,
        ]);
    }
}

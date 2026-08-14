<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    /**
     * Create or update a review.
     */
    public function store(Request $request)
    {
        $request->validate([
            'productId'   => 'required|exists:products,id',
            'orderId'     => 'required|exists:orders,id',
            'orderItemId' => 'nullable|exists:order_items,id',
            'rating'      => 'required|integer|min:1|max:5',
            'comment'     => 'required|string|max:1000',
            'photos'      => 'nullable|array',
            'photos.*'    => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'images'      => 'nullable|array',
            'video'       => 'nullable|file|mimes:mp4,mov,avi,webm,mkv|max:51200',
        ]);

        $customerId = Auth::id();
        $productId = $request->productId;
        $orderId = $request->orderId;

        // 1. Verify Order ownership
        $order = Order::where('id', $orderId)
            ->where('customerId', $customerId)
            ->first();

        if (!$order) {
            return $this->errorResponse($request, 'Order not found or does not belong to your account.', 403);
        }

        // 2. Verify Order status is Delivered or Completed
        $orderStatus = strtolower(trim($order->status));
        if (!in_array($orderStatus, ['delivered', 'completed'], true)) {
            return $this->errorResponse($request, 'Products can only be rated after the order has been delivered or completed.', 403);
        }

        // 3. Verify OrderItem exists in this order
        $orderItemQuery = OrderItem::where('orderId', $order->id)
            ->where('productId', $productId);

        if ($request->filled('orderItemId')) {
            $orderItemQuery->where('id', $request->orderItemId);
        }

        $orderItem = $orderItemQuery->first();

        if (!$orderItem) {
            return $this->errorResponse($request, 'This product is not part of the specified order.', 400);
        }

        // 4. Duplicate Check: Ensure one review per purchased order item
        $existingReview = Review::where('orderItemId', $orderItem->id)->first();
        if (!$existingReview) {
            $existingReview = Review::where('productId', $productId)
                ->where('customerId', $customerId)
                ->where('orderId', $order->id)
                ->first();
        }

        if ($existingReview) {
            return $this->errorResponse($request, 'You have already submitted a review for this purchased product.', 422);
        }

        // 5. Handle File Uploads (Photos & Video)
        $uploadedImages = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('reviews/photos', 'public');
                    $uploadedImages[] = asset('storage/' . $path);
                }
            }
        } elseif ($request->filled('images') && is_array($request->images)) {
            $uploadedImages = $request->images;
        }

        $uploadedVideo = null;
        if ($request->hasFile('video') && $request->file('video')->isValid()) {
            $videoPath = $request->file('video')->store('reviews/videos', 'public');
            $uploadedVideo = asset('storage/' . $videoPath);
        }

        // 6. Create Review
        $review = Review::create([
            'productId'   => $productId,
            'customerId'  => $customerId,
            'orderId'     => $order->id,
            'orderItemId' => $orderItem->id,
            'rating'      => $request->rating,
            'comment'     => $request->comment,
            'images'      => !empty($uploadedImages) ? json_encode($uploadedImages) : null,
            'video'       => $uploadedVideo,
        ]);

        // 6. Notify Seller of new product review
        try {
            $product = Product::find($productId);
            if ($product && $product->sellerId) {
                Notification::create([
                    'userId' => $product->sellerId,
                    'title' => '⭐ New Product Review',
                    'message' => "A customer left a {$request->rating}-star review for \"{$product->name}\".",
                    'type' => 'review',
                    'link' => '/seller/products',
                    'targetRole' => 'seller',
                    'isRead' => false
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Review notification error: ' . $e->getMessage());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Review submitted successfully!',
                'review' => $review
            ]);
        }

        return redirect()->back()->with('success', 'Review submitted successfully! Thank you for your feedback.');
    }

    /**
     * Get reviews for a specific product.
     *
     * @param int|string $productId
     */
    public function getProductReviews($productId)
    {
        $reviews = Review::where('productId', $productId)
            ->with('customer:id,name,profilePhoto')
            ->orderBy('createdAt', 'desc')
            ->get()
            ->map(function($review) {
                $review->images = json_decode($review->images) ?: [];
                return $review;
            });

        return response()->json($reviews);
    }

    /**
     * Get reviews for a specific seller's products.
     *
     * @param int|string $sellerId
     */
    public function getSellerReviews($sellerId)
    {
        $reviews = Review::whereHas('product', function($query) use ($sellerId) {
                $query->where('sellerId', $sellerId);
            })
            ->with(['product:id,name,images', 'customer:id,name,profilePhoto'])
            ->orderBy('createdAt', 'desc')
            ->get()
            ->map(function($review) {
                $review->images = json_decode($review->images) ?: [];
                return $review;
            });

        return response()->json($reviews);
    }

    /**
     * Helper to return appropriate error response based on request type.
     */
    private function errorResponse(Request $request, string $message, int $code = 400)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => $message], $code);
        }
        return redirect()->back()->with('error', $message);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use App\Utils\SocketUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
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
        return [];
    }

    private function toPublicImageUrl(mixed $url)
    {
        if (!$url) return null;
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
        $data = $review->toArray();
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
        if (isset($review->product)) {
            $productImages = $this->parseStoredList($review->product->image ?? '[]');
            $pubProdImages = [];
            foreach ($productImages as $img) {
                $pubProdImages[] = $this->toPublicImageUrl($img);
            }
            $data['product'] = [
                'name' => $review->product->name,
                'image' => array_filter($pubProdImages),
            ];
        }
        return $data;
    }

    private function emitReviewUpdated(string $productId, string $sellerId, string $reviewId)
    {
        try {
            $productAvg = Review::where('productId', $productId)->avg('rating') ?: 0;
            $productCount = Review::where('productId', $productId)->count();

            $sellerAvg = Review::whereHas('product', function ($q) use ($sellerId) {
                $q->where('sellerId', $sellerId);
            })->avg('rating') ?: 0;

            $sellerCount = Review::whereHas('product', function ($q) use ($sellerId) {
                $q->where('sellerId', $sellerId);
            })->count();

            $latestReview = Review::with('customer:id,name,profilePhoto')->find($reviewId);

            $payload = [
                'productId' => $productId,
                'sellerId' => $sellerId,
                'productRating' => round($productAvg, 1),
                'productReviewCount' => $productCount,
                'sellerRating' => round($sellerAvg, 1),
                'sellerReviewCount' => $sellerCount,
                'latestReview' => $this->serializeReview($latestReview),
                'timestamp' => now()->toIso8601String()
            ];

            SocketUtility::emit('review_updated', $payload);
            SocketUtility::emitStatsUpdate(['type' => 'review', 'productId' => $productId, 'sellerId' => $sellerId]);
        } catch (\Exception $e) {
            logger()->error('Emit review updated failed: ' . $e->getMessage());
        }
    }

    public function createReview(Request $request)
    {
        try {
            $productId = $request->input('productId');
            $orderId = $request->input('orderId');
            $rating = (int) $request->input('rating');
            $comment = $request->input('comment');
            $images = $request->input('images');
            $customerId = Auth::id();

            $product = Product::find($productId);
            if (!$product) {
                return response()->json(['message' => 'Product not found.'], 404);
            }

            $imagesStr = is_array($images) ? json_encode($images) : $images;

            $review = Review::where('productId', $productId)->where('customerId', $customerId)->first();

            if ($review) {
                $review->update([
                    'rating' => $rating,
                    'comment' => $comment,
                    'images' => $imagesStr
                ]);
            } else {
                $review = Review::create([
                    'productId' => $productId,
                    'customerId' => $customerId,
                    'orderId' => $orderId,
                    'rating' => $rating,
                    'comment' => $comment,
                    'images' => $imagesStr
                ]);
            }

            $this->emitReviewUpdated($product->id, $product->sellerId, $review->id);

            return response()->json([
                'message' => 'Review submitted successfully',
                'review' => $review
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getProductReviews(string $productId)
    {
        try {
            $reviews = Review::where('productId', $productId)
                ->with('customer:id,name,profilePhoto')
                ->orderBy('createdAt', 'DESC')
                ->get();

            $serialized = $reviews->map(fn($r) => $this->serializeReview($r));
            return response()->json(array_filter($serialized->toArray()));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getSellerReviews(string $sellerId)
    {
        try {
            $reviews = Review::whereHas('product', function ($q) use ($sellerId) {
                $q->where('sellerId', $sellerId);
            })
            ->with([
                'product:id,name,image',
                'customer:id,name,profilePhoto'
            ])
            ->orderBy('createdAt', 'DESC')
            ->get();

            $serialized = $reviews->map(fn($r) => $this->serializeReview($r));
            return response()->json(array_filter($serialized->toArray()));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}

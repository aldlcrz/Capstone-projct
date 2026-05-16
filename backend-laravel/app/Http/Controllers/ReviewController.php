<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Create or update a review.
     */
    public function store(Request $request)
    {
        $request->validate([
            'productId' => 'required|exists:products,id',
            'orderId' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'images' => 'nullable|array',
        ]);

        $customerId = Auth::id();
        $productId = $request->productId;

        $review = Review::updateOrCreate(
            ['productId' => $productId, 'customerId' => $customerId],
            [
                'orderId' => $request->orderId,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'images' => $request->images ? json_encode($request->images) : null,
            ]
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Review submitted successfully',
                'review' => $review
            ]);
        }

        return redirect()->back()->with('success', 'Review submitted successfully');
    }

    /**
     * Get reviews for a specific product.
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
}

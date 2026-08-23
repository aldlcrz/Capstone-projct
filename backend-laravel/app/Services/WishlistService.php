<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\Notification;
use App\Mail\WishlistRestockedMail;
use Illuminate\Support\Facades\Log;

class WishlistService
{
    /**
     * Handle automated cart addition and email dispatch when product/size is restocked.
     *
     * @param Product $product
     * @param array $oldSizeStocks Previous size stocks mapping (e.g. ['S' => 0, 'M' => 0])
     * @param int $oldTotalStock Previous total stock
     * @return int Number of customers processed
     */
    public static function handleProductRestocked(Product $product, array $oldSizeStocks = [], int $oldTotalStock = 0): int
    {
        // Must be approved and in-stock
        if ($product->status !== 'approved' || (int) $product->stock <= 0) {
            return 0;
        }

        // Must not belong to a frozen/suspended seller
        if ($product->seller && $product->seller->status === 'frozen') {
            return 0;
        }

        // Determine which sizes newly transitioned from 0 to >0
        $newSizeStocks = is_array($product->size_stocks) ? $product->size_stocks : [];
        $restockedSizes = [];

        if (!empty($newSizeStocks)) {
            foreach ($newSizeStocks as $sz => $qty) {
                $newQty = (int) $qty;
                $oldQty = isset($oldSizeStocks[$sz]) ? (int) $oldSizeStocks[$sz] : 0;
                // If it was 0 (or new) and now has stock
                if ($oldQty <= 0 && $newQty > 0) {
                    $restockedSizes[] = (string) $sz;
                }
            }
        }

        // If product has no size breakdown but total stock transitioned from 0 to >0
        $generalStockRestocked = ($oldTotalStock <= 0 && (int) $product->stock > 0);

        if (empty($restockedSizes) && !$generalStockRestocked) {
            // Also check if any size stock exists and is currently positive
            if (empty($newSizeStocks) && (int) $product->stock > 0) {
                $generalStockRestocked = true;
            } else {
                return 0;
            }
        }

        // Find all wishlists for this product
        $wishlistsQuery = Wishlist::where('product_id', $product->id)->with('user');

        // If specific sizes restocked, match either that specific size OR null size
        if (!empty($restockedSizes) && !$generalStockRestocked) {
            $wishlistsQuery->where(function($q) use ($restockedSizes) {
                $q->whereIn('size', $restockedSizes)->orWhereNull('size');
            });
        }

        $wishlists = $wishlistsQuery->get();

        if ($wishlists->isEmpty()) {
            return 0;
        }

        $processedCount = 0;
        $shopName = $product->seller ? ($product->seller->shopName ?: $product->seller->name ?: 'Lumban Heritage Shop') : 'Lumban Heritage Shop';
        $imageUrl = $product->getImageUrl();
        $fullImageUrl = str_starts_with($imageUrl, 'http') ? $imageUrl : url($imageUrl);

        foreach ($wishlists as $wishlist) {
            $user = $wishlist->user;
            if (!$user || $user->status !== 'active') {
                continue;
            }

            $targetSize = $wishlist->size;

            // If user didn't specify a size, but product has sizes, pick the first restocked size
            if (!$targetSize && !empty($restockedSizes)) {
                $targetSize = $restockedSizes[0];
            }

            // Verify the target size is indeed available
            $availableStock = (int) $product->stock;
            if ($targetSize && isset($newSizeStocks[$targetSize])) {
                $availableStock = (int) $newSizeStocks[$targetSize];
            }

            if ($availableStock <= 0) {
                continue;
            }

            // 1. Auto-add to user's cart in database
            $cart = [];
            if (!empty($user->cart)) {
                $decoded = json_decode($user->cart, true);
                if (is_array($decoded)) {
                    $cart = $decoded;
                }
            }

            $key = $product->id . '_' . ($targetSize ?? '') . '_';

            // If not already in cart, insert 1 unit
            if (!isset($cart[$key])) {
                $cart[$key] = [
                    'key'                 => $key,
                    'id'                  => $product->id,
                    'name'                => $product->name,
                    'price'               => $product->sale_price,
                    'image'               => $imageUrl,
                    'quantity'            => 1,
                    'size'                => $targetSize,
                    'variation'           => null,
                    'sellerId'            => $product->sellerId,
                    'shippingFee'         => $product->shippingFee ?? 0,
                    'original_price'      => $product->price,
                    'discount_percentage' => $product->discount_percentage,
                    'is_on_sale'          => $product->is_on_sale && ($product->discount_percentage > 0),
                    'category_name'       => $product->category->name ?? 'Traditional',
                    'shop_name'           => $shopName,
                ];

                $user->update(['cart' => json_encode($cart)]);
            }

            // 2. In-App Notification
            $sizeBadge = $targetSize ? " (Size {$targetSize})" : "";
            try {
                Notification::create([
                    'user_id'     => $user->id,
                    'title'       => '🎉 Wishlist Item Back in Stock',
                    'message'     => "\"{$product->name}\"{$sizeBadge} is back in stock and has been added to your shopping cart!",
                    'type'        => 'wishlist_restocked',
                    'target_url'  => '/cart',
                    'target_role' => 'customer',
                    'is_read'     => false,
                ]);
            } catch (\Throwable $ne) {
                Log::error("Failed to create in-app notification for restock: " . $ne->getMessage());
            }

            // 3. Email Notification via EmailNotificationService
            if ($user->email) {
                try {
                    $mailable = new WishlistRestockedMail(
                        $user->name ?: 'Valued Customer',
                        $product->name,
                        $shopName,
                        (float) $product->sale_price,
                        $targetSize,
                        $product->id,
                        $fullImageUrl
                    );

                    EmailNotificationService::sendNotification(
                        $user->email,
                        $mailable,
                        'wishlist_restocked',
                        $user->id,
                        'Product',
                        $product->id
                    );
                } catch (\Throwable $me) {
                    Log::error("Failed to send restock email to {$user->email}: " . $me->getMessage());
                }
            }

            $processedCount++;
        }

        return $processedCount;
    }
}

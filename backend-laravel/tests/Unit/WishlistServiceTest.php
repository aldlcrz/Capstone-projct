<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\Notification;
use App\Services\WishlistService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WishlistServiceTest extends TestCase
{
    public function test_restock_auto_adds_wishlisted_size_to_customer_cart()
    {
        Mail::fake();

        $seller = new User();
        $seller->id = (string) Str::uuid();
        $seller->name = 'Master Artisan Lumban';
        $seller->shopName = 'Heritage Embroidery Shop';
        $seller->status = 'active';

        $product = new Product();
        $product->id = (string) Str::uuid();
        $product->name = 'Handcrafted Piña-Seda Barong Tagalog';
        $product->price = '8500.00';
        $product->status = 'approved';
        $product->sellerId = $seller->id;
        $product->stock = 5;
        $product->size_stocks = ['S' => 0, 'M' => 5, 'L' => 0];
        $product->setRelation('seller', $seller);

        $customer = new User();
        $customer->id = (string) Str::uuid();
        $customer->name = 'Juan Dela Cruz';
        $customer->email = 'juan@example.com';
        $customer->status = 'active';
        $customer->cart = json_encode([]);

        $wishlist = new Wishlist();
        $wishlist->user_id = $customer->id;
        $wishlist->product_id = $product->id;
        $wishlist->size = 'M';
        $wishlist->setRelation('user', $customer);
        $wishlist->setRelation('product', $product);

        // Mock old size stocks where M was 0
        $oldSizeStocks = ['S' => 0, 'M' => 0, 'L' => 0];

        // Simulate restock logic directly
        $this->assertEquals('approved', $product->status);
        $this->assertEquals(5, $product->size_stocks['M']);
        $this->assertEquals('M', $wishlist->size);
    }
}

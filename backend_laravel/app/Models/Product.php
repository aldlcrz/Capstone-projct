<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sizes' => 'array',
            'categories' => 'array',
            'tags' => 'array',
            'image' => 'array',
            'price' => 'decimal:2',
            'costPerPiece' => 'decimal:2',
            'shippingFee' => 'decimal:2',
            'stock' => 'integer',
            'shippingDays' => 'integer',
            'views' => 'integer',
        ];
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'sellerId');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'productId');
    }

    public function productViews()
    {
        return $this->hasMany(ProductView::class, 'productId');
    }

    public function funnelEvents()
    {
        return $this->hasMany(SellerFunnelEvent::class, 'productId');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'productId');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SellerFunnelEvent extends Model
{
    use HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = null;

    protected $guarded = [];

    public function seller()
    {
        return $this->belongsTo(User::class, 'sellerId');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customerId');
    }
}

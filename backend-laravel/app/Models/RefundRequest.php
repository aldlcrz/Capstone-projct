<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    protected $table = 'refundrequests';
    protected $fillable = [
        'orderId', 'orderItemId', 'customerId', 'sellerId', 
        'reason', 'message', 'videoProof', 'status', 'sellerComment'
    ];
    public $timestamps = true;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
}

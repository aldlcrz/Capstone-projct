<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductView extends Model
{
    protected $table = 'product_views';
    protected $fillable = ['product_id', 'seller_id', 'customer_id', 'visitor_session_id', 'ip_address'];
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
}

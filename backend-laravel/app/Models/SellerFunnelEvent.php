<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerFunnelEvent extends Model
{
    protected $table = 'seller_funnel_events';
    protected $fillable = ['seller_id', 'product_id', 'customer_id', 'visitor_session_id', 'ip_address', 'event_type'];
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
}

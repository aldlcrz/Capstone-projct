<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderIdempotencyRecord extends Model
{
    use HasUuids;

    protected $table = 'order_idempotency_records';

    protected $fillable = [
        'id',
        'customer_id',
        'idempotency_key',
        'request_hash',
        'order_id',
        'status',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}

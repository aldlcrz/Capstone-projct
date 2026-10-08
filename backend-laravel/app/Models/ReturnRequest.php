<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReturnRequest extends Model
{
    use HasFactory;

    protected $table = 'returnrequests';
    public $incrementing = false;
    protected $keyType = 'string';

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'id',
        'orderId',
        'customer_id',
        'seller_id',
        'order_item_id',
        'reason',
        'proofImages',
        'status',
        'adminComment',
        'return_status',
        'physical_return_status',
        'refund_status',
        'dispute_status',
        'resolution_type',
        'requested_amount',
        'approved_amount',
        'seller_assessment',
        'seller_notes',
        'admin_decision',
        'admin_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'approved_amount'  => 'decimal:2',
            'resolved_at'      => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'orderId');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function evidences()
    {
        return $this->hasMany(ReturnRefundEvidence::class, 'return_request_id');
    }

    public function refundTransactions()
    {
        return $this->hasMany(RefundTransaction::class, 'return_request_id');
    }

    public function latestRefundTransaction()
    {
        return $this->hasOne(RefundTransaction::class, 'return_request_id')->latestOfMany();
    }
}

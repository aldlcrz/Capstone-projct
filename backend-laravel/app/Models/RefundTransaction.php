<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RefundTransaction extends Model
{
    use HasFactory;

    protected $table = 'refund_transactions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'return_request_id',
        'order_id',
        'payment_transaction_id',
        'payment_method',
        'refund_method',
        'refund_amount',
        'destination_account_encrypted',
        'destination_account_masked',
        'destination_account_name',
        'status',
        'transfer_reference',
        'transfer_proof_path',
        'processed_by',
        'processed_at',
        'failure_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:2',
            'processed_at'  => 'datetime',
            'destination_account_encrypted' => 'encrypted',
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

    public function returnRequest()
    {
        return $this->belongsTo(ReturnRequest::class, 'return_request_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}

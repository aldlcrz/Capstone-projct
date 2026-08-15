<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CommissionRecord extends Model
{
    protected $table = 'commission_records';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'sellerId', 'period', 'totalSales', 'commissionRate',
        'commissionAmount', 'status', 'dueDate', 'paidAt',
        'warningNotified', 'freezeNotified', 'notes',
        'paymentMethod', 'referenceNumber', 'paymentProof',
    ];

    protected function casts(): array
    {
        return [
            'totalSales'       => 'decimal:2',
            'commissionRate'   => 'decimal:2',
            'commissionAmount' => 'decimal:2',
            'dueDate'          => 'datetime',
            'paidAt'           => 'datetime',
            'warningNotified'  => 'boolean',
            'freezeNotified'   => 'boolean',
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

    public function seller()
    {
        return $this->belongsTo(User::class, 'sellerId');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SellerSubscription extends Model
{
    use HasFactory;

    protected $table = 'seller_subscriptions';

    protected $keyType = 'string';
    public $incrementing = false;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'id',
        'userId',
        'status',
        'planName',
        'amount',
        'paymentMethod',
        'paymentReference',
        'paymentProof',
        'rejectionReason',
        'startsAt',
        'endsAt',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'startsAt' => 'datetime',
        'endsAt' => 'datetime',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}

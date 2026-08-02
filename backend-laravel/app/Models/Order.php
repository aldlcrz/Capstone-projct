<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'customerId',
        'sellerId',
        'totalAmount',
        'status',
        'paymentMethod',
        'paymentReference',
        'paymentProof',
        'paymentStatus',
        'shippingAddress',
        'cancellationReason',
        'visitorSessionId',
    ];

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';

    /**
     * The names of the columns that should be used for the timestamps.
     */
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'totalAmount' => 'decimal:2',
            'shippingAddress' => 'array',
        ];
    }

    /**
     * Normalize shipping address data (handles legacy double-encoded JSON).
     */
    public function getNormalizedShippingAddressAttribute(): array
    {
        $address = $this->shippingAddress;

        if (is_string($address)) {
            $address = json_decode($address, true);
        }

        if (is_string($address)) {
            $address = json_decode($address, true);
        }

        return is_array($address) ? $address : [];
    }

    /**
     * Resolve payment status for display based on proof and order progress.
     */
    public function getResolvedPaymentStatusAttribute(): string
    {
        $paymentStatus = strtolower(trim((string) ($this->paymentStatus ?? '')));

        if (in_array($paymentStatus, ['paid', 'verified', 'confirmed'], true)) {
            return 'Paid';
        }

        if ($paymentStatus === 'failed') {
            return 'Failed';
        }

        if (
            $this->paymentProof
            || in_array(strtolower((string) $this->status), [
                'processing', 'to ship', 'shipped', 'to receive', 'delivered', 'completed',
            ], true)
        ) {
            return 'Paid';
        }

        return 'Pending';
    }

    /**
     * Get the items for the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'orderId');
    }

    /**
     * Get the customer that placed the order.
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customerId');
    }

    /**
     * Get the seller for the order.
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'sellerId');
    }

    /**
     * Get the reviews for the order.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'orderId');
    }
}

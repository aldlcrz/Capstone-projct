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
        'courierName',
        'trackingNumber',
        'trackingLink',
        'packingProof',
        'cancellationReason',
        'paymentRejectionReason',
        'visitorSessionId',
    ];

    /**
     * The accessors to append to the model's array and JSON form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'is_store_pickup',
        'is_special_delivery',
        'formatted_payment_method',
        'packing_proof_url',
        'payment_proof_url',
        'resolved_payment_status',
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
            'createdAt' => 'datetime',
            'updatedAt' => 'datetime',
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
     * Get the public URL for packing proof photo.
     */
    public function getPackingProofUrlAttribute(): ?string
    {
        if (empty($this->packingProof)) {
            return null;
        }

        $proof = trim($this->packingProof);

        if (str_starts_with($proof, 'http://') || str_starts_with($proof, 'https://')) {
            return $proof;
        }

        if (str_starts_with($proof, '/')) {
            return $proof;
        }

        if (file_exists(public_path('uploads/packing-proofs/' . basename($proof)))) {
            return asset('uploads/packing-proofs/' . basename($proof));
        }

        if (file_exists(public_path('uploads/' . $proof))) {
            return asset('uploads/' . $proof);
        }

        if (file_exists(public_path('storage/' . $proof))) {
            return asset('storage/' . $proof);
        }

        return asset('uploads/packing-proofs/' . basename($proof));
    }

    /**
     * Get the secure URL for customer payment receipt proof.
     */
    public function getPaymentProofUrlAttribute(): ?string
    {
        if (empty($this->paymentProof)) {
            return null;
        }

        $proof = trim($this->paymentProof);

        if (str_starts_with($proof, 'http://') || str_starts_with($proof, 'https://')) {
            return $proof;
        }

        return url('/orders/' . $this->id . '/payment-proof');
    }

    /**
     * Resolve payment status for display based on proof and order progress.
     */
    public function getResolvedPaymentStatusAttribute(): string
    {
        $paymentStatus = strtolower(trim((string) ($this->paymentStatus ?? '')));

        if (in_array($paymentStatus, ['verified', 'paid', 'confirmed'], true)) {
            return 'Verified';
        }

        if (in_array($paymentStatus, ['rejected', 'payment rejected', 'payment_rejected'], true)) {
            return 'Payment Rejected';
        }

        if ($paymentStatus === 'failed') {
            return 'Failed';
        }

        if (in_array(strtolower((string) $this->status), [
            'processing', 'to ship', 'ready_to_ship', 'shipped', 'to receive', 'in_transit', 'out_for_delivery', 'delivered', 'completed',
        ], true)) {
            return 'Verified';
        }

        if ($paymentStatus === 'payment submitted' || $paymentStatus === 'submitted' || !empty($this->paymentProof)) {
            return 'Payment Submitted';
        }

        return 'Pending Submission';
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

    public function shipping()
    {
        return $this->hasOne(OrderShipping::class, 'order_id');
    }

    public function getShippingFeeAttribute(): float
    {
        if ($this->relationLoaded('shipping')) {
            return (float) ($this->shipping?->shipping_fee ?? 0);
        }
        return (float) ($this->shipping()->value('shipping_fee') ?? 0);
    }

    /**
     * Get the reviews for the order.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'orderId');
    }

    /**
     * Get the status histories for the order.
     */
    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class, 'orderId')->orderBy('createdAt', 'asc');
    }

    /**
     * Get the return requests for the order.
     */
    public function returnRequests()
    {
        return $this->hasMany(ReturnRequest::class, 'orderId')->orderBy('createdAt', 'desc');
    }

    /**
     * Get all payment transaction attempts for this order.
     */
    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'order_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest payment transaction attempt for this order.
     */
    public function latestPaymentTransaction()
    {
        return $this->hasOne(PaymentTransaction::class, 'order_id')->latestOfMany('created_at');
    }

    /**
     * Check if this order is fulfilled / completed.
     */
    public function isCompleted(): bool
    {
        return \App\Support\OrderStatus::isCompleted($this->status);
    }

    /**
     * Check if this order is eligible for refund or return request.
     */
    public function isEligibleForReturnOrRefund(): bool
    {
        return \App\Support\OrderStatus::isEligibleForReturnOrRefund($this->status);
    }

    /**
     * Scope query to only include completed / delivered orders.
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', \App\Support\OrderStatus::completedStatuses());
    }

    /**
     * Scope query to only include active (non-cancelled) orders.
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['Cancelled', 'cancelled', 'cancellation pending', 'cancellation requested']);
    }

    /**
     * Check if this order uses Store Pickup / Workshop collection.
     */
    public function isStorePickup(): bool
    {
        if ($this->relationLoaded('shipping') && $this->shipping) {
            $pCode = strtolower((string) ($this->shipping->provider?->code ?? ''));
            $pName = strtolower((string) ($this->shipping->provider_name ?? ''));
            $pricingName = strtolower((string) ($this->shipping->pricing_provider_name ?? ''));
            $fulfillName = strtolower((string) ($this->shipping->fulfillment_provider_name ?? ''));

            if ($pCode === 'store_pickup' || 
                str_contains($pName, 'store pickup') || 
                str_contains($pName, 'in-shop') ||
                str_contains($pricingName, 'store pickup') || 
                str_contains($fulfillName, 'store pickup')) {
                return true;
            }
        } elseif (!$this->relationLoaded('shipping')) {
            $shipping = $this->shipping()->with('provider')->first();
            if ($shipping) {
                $pCode = strtolower((string) ($shipping->provider?->code ?? ''));
                $pName = strtolower((string) ($shipping->provider_name ?? ''));
                $pricingName = strtolower((string) ($shipping->pricing_provider_name ?? ''));
                $fulfillName = strtolower((string) ($shipping->fulfillment_provider_name ?? ''));

                if ($pCode === 'store_pickup' || 
                    str_contains($pName, 'store pickup') || 
                    str_contains($pName, 'in-shop') ||
                    str_contains($pricingName, 'store pickup') || 
                    str_contains($fulfillName, 'store pickup')) {
                    return true;
                }
            }
        }

        $courier = strtolower((string) ($this->courierName ?? ''));
        return str_contains($courier, 'store pickup') || str_contains($courier, 'in-shop');
    }

    /**
     * Accessor for is_store_pickup.
     */
    public function getIsStorePickupAttribute(): bool
    {
        return $this->isStorePickup();
    }

    /**
     * Check if this order uses Special Delivery / Local Artisan Rider delivery.
     */
    public function isSpecialDelivery(): bool
    {
        if ($this->relationLoaded('shipping') && $this->shipping) {
            $pCode = strtolower((string) ($this->shipping->provider?->code ?? ''));
            $pName = strtolower((string) ($this->shipping->provider_name ?? ''));
            $pricingName = strtolower((string) ($this->shipping->pricing_provider_name ?? ''));
            $fulfillName = strtolower((string) ($this->shipping->fulfillment_provider_name ?? ''));

            if ($pCode === 'seller_direct' || 
                $pCode === 'special_delivery' ||
                str_contains($pName, 'special delivery') || 
                str_contains($pName, 'local direct') || 
                str_contains($pName, 'artisan rider') ||
                str_contains($pName, 'seller direct') ||
                str_contains($pricingName, 'special delivery') || 
                str_contains($fulfillName, 'special delivery')) {
                return true;
            }
        } elseif (!$this->relationLoaded('shipping')) {
            $shipping = $this->shipping()->with('provider')->first();
            if ($shipping) {
                $pCode = strtolower((string) ($shipping->provider?->code ?? ''));
                $pName = strtolower((string) ($shipping->provider_name ?? ''));
                $pricingName = strtolower((string) ($shipping->pricing_provider_name ?? ''));
                $fulfillName = strtolower((string) ($shipping->fulfillment_provider_name ?? ''));

                if ($pCode === 'seller_direct' || 
                    $pCode === 'special_delivery' ||
                    str_contains($pName, 'special delivery') || 
                    str_contains($pName, 'local direct') || 
                    str_contains($pName, 'artisan rider') ||
                    str_contains($pName, 'seller direct') ||
                    str_contains($pricingName, 'special delivery') || 
                    str_contains($fulfillName, 'special delivery')) {
                    return true;
                }
            }
        }

        $courier = strtolower((string) ($this->courierName ?? ''));
        return str_contains($courier, 'special delivery') || 
               str_contains($courier, 'local direct') || 
               str_contains($courier, 'artisan rider') ||
               str_contains($courier, 'seller direct');
    }

    /**
     * Accessor for is_special_delivery.
     */
    public function getIsSpecialDeliveryAttribute(): bool
    {
        return $this->isSpecialDelivery();
    }

    /**
     * Accessor for formatted_payment_method.
     */
    public function getFormattedPaymentMethodAttribute(): string
    {
        $method = strtoupper(trim((string) ($this->paymentMethod ?? '')));

        if ($method === 'COD' || $method === '' || $method === 'CASH ON DELIVERY' || $method === 'PAY ON CLAIM' || $method === 'PAY IN SHOP') {
            if ($this->isStorePickup()) {
                return 'Pay in Shop';
            }
            if ($this->isSpecialDelivery()) {
                return 'Special Delivery (COD)';
            }
            return 'Cash on Delivery';
        }

        if ($method === 'GCASH') {
            return 'GCash';
        }

        if ($method === 'MAYA' || $method === 'PAYMAYA') {
            return 'Maya';
        }

        if ($method === 'CARD' || $method === 'CREDIT_CARD' || $method === 'DEBIT_CARD') {
            return 'Credit / Debit Card';
        }

        return $this->paymentMethod ?? 'Cash on Delivery';
    }
}

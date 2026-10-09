<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
        'total_verified_payments',
        'overpayment_amount',
        'refund_mobile_number',
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
        'sukli_refund_status',
        'authoritative_sukli_amount',
        'remaining_sukli_refund_amount',
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
            'total_verified_payments' => 'decimal:2',
            'overpayment_amount' => 'decimal:2',
            'shippingAddress' => 'array',
            'createdAt' => 'datetime',
            'updatedAt' => 'datetime',
        ];
    }

    /**
     * Check if this order has an overpayment requiring sukli refund.
     */
    public function isOverpaid(): bool
    {
        return (float) ($this->overpayment_amount ?? 0) > 0.0 || $this->authoritativeSukliAmount() > 0.0;
    }

    /**
     * Get the authoritative total payments received for this order across all payment transactions.
     */
    public function totalReceivedPayments(): float
    {
        $validStatuses = ['VERIFIED', 'UNVERIFIED', 'DETECTED', 'PENDING', 'AUTO_VERIFIED', 'COMPLETED'];
        if ($this->relationLoaded('paymentTransactions') && $this->paymentTransactions->isNotEmpty()) {
            $sum = (float) $this->paymentTransactions->whereIn('status', $validStatuses)->sum('detected_amount');
            if ($sum > 0) {
                return round($sum, 2);
            }
        } elseif ($this->paymentTransactions()->exists()) {
            $sum = (float) $this->paymentTransactions()->whereIn('status', $validStatuses)->sum('detected_amount');
            if ($sum > 0) {
                return round($sum, 2);
            }
        }

        if ((float) ($this->total_verified_payments ?? 0) > 0.0) {
            return round((float) $this->total_verified_payments, 2);
        }

        if ((float) ($this->overpayment_amount ?? 0) > 0.0) {
            return round((float) $this->totalAmount + (float) $this->overpayment_amount, 2);
        }

        return round((float) $this->totalAmount, 2);
    }

    /**
     * Calculate authoritative sukli amount based on server financial records.
     */
    public function authoritativeSukliAmount(): float
    {
        if ((float) ($this->overpayment_amount ?? 0) > 0.0) {
            return round((float) $this->overpayment_amount, 2);
        }

        $received = $this->totalReceivedPayments();
        $payable = (float) $this->totalAmount;

        return max(0.0, round($received - $payable, 2));
    }

    /**
     * Get the exact sukli amount to be refunded.
     */
    public function sukliAmount(): float
    {
        return $this->authoritativeSukliAmount();
    }

    /**
     * Sum of all disbursed / recorded sukli refunds.
     */
    public function sukliRefundedAmount(): float
    {
        if ($this->relationLoaded('refundTransactions')) {
            return (float) $this->refundTransactions
                ->whereIn('status', ['transferred', 'completed', 'refunded'])
                ->sum('refund_amount');
        }

        return (float) $this->refundTransactions()
            ->whereIn('status', ['transferred', 'completed', 'refunded'])
            ->sum('refund_amount');
    }

    /**
     * Calculate server-authoritative remaining unrefunded sukli amount.
     */
    public function remainingSukliRefundAmount(): float
    {
        return max(0.0, round($this->authoritativeSukliAmount() - $this->sukliRefundedAmount(), 2));
    }

    /**
     * Get the latest sukli refund transaction record.
     */
    public function latestSukliRefundTransaction()
    {
        if ($this->relationLoaded('refundTransactions')) {
            return $this->refundTransactions
                ->whereIn('status', ['transferred', 'completed', 'refunded', 'processing', 'pending'])
                ->first();
        }

        return $this->refundTransactions()
            ->whereIn('status', ['transferred', 'completed', 'refunded', 'processing', 'pending'])
            ->first();
    }

    /**
     * Determine distinct lifecycle stage for sukli overpayment:
     * - NOT_APPLICABLE: No overpayment on this order
     * - PENDING_VERIFICATION: Overpayment detected, awaiting Admin incoming payment approval
     * - OVERPAYMENT_PENDING_REFUND: Incoming payment verified, excess sukli awaiting refund disbursement
     * - REFUND_PROCESSING: Refund disbursement is initiated/in-progress
     * - REFUNDED: Excess sukli has been fully refunded & transfer recorded
     */
    public function sukliRefundStatus(): string
    {
        if (!$this->isOverpaid() && $this->authoritativeSukliAmount() <= 0) {
            return 'NOT_APPLICABLE';
        }

        $remaining = $this->remainingSukliRefundAmount();
        $refunded = $this->sukliRefundedAmount();

        if ($remaining <= 0.0 && $refunded > 0.0) {
            return 'REFUNDED';
        }

        $latestTx = $this->latestSukliRefundTransaction();
        if ($latestTx && in_array($latestTx->status, ['processing', 'pending'], true)) {
            return 'REFUND_PROCESSING';
        }

        $isPaymentVerified = in_array(strtolower($this->paymentStatus ?? ''), ['paid', 'verified'], true)
            || ($this->latestPaymentTransaction && $this->latestPaymentTransaction->status === 'VERIFIED');

        if ($isPaymentVerified) {
            return 'OVERPAYMENT_PENDING_REFUND';
        }

        return 'PENDING_VERIFICATION';
    }

    public function getSukliRefundStatusAttribute(): string
    {
        return $this->sukliRefundStatus();
    }

    public function getAuthoritativeSukliAmountAttribute(): float
    {
        return $this->authoritativeSukliAmount();
    }

    public function getRemainingSukliRefundAmountAttribute(): float
    {
        return $this->remainingSukliRefundAmount();
    }

    public function getDecryptedRefundMobileNumberAttribute(): ?string
    {
        $raw = $this->refund_mobile_number ?? ($this->refundMobileNumber ?? null);
        if (empty($raw)) {
            return null;
        }
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            return $raw;
        }
    }

    public function getMaskedRefundPhoneAttribute(): ?string
    {
        $phone = $this->decrypted_refund_mobile_number ?? ($this->customer?->mobileNumber ?? ($this->customer?->phone ?? null));
        if (empty($phone)) {
            return null;
        }
        $clean = preg_replace('/\s+/', '', (string) $phone);
        $len = strlen($clean);
        if ($len <= 4) {
            return $clean;
        }
        return substr($clean, 0, 4) . str_repeat('*', max(2, $len - 7)) . substr($clean, -3);
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
     * Get all refund transactions for this order.
     */
    public function refundTransactions()
    {
        return $this->hasMany(RefundTransaction::class, 'order_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get all seller payouts/settlements for this order.
     */
    public function sellerPayouts()
    {
        return $this->hasMany(SellerPayout::class, 'order_id');
    }

    /**
     * Get the primary seller payout for this order.
     */
    public function sellerPayout()
    {
        return $this->hasOne(SellerPayout::class, 'order_id');
    }

    /**
     * Calculate server-authoritative remaining refundable balance.
     */
    public function remainingRefundableAmount(): float
    {
        $paidAmount = (float) $this->totalAmount;
        if ($this->latestPaymentTransaction && $this->latestPaymentTransaction->status === 'VERIFIED') {
            $paidAmount = (float) ($this->latestPaymentTransaction->detected_amount ?: $this->totalAmount);
        }

        $alreadyRefunded = (float) $this->refundTransactions()
            ->whereIn('status', ['transferred', 'completed'])
            ->sum('refund_amount');

        return max(0.0, round($paidAmount - $alreadyRefunded, 2));
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

    /**
     * Scope a query to only include orders visible to the seller.
     * - GCash/Maya orders must be verified by Admin (Paid/Verified) before appearing to the seller.
     * - Unverified (Pending Verification) or Admin-Rejected GCash/Maya orders are hidden from the seller.
     * - COD and In-Shop cash orders are directly visible to the seller.
     */
    public function scopeVisibleToSeller($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($nonEwallet) {
                $nonEwallet->whereNotIn(DB::raw('UPPER(TRIM(COALESCE(paymentMethod, "")))'), ['GCASH', 'MAYA', 'PAYMAYA'])
                    ->where(function ($sub) {
                        $sub->whereNotIn('paymentStatus', ['Payment Rejected', 'Rejected'])
                            ->orWhereNull('paymentStatus');
                    });
            })
            ->orWhere(function ($ewallet) {
                $ewallet->whereIn(DB::raw('UPPER(TRIM(COALESCE(paymentMethod, "")))'), ['GCASH', 'MAYA', 'PAYMAYA'])
                    ->whereIn('paymentStatus', ['Paid', 'Verified', 'Paid (Verified)'])
                    ->whereNotIn('paymentStatus', ['Payment Rejected', 'Rejected', 'Pending Verification', 'Pending Verification (Overpayment)']);
            });
        });
    }
}

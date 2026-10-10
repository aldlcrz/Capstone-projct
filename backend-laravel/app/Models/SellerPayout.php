<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SellerPayout extends Model
{
    use HasFactory;

    public const STATUS_PENDING_ELIGIBILITY = 'PENDING_ELIGIBILITY';
    public const STATUS_AVAILABLE_FOR_PAYOUT = 'AVAILABLE_FOR_PAYOUT';
    public const STATUS_PAYOUT_PROCESSING = 'PAYOUT_PROCESSING';
    public const STATUS_PAID = 'PAID';
    public const STATUS_ON_HOLD = 'ON_HOLD';
    public const STATUS_PAYOUT_FAILED = 'PAYOUT_FAILED';

    protected $table = 'seller_payouts';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'seller_id',
        'gross_sales',
        'shipping_amount',
        'discount_amount',
        'commission_deducted',
        'commission_rate',
        'net_settlement_amount',
        'status',
        'payout_method',
        'payout_destination_account',
        'payout_destination_name',
        'transfer_reference',
        'transfer_proof_path',
        'hold_reason',
        'admin_notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'gross_sales'           => 'decimal:2',
        'shipping_amount'       => 'decimal:2',
        'discount_amount'       => 'decimal:2',
        'commission_deducted'   => 'decimal:2',
        'commission_rate'       => 'decimal:2',
        'net_settlement_amount' => 'decimal:2',
    ];

    public function getTransactionReferenceAttribute(): ?string
    {
        return $this->attributes['transfer_reference'] ?? null;
    }

    public function setTransactionReferenceAttribute(?string $value): void
    {
        $this->attributes['transfer_reference'] = $value;
    }

    public function getPaidAtAttribute(): ?\Carbon\Carbon
    {
        return $this->processed_at;
    }

    public function setPaidAtAttribute($value): void
    {
        $this->attributes['processed_at'] = $value;
    }

    protected function casts(): array
    {
        return [
            'gross_sales'           => 'decimal:2',
            'shipping_amount'       => 'decimal:2',
            'discount_amount'       => 'decimal:2',
            'commission_deducted'   => 'decimal:2',
            'net_settlement_amount' => 'decimal:2',
            'processed_at'          => 'datetime',
            'created_at'            => 'datetime',
            'updated_at'            => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'AVAILABLE_FOR_PAYOUT');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'PAID');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['PENDING_ELIGIBILITY', 'PAYOUT_PROCESSING']);
    }

    public function scopeOnHold(Builder $query): Builder
    {
        return $query->where('status', 'ON_HOLD');
    }

    public function getTransferProofUrlAttribute(): ?string
    {
        if (empty($this->transfer_proof_path)) {
            return null;
        }

        $proof = trim($this->transfer_proof_path);

        if (str_starts_with($proof, 'http://') || str_starts_with($proof, 'https://')) {
            return $proof;
        }

        if (str_starts_with($proof, '/')) {
            return $proof;
        }

        return asset('storage/' . $proof);
    }
}

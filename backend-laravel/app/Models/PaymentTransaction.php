<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $table = 'payment_transactions';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'customer_id',
        'seller_id',
        'reference_number',
        'active_reference',
        'wallet_type',
        'expected_amount',
        'detected_amount',
        'amount_confidence',
        'reference_confidence',
        'confidence',
        'status',
        'verification_tier',
        'receipt_path',
        'verified_at',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_amount'      => 'decimal:2',
            'detected_amount'      => 'decimal:2',
            'amount_confidence'    => 'float',
            'reference_confidence' => 'float',
            'confidence'           => 'float',
            'verified_at'          => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
            $model->syncActiveReference();
        });

        static::updating(function ($model) {
            $model->syncActiveReference();
        });
    }

    /**
     * Ensure active_reference is strictly kept for UNVERIFIED and VERIFIED states,
     * and released (null) for REJECTED and VOID states so references can be reused.
     */
    public function syncActiveReference(): void
    {
        $cleanRef = preg_replace('/\D/', '', (string) $this->reference_number);
        if (in_array($this->status, ['UNVERIFIED', 'VERIFIED'], true) && !empty($cleanRef)) {
            $this->active_reference = $cleanRef;
        } else {
            $this->active_reference = null;
        }
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['UNVERIFIED', 'VERIFIED']);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', 'VERIFIED');
    }

    public function scopeForReference(Builder $query, string $reference): Builder
    {
        $clean = preg_replace('/\D/', '', $reference);
        return $query->where('reference_number', $clean);
    }
}

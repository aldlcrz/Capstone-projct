<?php

namespace App\Models;

use App\Support\VariationFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'orderId',
        'productId',
        'product_name',
        'product_image',
        'quantity',
        'price',
        'size',
        'variation',
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
    protected $table = 'order_items';

    protected $appends = ['display_variation', 'image_url'];

    /**
     * Use custom timestamp column names to match the DB schema.
     */
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    public $timestamps = true;

    /**
     * Ensure product snapshot columns exist in the database table.
     */
    public static function ensureSnapshotColumnsExist(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('order_items')) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('order_items', 'product_name')) {
                    \Illuminate\Support\Facades\Schema::table('order_items', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('product_name')->nullable()->after('productId');
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('order_items', 'product_image')) {
                    \Illuminate\Support\Facades\Schema::table('order_items', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('product_image')->nullable()->after('product_name');
                    });
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Could not auto-add snapshot columns to order_items: ' . $e->getMessage());
        }
    }

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

            // Defensively ensure missing DB columns do not cause SQLSTATE[42S22] Column not found
            try {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('order_items', 'product_name')) {
                    unset($model->attributes['product_name']);
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('order_items', 'product_image')) {
                    unset($model->attributes['product_image']);
                }
            } catch (\Throwable $e) {
                // Ignore schema inspection failures
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
            'price' => 'decimal:2',
        ];
    }

    /**
     * Get the order that owns the item.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orderId');
    }

    /**
     * Get the product for the item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    public function getProductNameAttribute($value): string
    {
        return $value ?: ($this->product?->name ?? 'Heritage Piece');
    }

    public function getDisplayVariationAttribute(): ?string
    {
        return VariationFormatter::label($this->variation, $this->product?->image);
    }

    public function getImageUrlAttribute(): string
    {
        if (!empty($this->attributes['product_image'])) {
            return $this->attributes['product_image'];
        }
        return VariationFormatter::getImageForVariation($this->variation, $this->product)
            ?: ($this->product ? $this->product->getImageUrl() : asset('uploads/products/default.jpg'));
    }
}

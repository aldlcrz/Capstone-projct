<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id', 'name', 'description', 'price', 'costPerPiece', 'stock',
        'sizes', 'categories', 'image', 'shippingFee', 'shippingDays',
        'sellerId', 'status', 'rejectionReason', 'views',
        'sku', 'fabric_type', 'collar_type', 'artisan_region', 'CategoryId',
        'target_group', 'size_stocks',
        // Lumban Special discount
        'is_on_sale', 'discount_percentage',
        // Per-product payment overrides
        'is_gcash_available', 'gcash_number', 'gcash_qr_code',
        'is_maya_available',  'maya_number',  'maya_qr_code',
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
    protected $table = 'products';

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
            'price'               => 'decimal:2',
            'costPerPiece'        => 'decimal:2',
            'shippingFee'         => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'sizes'               => 'array',
            'categories'          => 'array',
            'image'               => 'array',
            'size_stocks'         => 'array',
            'is_on_sale'          => 'boolean',
            'is_gcash_available'  => 'boolean',
            'is_maya_available'   => 'boolean',
        ];
    }

    /**
     * Get the final sale price after discount.
     */
    public function getSalePriceAttribute(): float
    {
        if ($this->is_on_sale && $this->discount_percentage > 0) {
            return round($this->price * (1 - $this->discount_percentage / 100), 2);
        }
        return (float) $this->price;
    }

    /**
     * Get the artisan (seller) name.
     */
    public function getArtisanAttribute(): ?string
    {
        return $this->seller ? $this->seller->displayName : null;
    }

    /**
     * Get the seller that owns the product.
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'sellerId');
    }

    /**
     * Get the category that the product belongs to.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'CategoryId');
    }

    /**
     * Get the reviews for the product.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'productId');
    }

    /**
     * Get the resolved URL for a product image.
     */
    public function getImageUrl($image = null)
    {
        $img = $image ?? ($this->image[0] ?? null);
        if (!$img) {
            return asset('uploads/products/default.jpg');
        }
        if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
            return $img;
        }
        if (str_starts_with($img, 'products/')) {
            return asset('storage/' . $img);
        }
        if (str_starts_with($img, 'uploads/')) {
            return asset($img);
        }
        if (str_starts_with($img, '/uploads/')) {
            return asset($img);
        }
        return asset('uploads/products/' . $img);
    }
}

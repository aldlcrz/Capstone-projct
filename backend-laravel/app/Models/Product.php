<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $sellerId
 * @property string $name
 * @property string|null $description
 * @property string|float|int|null $price
 * @property string|float|int|null $costPerPiece
 * @property int $stock
 * @property string|float|int|null $shippingFee
 * @property int $shippingDays
 * @property string $status
 * @property array|null $sizes
 * @property array|null $categories
 * @property array|null $image
 * @property string|null $CategoryId
 * @property string|null $target_group
 * @property \Illuminate\Support\Carbon|\Carbon\CarbonInterface|string|null $createdAt
 * @property \Illuminate\Support\Carbon|\Carbon\CarbonInterface|string|null $updatedAt
 */
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
        // Product Variants / Variations
        'has_variants', 'variations',
        // Lumban Special discount / Lumbarong Seller Sales
        'is_on_sale', 'discount_percentage', 'sale_duration', 'sale_ends_at',
        // Per-product payment overrides
        'is_gcash_available', 'gcash_number', 'gcash_qr_code',
        'is_maya_available',  'maya_number',  'maya_qr_code',
        // Seller Custom Size Guide
        'size_guide_image', 'size_guide_measurements',
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
            'price'                   => 'decimal:2',
            'costPerPiece'            => 'decimal:2',
            'shippingFee'             => 'decimal:2',
            'discount_percentage'     => 'decimal:2',
            'sizes'                   => 'array',
            'categories'              => 'array',
            'image'                   => 'array',
            'size_stocks'             => 'array',
            'size_guide_measurements' => 'array',
            'variations'              => 'array',
            'has_variants'            => 'boolean',
            'is_on_sale'              => 'boolean',
            'sale_ends_at'            => 'datetime',
            'is_gcash_available'      => 'boolean',
            'is_maya_available'       => 'boolean',
        ];
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['image_url'];

    /**
     * Get the image_url appended attribute.
     */
    public function getImageUrlAttribute(): string
    {
        return $this->getImageUrl();
    }

    /**
     * Get the clean product name handling special characters like ñ.
     */
    public function getNameAttribute(?string $value): string
    {
        return str_replace(['Pi??a', 'Pi?a'], 'Piña', $value ?? '');
    }

    /**
     * Get the sizes attribute, filtering out any Custom size.
     */
    public function getSizesAttribute(array|string|null $value = null): array
    {
        if (is_null($value)) {
            return ['S', 'M', 'L', 'XL', 'XXL'];
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($decoded)) {
            return ['S', 'M', 'L', 'XL', 'XXL'];
        }

        $filtered = array_values(array_filter($decoded, function($sz) {
            $name = is_array($sz) ? ($sz['size'] ?? $sz['name'] ?? '') : $sz;
            return strtolower(trim((string)$name)) !== 'custom';
        }));

        return !empty($filtered) ? $filtered : ['S', 'M', 'L', 'XL', 'XXL'];
    }

    /**
     * Get the image attribute as an array of paths.
     */
    public function getImageAttribute(array|string|null $value = null): array
    {
        if (is_null($value)) {
            return ['uploads/products/default.jpg'];
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($decoded)) {
            $decoded = [$value];
        }

        $validImages = [];
        foreach ($decoded as $img) {
            if (!$img || $img === 'Array' || $img === '[]' || $img === '[') {
                continue;
            }
            $validImages[] = $img;
        }

        return !empty($validImages) ? $validImages : ['uploads/products/default.jpg'];
    }

    /**
     * Check if product sale is currently active.
     */
    public function isSaleActive(): bool
    {
        if (!$this->is_on_sale || (float)($this->discount_percentage ?? 0) <= 0) {
            return false;
        }
        if ($this->sale_ends_at && $this->sale_ends_at->isPast()) {
            return false;
        }
        return true;
    }

    /**
     * Get the final sale price after discount.
     */
    public function getSalePriceAttribute(): float
    {
        if ($this->isSaleActive()) {
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
        $img = $image;

        if (is_null($img)) {
            $raw = $this->getAttributes()['image'] ?? null;
            if (is_array($raw)) {
                $img = $raw[0] ?? null;
            } elseif (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && !empty($decoded)) {
                    $img = $decoded[0];
                } else {
                    $img = $raw;
                }
            }
            if (is_null($img)) {
                $imgArray = $this->image;
                $img = is_array($imgArray) ? ($imgArray[0] ?? null) : $imgArray;
            }
        }

        if (is_array($img)) {
            $img = $img[0] ?? null;
        }

        if (!$img || $img === 'Array' || $img === '[]' || $img === '[' || $img === 'products/default.jpg' || $img === 'uploads/products/default.jpg' || $img === 'default.jpg') {
            return '/uploads/products/default.jpg';
        }

        $img = str_replace('\\', '/', $img);
        $img = ltrim($img, '/');

        if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
            return $img;
        }

        // Canonical new storage paths: products/... or payments/...
        if (str_starts_with($img, 'products/') || str_starts_with($img, 'payments/')) {
            return '/storage/' . $img;
        }

        // Stored with storage/ prefix
        if (str_starts_with($img, 'storage/')) {
            return '/' . $img;
        }

        // Legacy path handling: check if explicitly stored under uploads/
        if (str_starts_with($img, 'uploads/')) {
            return '/' . $img;
        }

        // Fallback for bare legacy filenames
        return '/uploads/products/' . $img;
    }

    /**
     * Get the primary cover image URL.
     */
    public function getPrimaryImageUrl(): string
    {
        return $this->getImageUrl();
    }

    /**
     * Get all gallery photograph objects according to the canonical contract.
     *
     * @return array<int, array{url: string, path: string}>
     */
    public function getGalleryImages(): array
    {
        return \App\Support\VariationFormatter::buildGalleryImages($this->image, $this);
    }

    /**
     * Get all resolved URLs for all product variant images.
     */
    public function getAllImageUrls(): array
    {
        $raw = $this->getAttributes()['image'] ?? $this->image;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $images = is_array($decoded) ? $decoded : [$raw];
        } elseif (is_array($raw)) {
            $images = $raw;
        } else {
            $images = [];
        }

        $urls = [];
        foreach ($images as $img) {
            if ($img && $img !== 'Array' && $img !== '[]' && $img !== '[') {
                $url = $this->getImageUrl($img);
                if ($url && !in_array($url, $urls)) {
                    $urls[] = $url;
                }
            }
        }

        if (empty($urls)) {
            $urls[] = $this->getImageUrl();
        }

        return $urls;
    }

    /**
     * Get the resolved URL for the seller's custom size guide image.
     */
    public function getSizeGuideUrl(): ?string
    {
        if (!$this->size_guide_image) {
            return null;
        }

        $img = str_replace('\\', '/', $this->size_guide_image);
        $img = ltrim($img, '/');

        if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
            return $img;
        }

        if (str_starts_with($img, 'uploads/')) {
            return '/' . $img;
        }

        return '/storage/' . $img;
    }

    /**
     * Get the resolved URL for the product's GCash QR code.
     */
    public function getGcashQrUrl(): ?string
    {
        if (!$this->gcash_qr_code) {
            return null;
        }

        if (str_starts_with($this->gcash_qr_code, 'http://') || str_starts_with($this->gcash_qr_code, 'https://')) {
            return $this->gcash_qr_code;
        }

        $clean = ltrim(str_replace('\\', '/', $this->gcash_qr_code), '/');
        if (str_starts_with($clean, 'uploads/')) {
            return '/' . $clean;
        }

        return '/storage/' . $clean;
    }

    /**
     * Get the resolved URL for the product's Maya QR code.
     */
    public function getMayaQrUrl(): ?string
    {
        if (!$this->maya_qr_code) {
            return null;
        }

        if (str_starts_with($this->maya_qr_code, 'http://') || str_starts_with($this->maya_qr_code, 'https://')) {
            return $this->maya_qr_code;
        }

        $clean = ltrim(str_replace('\\', '/', $this->maya_qr_code), '/');
        if (str_starts_with($clean, 'uploads/')) {
            return '/' . $clean;
        }

        return '/storage/' . $clean;
    }
}

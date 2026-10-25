<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'target_group', 'size_stocks', 'color', 'colors',
        // Product Variants / Variations
        'has_variants', 'variations',
        // Lumban Special discount / Lumbarong Seller Sales
        'is_on_sale', 'discount_percentage', 'sale_duration', 'sale_ends_at',
        // Per-product payment overrides
        'is_gcash_available', 'gcash_number', 'gcash_qr_code',
        'is_maya_available',  'maya_number',  'maya_qr_code',
        // Seller Custom Size Guide
        'size_guide_image', 'size_guide_measurements',
        // Multi-provider Package Specs
        'package_weight_per_unit', 'package_length_per_unit',
        'package_width_per_unit', 'package_height_per_unit', 'handling_days',
        // Inventory Mode (available_stock, preorder)
        'inventory_mode',
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

        static::saved(function ($model) {
            \App\Services\ProductFilterService::clearPriceBoundsCache();
        });

        static::deleted(function ($model) {
            \App\Services\ProductFilterService::clearPriceBoundsCache();
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
            'sale_starts_at'          => 'datetime',
            'sale_ends_at'            => 'datetime',
            'is_gcash_available'      => 'boolean',
            'is_maya_available'       => 'boolean',
            'colors'                  => 'array',
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
            return [self::getPlaceholderImageUrl()];
        }

        $val = $value;
        while (is_string($val)) {
            $trimmed = trim($val);
            if ((str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) || (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"'))) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && !is_null($decoded)) {
                    $val = $decoded;
                    continue;
                }
            }
            break;
        }

        if (!is_array($val)) {
            $val = [$val];
        }

        $validImages = [];
        foreach ($val as $img) {
            while (is_string($img)) {
                $trimmed = trim($img);
                if ((str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) || (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"'))) {
                    $dec = json_decode($trimmed, true);
                    if (json_last_error() === JSON_ERROR_NONE && !is_null($dec)) {
                        $img = is_array($dec) ? ($dec[0] ?? null) : $dec;
                        continue;
                    }
                }
                break;
            }
            if (!$img || $img === 'Array' || $img === '[]' || $img === '[' || $img === '""') {
                continue;
            }
            $validImages[] = is_string($img) ? trim($img, "\"' ") : $img;
        }

        return !empty($validImages) ? $validImages : [self::getPlaceholderImageUrl()];
    }

    /**
     * Check if product sale is currently active.
     */
    public function isSaleActive(): bool
    {
        $rawOnSale = $this->attributes['is_on_sale'] ?? false;
        if (!$rawOnSale || (float)($this->discount_percentage ?? 0) <= 0) {
            return false;
        }
        if (!empty($this->sale_starts_at)) {
            $startsAt = $this->sale_starts_at instanceof \Carbon\CarbonInterface ? $this->sale_starts_at : \Carbon\Carbon::parse($this->sale_starts_at);
            if ($startsAt->isFuture()) {
                return false;
            }
        }
        if (!empty($this->sale_ends_at)) {
            $endsAt = $this->sale_ends_at instanceof \Carbon\CarbonInterface ? $this->sale_ends_at : \Carbon\Carbon::parse($this->sale_ends_at);
            if ($endsAt->isPast() || $endsAt->lte(now())) {
                return false;
            }
        }
        return true;
    }

    /**
     * Dynamically determine if the product is on sale based on active validity period.
     */
    public function getIsOnSaleAttribute($value): bool
    {
        return $this->isSaleActive();
    }

    /**
     * Scope query to products with active valid sales/discounts (Lumban Special).
     */
    public function scopeOnSale(Builder $query): Builder
    {
        return $query->where('is_on_sale', true)
            ->where('discount_percentage', '>', 0)
            ->where(function ($q) {
                $q->whereNull('sale_ends_at')
                  ->orWhere('sale_ends_at', '>', now());
            });
    }

    /**
     * Scope query for Lumban Special items.
     */
    public function scopeLumbanSpecial(Builder $query): Builder
    {
        return $this->scopeOnSale($query);
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
    public function getImageUrl($image = null): string
    {
        $img = $image;

        if (is_null($img)) {
            $raw = $this->getAttributes()['image'] ?? null;
            while (is_string($raw)) {
                $trimmed = trim($raw);
                if ((str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) || (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"'))) {
                    $decoded = json_decode($trimmed, true);
                    if (json_last_error() === JSON_ERROR_NONE && !is_null($decoded)) {
                        $raw = $decoded;
                        continue;
                    }
                }
                break;
            }
            if (is_array($raw)) {
                $img = !empty($raw) ? reset($raw) : null;
            } else {
                $img = $raw;
            }
            if (is_null($img)) {
                $imgArray = $this->image;
                $img = is_array($imgArray) ? ($imgArray[0] ?? null) : $imgArray;
            }
        }

        while (is_string($img)) {
            $trimmed = trim($img);
            if ((str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) || (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"'))) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && !is_null($decoded)) {
                    $img = is_array($decoded) ? ($decoded[0] ?? null) : $decoded;
                    continue;
                }
            }
            break;
        }

        if (is_array($img)) {
            $img = !empty($img) ? reset($img) : null;
        }

        if (!$img || $img === 'Array' || $img === '[]' || $img === '[' || $img === 'products/default.jpg' || $img === 'uploads/products/default.jpg' || $img === 'default.jpg' || str_contains((string)$img, 'placeholder') || str_contains((string)$img, '451e9610-2077-437e-a314-670d576e233c')) {
            // If primary image is missing, default, or references deleted photo, try falling back to the first variation's photo
            if (is_null($image) || str_contains((string)$image, '451e9610-2077-437e-a314-670d576e233c')) {
                $vars = is_array($this->variations) ? $this->variations : json_decode($this->variations ?? '[]', true);
                if (is_array($vars) && !empty($vars)) {
                    $firstVar = $vars[0] ?? null;
                    if (is_array($firstVar)) {
                        $fallbackImg = !empty($firstVar['image']) ? $firstVar['image'] : (!empty($firstVar['images'][0]) ? $firstVar['images'][0] : null);
                        if (!empty($fallbackImg) && !in_array($fallbackImg, ['products/default.jpg', 'default.jpg', 'uploads/products/default.jpg', 'Array', '[]', '['], true) && !str_contains((string)$fallbackImg, '451e9610-2077-437e-a314-670d576e233c')) {
                            return $this->getImageUrl($fallbackImg);
                        }
                    }
                }
            }
            return self::getPlaceholderImageUrl();
        }

        $img = trim((string)$img, "\"' \t\n\r\0\x0B");
        $img = str_replace('\\', '/', $img);
        $img = preg_replace('#/{2,}#', '/', $img);
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
     * Get the authoritative neutral placeholder image URL for products with missing/unassigned photos.
     */
    public static function getPlaceholderImageUrl(): string
    {
        return '/images/placeholder-product.svg';
    }

    /**
     * Memoized per-instance check for whether the primary cover image is missing on disk.
     */
    protected ?bool $cachedCoverMissing = null;

    /**
     * Determine if the primary cover image file is confirmed missing on storage.
     * Note: This performs an on-disk check, intended for single-item views (like seller/admin details)
     * and should NOT be called in large public loops.
     */
    public function hasMissingCoverImage(): bool
    {
        if ($this->cachedCoverMissing !== null) {
            return $this->cachedCoverMissing;
        }

        $raw = $this->getAttributes()['image'] ?? $this->image;
        if (empty($raw)) {
            return $this->cachedCoverMissing = true;
        }

        $first = is_array($raw) ? reset($raw) : $raw;
        if (is_string($first)) {
            $trimmed = trim($first);
            if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded) && !empty($decoded)) {
                    $first = reset($decoded);
                }
            }
        }

        if (empty($first) || !is_string($first)) {
            return $this->cachedCoverMissing = true;
        }

        $first = trim($first, "\"' \t\n\r\0\x0B");

        if (str_starts_with($first, 'http://') || str_starts_with($first, 'https://')) {
            return $this->cachedCoverMissing = false;
        }

        if (str_contains($first, 'default.jpg') || str_contains($first, 'placeholder')) {
            return $this->cachedCoverMissing = true;
        }

        $clean = ltrim(str_replace('\\', '/', $first), '/');

        // Check 1: public storage disk
        if (str_starts_with($clean, 'storage/')) {
            $cleanStorage = substr($clean, 8);
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($cleanStorage)) {
                return $this->cachedCoverMissing = false;
            }
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($clean)) {
            return $this->cachedCoverMissing = false;
        }

        // Check 2: direct public_path
        if (file_exists(public_path($clean))) {
            return $this->cachedCoverMissing = false;
        }

        // Check 3: storage_path app/public
        if (file_exists(storage_path('app/public/' . $clean))) {
            return $this->cachedCoverMissing = false;
        }

        return $this->cachedCoverMissing = true;
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

    /**
     * Check if product is in preorder inventory mode.
     */
    public function isPreorder(): bool
    {
        return ($this->inventory_mode ?? 'available_stock') === 'preorder';
    }

    /**
     * Check if product is in available stock inventory mode.
     */
    public function isAvailableStock(): bool
    {
        return ($this->inventory_mode ?? 'available_stock') === 'available_stock';
    }

    /**
     * Determine if product can be purchased.
     */
    public function isPurchasable(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        if ($this->isPreorder()) {
            return !empty($this->sizes);
        }

        return (int) $this->stock > 0;
    }

    /**
     * Scope query to only purchasable approved products.
     */
    public function scopePurchasable(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->where(function ($q) {
                $q->where('inventory_mode', 'preorder')
                    ->orWhere(function ($sub) {
                        $sub->where('inventory_mode', '!=', 'preorder')
                            ->where('stock', '>', 0);
                    })
                    ->orWhere(function ($sub) {
                        $sub->whereNull('inventory_mode')
                            ->where('stock', '>', 0);
                    });
            });
    }

    /**
     * Scope query to search products flexibly by name, description, category, tags, and audience.
     */
    public function scopeSearch(Builder $query, ?string $search, ?string $sort = null): Builder
    {
        return \App\Services\ProductSearchService::apply($query, $search, $sort);
    }

    /**
     * Get tailored badge styling classes for the garment color.
     */
    public function getColorBadgeClass(): string
    {
        $c = strtolower(trim((string)$this->color));
        return match (true) {
            str_contains($c, 'pink') || str_contains($c, 'rose') || str_contains($c, 'blush') => 'bg-pink-50 text-pink-700 border-pink-200',
            str_contains($c, 'blue') || str_contains($c, 'navy') || str_contains($c, 'cyan') => 'bg-sky-50 text-sky-800 border-sky-200',
            str_contains($c, 'green') || str_contains($c, 'emerald') || str_contains($c, 'sage') => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            str_contains($c, 'red') || str_contains($c, 'maroon') || str_contains($c, 'burgundy') => 'bg-rose-50 text-rose-800 border-rose-200',
            str_contains($c, 'black') || str_contains($c, 'charcoal') || str_contains($c, 'dark') => 'bg-gray-100 text-gray-900 border-gray-300',
            str_contains($c, 'white') || str_contains($c, 'ivory') => 'bg-gray-50 text-gray-700 border-gray-200',
            str_contains($c, 'gold') || str_contains($c, 'yellow') || str_contains($c, 'mustard') => 'bg-amber-50 text-amber-800 border-amber-200',
            str_contains($c, 'purple') || str_contains($c, 'violet') || str_contains($c, 'lavender') => 'bg-purple-50 text-purple-800 border-purple-200',
            str_contains($c, 'orange') || str_contains($c, 'rust') || str_contains($c, 'peach') => 'bg-orange-50 text-orange-800 border-orange-200',
            str_contains($c, 'multi') => 'bg-gradient-to-r from-pink-50 via-amber-50 to-sky-50 text-gray-800 border-gray-200',
            default => 'bg-stone-50 text-stone-800 border-stone-200', // Natural/Ecru/Beige/Cream
        };
    }

    /**
     * Get tailored dot styling classes for the garment color dot.
     */
    public function getColorDotClass(): string
    {
        $c = strtolower(trim((string)$this->color));
        return match (true) {
            str_contains($c, 'pink') || str_contains($c, 'rose') || str_contains($c, 'blush') => 'bg-pink-400',
            str_contains($c, 'navy') => 'bg-blue-900',
            str_contains($c, 'blue') || str_contains($c, 'cyan') => 'bg-sky-500',
            str_contains($c, 'emerald') => 'bg-emerald-600',
            str_contains($c, 'green') || str_contains($c, 'sage') => 'bg-emerald-500',
            str_contains($c, 'maroon') || str_contains($c, 'burgundy') => 'bg-rose-900',
            str_contains($c, 'red') => 'bg-rose-600',
            str_contains($c, 'black') || str_contains($c, 'charcoal') => 'bg-black',
            str_contains($c, 'white') || str_contains($c, 'ivory') => 'bg-white border border-gray-300',
            str_contains($c, 'gold') || str_contains($c, 'mustard') => 'bg-amber-500',
            str_contains($c, 'yellow') => 'bg-yellow-400',
            str_contains($c, 'purple') || str_contains($c, 'violet') => 'bg-purple-600',
            str_contains($c, 'lavender') => 'bg-purple-300',
            str_contains($c, 'orange') || str_contains($c, 'rust') => 'bg-orange-500',
            str_contains($c, 'peach') => 'bg-orange-300',
            str_contains($c, 'multi') => 'bg-gradient-to-tr from-pink-500 via-amber-400 to-sky-400',
            default => 'bg-[#D6C4A5]', // Natural / Ecru Piña tone
        };
    }
}


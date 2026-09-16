<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $table = 'banners';

    protected $fillable = [
        'userId',
        'image_path',
        'title',
        'subtitle',
        'button_text_1',
        'button_url_1',
        'button_text_2',
        'button_url_2',
        'order_index',
        'start_date',
        'end_date',
        'is_active',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_index' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Clean subtitle attribute to ensure no legacy placeholder text.
     */
    public function getSubtitleAttribute(?string $value): ?string
    {
        if ($value && stripos($value, 'macapagal') !== false) {
            return 'LumBarong Shop';
        }
        return $value;
    }

    /**
     * Scope query to only include banners that are active and currently within their scheduled window.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            });
    }

    /**
     * Check whether the banner is active and currently within its schedule window.
     */
    public function isCurrentlyLive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        if ($this->start_date && $this->start_date > $now) {
            return false;
        }
        if ($this->end_date && $this->end_date < $now) {
            return false;
        }

        return true;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    /**
     * Get the resolved URL for the banner image.
     */
    public function getImageUrl()
    {
        if (!$this->image_path) {
            return asset('uploads/banners/default.jpg');
        }
        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }
        if (str_starts_with($this->image_path, '/')) {
            return asset(ltrim($this->image_path, '/'));
        }
        if (str_starts_with($this->image_path, 'uploads/')) {
            return asset($this->image_path);
        }
        return asset('uploads/banners/' . $this->image_path);
    }

    /**
     * Resolve the first button URL intelligently.
     */
    public function getResolvedButtonUrl1()
    {
        if ($this->button_url_1 && $this->button_url_1 !== '#' && $this->button_url_1 !== '/') {
            return $this->button_url_1;
        }

        // If the banner is created by or belongs to a seller
        if ($this->userId) {
            return route('shops.show', ['id' => $this->userId]) . '#shop-catalogue';
        }

        return '#catalogue-section';
    }

    /**
     * Resolve the second button URL intelligently.
     */
    public function getResolvedButtonUrl2()
    {
        if ($this->button_url_2 && $this->button_url_2 !== '#' && $this->button_url_2 !== '/') {
            return $this->button_url_2;
        }

        // If banner has seller user attached
        if ($this->userId) {
            return route('shops.show', ['id' => $this->userId]);
        }

        // If subtitle or title mentions a seller/store name (e.g., 'MACAPAGAL')
        $possibleName = trim($this->subtitle ?: '');
        if ($possibleName && strlen($possibleName) <= 50) {
            $seller = User::where('role', 'seller')
                ->where(function($q) use ($possibleName) {
                    $q->where('shopName', 'like', '%' . $possibleName . '%')
                      ->orWhere('name', 'like', '%' . $possibleName . '%');
                })->first();

            if ($seller) {
                return route('shops.show', ['id' => $seller->id]);
            }

            return '/?search=' . urlencode($possibleName) . '#catalogue-section';
        }

        return '#catalogue-section';
    }


    /**
     * Determine the associated seller/shop ID for this banner.
     * Checks:
     * 1. Direct seller userId attribute
     * 2. Product ID in button_url_1 or button_url_2 (/products/{id})
     * 3. Shop ID in button_url_1 or button_url_2 (/shops/{id})
     * 4. Shop name matching subtitle or title
     */
    public function getAssociatedSellerId(): ?string
    {
        if ($this->userId) {
            return (string)$this->userId;
        }

        $urls = array_filter([$this->button_url_1, $this->button_url_2]);
        foreach ($urls as $url) {
            if (preg_match('#(?:/|^)products/([a-zA-Z0-9\-_]+)#i', $url, $m)) {
                $product = Product::find($m[1]);
                if ($product && $product->sellerId) {
                    return (string)$product->sellerId;
                }
            }

            if (preg_match('#(?:/|^)shops/([a-zA-Z0-9\-_]+)#i', $url, $m)) {
                return (string)$m[1];
            }
        }

        if ($this->subtitle) {
            $seller = User::where('role', 'seller')
                ->where(function ($q) {
                    $q->where('shopName', $this->subtitle)
                      ->orWhere('name', $this->subtitle);
                })->first();

            if ($seller) {
                return (string)$seller->id;
            }
        }

        return null;
    }

    /**
     * Find if another active banner already exists featuring/associated with the given seller ID.
     */
    public static function findActiveBannerForSeller(string|int $sellerId, string|int|null $excludeBannerId = null): ?self
    {
        $activeBanners = self::where('is_active', true)
            ->when($excludeBannerId, fn($q) => $q->where('id', '!=', $excludeBannerId))
            ->get();

        foreach ($activeBanners as $b) {
            if ((string)$b->getAssociatedSellerId() === (string)$sellerId) {
                return $b;
            }
        }

        return null;
    }
}



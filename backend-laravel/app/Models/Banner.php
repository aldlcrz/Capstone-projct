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
        'is_active',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_index' => 'integer',
    ];

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
}

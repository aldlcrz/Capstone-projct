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
        if (str_starts_with($this->image_path, 'uploads/')) {
            return asset($this->image_path);
        }
        if (str_starts_with($this->image_path, '/uploads/')) {
            return asset($this->image_path);
        }
        return asset('uploads/banners/' . $this->image_path);
    }
}

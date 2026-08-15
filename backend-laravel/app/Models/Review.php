<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Review extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'productId',
        'customerId',
        'orderId',
        'orderItemId',
        'rating',
        'comment',
        'images',
        'video',
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
    protected $table = 'reviews';

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
     * Get the product associated with the review.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    /**
     * Get the customer who wrote the review.
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customerId');
    }

    /**
     * Get parsed images list with absolute URLs.
     */
    public function getImagesListAttribute(): array
    {
        if (empty($this->images)) return [];
        $decoded = is_string($this->images) ? json_decode($this->images, true) : $this->images;
        if (!is_array($decoded)) return [];
        return array_values(array_map(function($img) {
            if (!$img) return null;
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) return $img;
            if (str_starts_with($img, '/')) return asset(ltrim($img, '/'));
            if (str_starts_with($img, 'uploads/') || str_starts_with($img, 'storage/')) return asset($img);
            if (str_starts_with($img, 'reviews/')) return asset('storage/' . $img);
            return asset('uploads/reviews/' . $img);
        }, array_filter($decoded)));
    }

    /**
     * Get full URL to video.
     */
    public function getVideoUrlAttribute(): ?string
    {
        if (empty($this->video)) return null;
        if (str_starts_with($this->video, 'http://') || str_starts_with($this->video, 'https://')) return $this->video;
        if (str_starts_with($this->video, '/')) return asset(ltrim($this->video, '/'));
        if (str_starts_with($this->video, 'uploads/') || str_starts_with($this->video, 'storage/')) return asset($this->video);
        return asset('uploads/reviews/videos/' . $this->video);
    }
}

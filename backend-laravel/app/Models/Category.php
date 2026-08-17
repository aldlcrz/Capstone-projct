<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'description',
        'parentId',
        'target_group',
        'image',
    ];

    /**
     * Get the full URL for the category image.
     */
    public function getImageUrl(): string
    {
        if (!empty($this->image)) {
            $img = $this->image;
            if (str_starts_with($img, 'http')) {
                return $img;
            }
            $clean = ltrim($img, '/');
            if (file_exists(public_path($clean))) {
                return '/' . $clean;
            }
            if (file_exists(public_path('uploads/categories/' . basename($clean)))) {
                return '/uploads/categories/' . basename($clean);
            }
            if (file_exists(storage_path('app/public/' . $clean))) {
                return '/storage/' . $clean;
            }
        }

        // Use first product's photo if category has products
        try {
            $firstProduct = $this->products()->first();
            if ($firstProduct && method_exists($firstProduct, 'getImageUrl')) {
                $pImg = $firstProduct->getImageUrl();
                if ($pImg && !str_contains($pImg, 'default.jpg')) {
                    return $pImg;
                }
            }
        } catch (\Throwable $e) {}

        // Intelligently map categories to distinct curated images based on name keywords
        $name = strtolower(trim($this->name ?? ''));

        if (str_contains($name, 'access') || str_contains($name, 'cufflink') || str_contains($name, 'jewelry') || str_contains($name, 'heritage')) {
            return '/uploads/categories/accessories.png';
        }
        if (str_contains($name, 'camisa') || str_contains($name, 'undershirt')) {
            return '/uploads/categories/camisa_undershirt.png';
        }
        if (str_contains($name, 'terno') || str_contains($name, 'modern top')) {
            return '/uploads/categories/women_terno.png';
        }
        if (str_contains($name, 'lady') || str_contains($name, 'blouse')) {
            return '/uploads/categories/women_lady_barong.png';
        }
        if (str_contains($name, 'filipiniana') || str_contains($name, 'gown') || str_contains($name, 'dress')) {
            return '/uploads/categories/women_filipiniana.png';
        }
        if (str_contains($name, 'girl')) {
            return '/uploads/categories/kids_girls.png';
        }
        if (str_contains($name, 'boy') || str_contains($name, 'kid')) {
            return '/uploads/categories/kids_boys.png';
        }
        if (str_contains($name, 'wedding') || str_contains($name, 'groom') || str_contains($name, 'special occasion')) {
            return '/uploads/categories/wedding_groom.png';
        }
        if (str_contains($name, 'pina') || str_contains($name, 'piña') || str_contains($name, 'formal')) {
            return '/uploads/categories/pina_formal.png';
        }
        if (str_contains($name, 'jusi') || str_contains($name, 'classic') || str_contains($name, 'lumban') || str_contains($name, 'traditional')) {
            return '/uploads/categories/jusi_classic.png';
        }
        if (str_contains($name, 'polo') || str_contains($name, 'casual') || str_contains($name, 'semi-formal') || str_contains($name, 'modern')) {
            return '/uploads/categories/polo_casual.png';
        }

        return '/uploads/categories/pina_formal.png';
    }

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
    protected $table = 'categories';

    /**
     * Disable timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

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
            'target_group' => 'array',
        ];
    }

    /**
     * Get the clean category name handling special characters like ñ.
     */
    public function getNameAttribute($value): string
    {
        return str_replace(['Pi??a', 'Pi?a'], 'Piña', $value ?? '');
    }

    /**
     * Get the products for the category.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'CategoryId');
    }

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parentId');
    }

    /**
     * Get the subcategories.
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parentId');
    }
}

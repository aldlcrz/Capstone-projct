<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id', 'name', 'email', 'password', 'role', 'isVerified', 'profilePhoto',
        'residencyCertificate', 'mobileNumber', 'gcashNumber', 'birDocument',
        'mayaNumber', 'mayaQrCode', 'gcashQrCode', 'isMayaAvailable', 'isGcashAvailable',
        'facebookLink', 'instagramLink', 'tiktokLink',
        'youtubeLink', 'socialLinks', 'shopHouseNo', 'shopStreet', 'shopAddress',
        'shopBarangay', 'shopCity', 'shopProvince', 'shopPostalCode', 'shopLatitude',
        'shopLongitude', 'isAdult', 'fcmToken', 'followers', 'following', 'status',
        'violationReason', 'rejectionReason', 'sessionVersion', 'googleId',
        'hasPasswordSet', 'loginAttempts', 'loginLockedUntil', 'bio', 'username',
        'gender', 'birthday', 'resetPasswordToken', 'resetPasswordExpires',
        'shopName', 'shopDescription', 'cancellation_policy', 'refund_policy', 'businessPermit', 'cart',
        'isPremium', 'premiumEndsAt', 'is_onboarded',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
    protected $table = 'users';

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
            'password'          => 'hashed',
            'isVerified'        => 'boolean',
            'isAdult'           => 'boolean',
            'hasPasswordSet'    => 'boolean',
            'followers'         => 'array',
            'following'         => 'array',
            'socialLinks'       => 'array',
            'loginLockedUntil'  => 'datetime',
            'isMayaAvailable'   => 'boolean',
            'isGcashAvailable'  => 'boolean',
            'createdAt'         => 'datetime',
            'updatedAt'         => 'datetime',
            'isPremium'         => 'boolean',
            'premiumEndsAt'     => 'datetime',
            'size_guides'       => 'array',
            'is_onboarded'      => 'boolean',
        ];
    }

    /**
     * Determine if user has completed or skipped initial profile onboarding.
     */
    public function isOnboarded(): bool
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_onboarded')) {
                return (bool) $this->is_onboarded;
            }
        } catch (\Throwable $e) {}
        return true;
    }

    // Relationships

    public function products()
    {
        return $this->hasMany(Product::class, 'sellerId');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'sellerId');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'userId')->orderByDesc('isDefault');
    }

    public function subscriptions()
    {
        return $this->hasMany(SellerSubscription::class, 'userId')->orderByDesc('createdAt');
    }

    /**
     * Check if the seller has an active premium subscription.
     * Integrates self-healing automatic expiry.
     */
    public function isPremiumActive(): bool
    {
        if ($this->isPremium && $this->premiumEndsAt && $this->premiumEndsAt->isPast()) {
            $this->isPremium = false;
            $this->save();
        }
        return (bool) $this->isPremium;
    }

    /**
     * Public-facing seller/shop name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->shopName ?: $this->name ?: 'Artisan';
    }

    /**
     * Resolved profile photo URL for display.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profilePhoto) {
            return null;
        }

        $photo = trim($this->profilePhoto);

        if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
            return $photo;
        }

        $clean = ltrim($photo, '/');

        if (str_starts_with($clean, 'uploads/') || str_starts_with($clean, 'storage/')) {
            return asset($clean);
        }

        if (file_exists(public_path('uploads/avatars/' . $clean))) {
            return asset('uploads/avatars/' . $clean);
        }

        if (file_exists(public_path('uploads/' . $clean))) {
            return asset('uploads/' . $clean);
        }

        if (file_exists(public_path('storage/' . $clean))) {
            return asset('storage/' . $clean);
        }

        return asset('uploads/' . $clean);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class, 'user_id');
    }

    /**
     * Get custom cancellation policy or sensible artisan default.
     */
    public function getCancellationPolicy(): string
    {
        return !empty($this->cancellation_policy)
            ? $this->cancellation_policy
            : "Cancellation requests must be submitted prior to order processing and payment verification. Once payment is confirmed and artisan crafting has begun, cancellations may not be accepted.";
    }

    /**
     * Get custom refund policy or sensible artisan default.
     */
    public function getRefundPolicy(): string
    {
        return !empty($this->refund_policy)
            ? $this->refund_policy
            : "Refund requests are subject to shop evaluation. Custom tailored garments are crafted to provided measurements. Damaged or defective items upon arrival may be submitted for review through our return system.";
    }
}

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
        'indigencyCertificate', 'validId', 'mobileNumber', 'gcashNumber', 'gcashQrCode',
        'mayaNumber', 'mayaQrCode', 'facebookLink', 'instagramLink', 'tiktokLink',
        'youtubeLink', 'socialLinks', 'shopHouseNo', 'shopStreet', 'shopAddress',
        'shopBarangay', 'shopCity', 'shopProvince', 'shopPostalCode', 'shopLatitude',
        'shopLongitude', 'isAdult', 'fcmToken', 'followers', 'following', 'status',
        'violationReason', 'rejectionReason', 'sessionVersion', 'googleId',
        'hasPasswordSet', 'loginAttempts', 'loginLockedUntil', 'bio', 'username',
        'gender', 'birthday', 'resetPasswordToken', 'resetPasswordExpires',
        'shopName', 'shopDescription', 'businessPermit',
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
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'isVerified'        => 'boolean',
            'isAdult'           => 'boolean',
            'hasPasswordSet'    => 'boolean',
            'followers'         => 'array',
            'following'         => 'array',
            'socialLinks'       => 'array',
            'loginLockedUntil'  => 'datetime',
        ];
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
}

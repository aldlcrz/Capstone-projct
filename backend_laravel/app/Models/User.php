<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'resetPasswordToken',
    ];

    protected function casts(): array
    {
        return [
            'isVerified' => 'boolean',
            'isAdult' => 'boolean',
            'hasPasswordSet' => 'boolean',
            'socialLinks' => 'array',
            'followers' => 'array',
            'following' => 'array',
            'birthday' => 'date',
            'resetPasswordExpires' => 'datetime',
            'passwordChangedAt' => 'datetime',
            'loginLockedUntil' => 'datetime',
        ];
    }

    // JWT Subject methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
        ];
    }

    // Relationships
    public function sellerProducts()
    {
        return $this->hasMany(Product::class, 'sellerId');
    }

    public function customerOrders()
    {
        return $this->hasMany(Order::class, 'customerId');
    }

    public function sellerOrders()
    {
        return $this->hasMany(Order::class, 'sellerId');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'userId');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'senderId');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiverId');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'userId');
    }

    public function customerRefunds()
    {
        return $this->hasMany(RefundRequest::class, 'customerId');
    }

    public function sellerRefunds()
    {
        return $this->hasMany(RefundRequest::class, 'sellerId');
    }

    public function filedReports()
    {
        return $this->hasMany(Report::class, 'reporterId');
    }

    public function receivedReports()
    {
        return $this->hasMany(Report::class, 'reportedId');
    }
}

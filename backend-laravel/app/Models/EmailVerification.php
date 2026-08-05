<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailVerification extends Model
{
    protected $table = 'email_verifications';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'email',
        'code',
        'type',
        'expires_at',
        'resend_count',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'   => 'datetime',
            'last_sent_at'  => 'datetime',
            'resend_count'  => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}

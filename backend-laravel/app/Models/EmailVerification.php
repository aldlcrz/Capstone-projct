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
        'failed_attempts',
        'last_sent_at',
        'resend_window_started_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'               => 'datetime',
            'last_sent_at'             => 'datetime',
            'resend_window_started_at' => 'datetime',
            'resend_count'             => 'integer',
            'failed_attempts'          => 'integer',
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
        return $this->expires_at ? $this->expires_at->isPast() : true;
    }

    /**
     * Check if 60-second resend cooldown is active.
     */
    public function isCooldownActive(int $cooldownSeconds = 60): bool
    {
        if (!$this->last_sent_at) {
            return false;
        }
        return $this->last_sent_at->diffInSeconds(now()) < $cooldownSeconds;
    }

    /**
     * Get remaining cooldown seconds before resending is permitted.
     */
    public function remainingCooldownSeconds(int $cooldownSeconds = 60): int
    {
        if (!$this->last_sent_at) {
            return 0;
        }
        $elapsed = $this->last_sent_at->diffInSeconds(now());
        return max(0, $cooldownSeconds - $elapsed);
    }

    /**
     * Check if 5 resends per rolling 1-hour window limit is reached.
     */
    public function isHourlyLimitReached(int $maxResends = 5): bool
    {
        if (!$this->resend_window_started_at) {
            return false;
        }
        $isWithinWindow = $this->resend_window_started_at->gt(now()->subHour());
        return $isWithinWindow && (int) $this->resend_count >= $maxResends;
    }

    /**
     * Remaining minutes until the 1-hour resend limit window resets.
     */
    public function remainingHourlyWaitMinutes(): int
    {
        if (!$this->resend_window_started_at) {
            return 0;
        }
        $windowExpiresAt = $this->resend_window_started_at->copy()->addHour();
        return max(1, now()->diffInMinutes($windowExpiresAt, false));
    }
}

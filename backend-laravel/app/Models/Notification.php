<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'userId',
        'user_id',
        'title',
        'message',
        'type',
        'link',
        'target_url',
        'isRead',
        'is_read',
        'is_dismissed',
        'targetRole',
        'target_role',
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
    protected $table = 'notifications';

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
            if (empty($model->attributes['userId']) && !empty($model->attributes['user_id'])) {
                $model->attributes['userId'] = $model->attributes['user_id'];
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
            'isRead' => 'boolean',
            'is_dismissed' => 'boolean',
            'createdAt' => 'datetime',
            'updatedAt' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    /**
     * Mutators for snake_case backwards compatibility
     */
    public function setUserIdAttribute(mixed $value)
    {
        if ($value !== null) {
            $this->attributes['userId'] = is_object($value) ? ($value->id ?? (string)$value) : (string)$value;
        }
    }

    public function setTargetUrlAttribute(mixed $value)
    {
        $this->attributes['link'] = $value;
    }

    public function setTargetRoleAttribute(mixed $value)
    {
        $this->attributes['targetRole'] = $value;
    }

    public function setIsReadAttribute(mixed $value)
    {
        $this->attributes['isRead'] = $value;
    }

    /**
     * Get the resolved target URL for this notification.
     */
    public function getTargetUrlAttribute(): string
    {
        $candidate = $this->attributes['link'] ?? null;
        if (!empty($candidate) && $candidate !== '#') {
            return $candidate;
        }

        $role = $this->targetRole ?? null;
        $type = $this->type ?? '';
        $text = strtolower(($this->title ?? '') . ' ' . ($this->message ?? ''));
        $isReturn = $type === 'return' || $type === 'refund' || str_contains($text, 'return') || str_contains($text, 'refund') || str_contains($text, 'dispute');
        $isOrder = in_array($type, ['order', 'order_status', 'delivery', 'special_delivery', 'pickup']) || str_contains($text, 'order');

        // Extract order ID if present (UUID or 8-char short hex)
        $orderId = null;
        if (preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', ($this->message ?? '') . ' ' . ($this->title ?? ''), $matches)) {
            $orderId = $matches[0];
        } elseif (preg_match('/(?:order|order #|#lb-or-|#lb-|#)\s*([0-9a-f]{8})/i', ($this->title ?? '') . ' ' . ($this->message ?? ''), $matches)) {
            $orderId = $matches[1];
        }

        if ($role === 'seller') {
            if ($isReturn) {
                return $orderId 
                    ? "/seller/orders?status=return+requests&order_id={$orderId}" 
                    : (\Illuminate\Support\Facades\Route::has('seller.orders') ? route('seller.orders', ['status' => 'return requests']) : '/seller/orders?status=return+requests');
            }
            if ($isOrder) {
                return $orderId 
                    ? "/seller/orders?order_id={$orderId}" 
                    : (\Illuminate\Support\Facades\Route::has('seller.orders') ? route('seller.orders') : '/seller/orders');
            }
            return \Illuminate\Support\Facades\Route::has('seller.dashboard') ? route('seller.dashboard') : '/seller/dashboard';
        }

        if ($role === 'admin') {
            if ($isReturn && \Illuminate\Support\Facades\Route::has('admin.returns.index')) {
                return route('admin.returns.index');
            }
            if ($isOrder && \Illuminate\Support\Facades\Route::has('admin.orders')) {
                return route('admin.orders');
            }
            return \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : '/admin/dashboard';
        }

        if ($role === 'superadmin') {
            if ($isOrder && \Illuminate\Support\Facades\Route::has('superadmin.orders')) {
                return route('superadmin.orders');
            }
            return \Illuminate\Support\Facades\Route::has('superadmin.dashboard') ? route('superadmin.dashboard') : '/superadmin/dashboard';
        }

        // Customer / default fallback
        if ($orderId) {
            return "/orders/{$orderId}";
        }

        if (\Illuminate\Support\Facades\Route::has('orders')) {
            return route('orders');
        }

        if (\Illuminate\Support\Facades\Route::has('profile.orders')) {
            return route('profile.orders');
        }

        return '/orders/my-orders';
    }

    /**
     * Send a notification to a specific user.
     */
    public static function send(mixed $userId, string $title, string $message, string $type = 'system', ?string $link = null, string $targetRole = 'customer')
    {
        $userIdStr = is_object($userId) ? ($userId->id ?? null) : $userId;
        if (empty($userIdStr)) {
            \Illuminate\Support\Facades\Log::warning('Notification::send skipped: empty userId', [
                'title' => $title,
                'type' => $type,
            ]);
            return null;
        }

        try {
            return self::create([
                'userId'     => (string) $userIdStr,
                'title'      => $title,
                'message'    => $message,
                'type'       => $type,
                'link'       => $link,
                'targetRole' => $targetRole,
                'isRead'     => false
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Notification send error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send a notification to all administrators.
     */
    public static function sendToAdmins(string $title, string $message, string $type = 'system', ?string $link = null)
    {
        try {
            $admins = \App\Models\User::whereIn('role', ['admin', 'superadmin'])->get();
            foreach ($admins as $admin) {
                self::create([
                    'userId' => $admin->id,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'link' => $link,
                    'targetRole' => 'admin',
                    'isRead' => false
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Notification sendToAdmins error: ' . $e->getMessage());
        }
    }
}

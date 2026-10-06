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
    public function setUserIdAttribute($value)
    {
        if ($value !== null) {
            $this->attributes['userId'] = is_object($value) ? ($value->id ?? (string)$value) : (string)$value;
        }
    }

    public function setTargetUrlAttribute($value)
    {
        $this->attributes['link'] = $value;
    }

    public function setTargetRoleAttribute($value)
    {
        $this->attributes['targetRole'] = $value;
    }

    public function setIsReadAttribute($value)
    {
        $this->attributes['isRead'] = $value;
    }

    /**
     * Send a notification to a specific user.
     */
    public static function send($userId, string $title, string $message, string $type = 'system', ?string $link = null, string $targetRole = 'customer')
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

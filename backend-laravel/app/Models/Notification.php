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
        'title',
        'message',
        'type',
        'link',
        'isRead',
        'targetRole',
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
     * Send a notification to a specific user.
     */
    public static function send(string $userId, string $title, string $message, string $type = 'system', ?string $link = null, string $targetRole = 'customer')
    {
        try {
            return self::create([
                'userId' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'link' => $link,
                'targetRole' => $targetRole,
                'isRead' => false
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
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                self::create([
                    'userId' => $admin->id,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'link' => $link,
                    'targetRole' => $role ?? 'admin', // use admin role
                    'isRead' => false
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Notification sendToAdmins error: ' . $e->getMessage());
        }
    }
}

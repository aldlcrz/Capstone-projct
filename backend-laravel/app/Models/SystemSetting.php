<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'systemsettings';
    protected $fillable = ['key', 'value'];
    public $timestamps = true;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    /**
     * Cast 'value' as JSON so Laravel automatically encodes on write
     * (satisfying MariaDB's JSON column CHECK constraint) and decodes on read.
     */
    protected $casts = [
        'value' => 'json',
    ];

    protected static function booted(): void
    {
        static::saved(function ($setting) {
            if (str_starts_with((string) ($setting->key ?? ''), 'maintenance')) {
                \App\Http\Middleware\CheckMaintenance::clearMaintenanceCache();
            }
        });

        static::deleted(function ($setting) {
            if (str_starts_with((string) ($setting->key ?? ''), 'maintenance')) {
                \App\Http\Middleware\CheckMaintenance::clearMaintenanceCache();
            }
        });
    }
}

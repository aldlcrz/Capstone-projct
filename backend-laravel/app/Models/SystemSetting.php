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
}

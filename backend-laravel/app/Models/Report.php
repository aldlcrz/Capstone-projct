<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Report extends Model
{
    protected $table = 'reports';
    protected $fillable = [
        'reporterId', 'reportedId', 'type', 'referenceId', 'reason', 
        'description', 'evidence', 'status', 'adminNotes', 'actionTaken'
    ];
    
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporterId');
    }

    public function reported()
    {
        return $this->belongsTo(User::class, 'reportedId');
    }
}

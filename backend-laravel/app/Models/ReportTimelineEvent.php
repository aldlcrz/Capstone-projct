<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReportTimelineEvent extends Model
{
    protected $table = 'report_timeline_events';

    protected $fillable = [
        'id',
        'report_id',
        'actor_id',
        'actor_role',
        'event_type',
        'title',
        'description',
        'metadata',
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function report()
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

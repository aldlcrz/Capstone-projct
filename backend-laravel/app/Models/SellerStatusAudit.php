<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SellerStatusAudit extends Model
{
    use HasFactory;

    protected $table = 'seller_status_audits';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'seller_id',
        'admin_id',
        'previous_status',
        'new_status',
        'reason',
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

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReturnRequest extends Model
{
    use HasFactory;

    protected $table = 'returnrequests';
    protected $fillable = ['id', 'orderId', 'reason', 'proofImages', 'status', 'adminComment'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('returnrequests')) {
                $this->setTable('returnrequests');
            } elseif (\Illuminate\Support\Facades\Schema::hasTable('return_requests')) {
                $this->setTable('return_requests');
            }
        } catch (\Throwable $e) {}
    }

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'orderId');
    }
}

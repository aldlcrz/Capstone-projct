<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReturnRefundEvidence extends Model
{
    use HasFactory;

    protected $table = 'return_refund_evidences';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'return_request_id',
        'uploaded_by',
        'type',
        'storage_path',
        'mime_type',
        'file_size',
        'checksum',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function returnRequest()
    {
        return $this->belongsTo(ReturnRequest::class, 'return_request_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

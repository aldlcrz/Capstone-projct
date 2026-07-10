<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            // Evidence is stored as a JSON string in DB, cast to array in Eloquent
            'evidence' => 'array',
        ];
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporterId');
    }

    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'reportedId');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $table = 'reports';
    protected $fillable = [
        'reporterId', 'reportedId', 'type', 'referenceId', 'reason', 
        'description', 'evidence', 'status', 'adminNotes', 'actionTaken'
    ];
    public $timestamps = true;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporterId');
    }

    public function reported()
    {
        return $this->belongsTo(User::class, 'reportedId');
    }
}

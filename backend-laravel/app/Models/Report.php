<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Report extends Model
{
    protected $table = 'reports';
    
    protected $fillable = [
        'reporterId', 'reportedId', 'type', 'reportType', 'productId', 'referenceId', 'reason', 
        'description', 'evidence', 'severity', 'status', 'adminNotes', 'investigationResult',
        'disciplinaryReason', 'sellerResponse', 'sellerResponseEvidence', 'sellerRespondedAt',
        'assignedAdminId', 'actionTaken'
    ];
    
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $casts = [
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime',
        'sellerRespondedAt' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
            if (empty($model->severity)) {
                $model->severity = 'MEDIUM';
            }
            if (empty($model->reportType)) {
                $model->reportType = !empty($model->productId) ? 'product' : 'account';
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

    public function product()
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assignedAdminId');
    }

    public function timelineEvents()
    {
        return $this->hasMany(ReportTimelineEvent::class, 'report_id')->orderBy('created_at', 'asc');
    }

    /**
     * Log a real event in the case timeline.
     */
    public function addTimelineEvent(string $type, string $title, ?string $description = null, ?User $actor = null, string $role = 'system', array $metadata = []): ?ReportTimelineEvent
    {
        try {
            return ReportTimelineEvent::create([
                'report_id'   => $this->id,
                'actor_id'    => $actor?->id,
                'actor_role'  => $actor ? ($actor->role ?? $role) : $role,
                'event_type'  => $type,
                'title'       => $title,
                'description' => $description,
                'metadata'    => $metadata,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to record timeline event for report {$this->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get clean 8-character display code (e.g. RPT-D84B4ED8)
     */
    public function getReportCode(): string
    {
        return 'RPT-' . strtoupper(substr($this->id, -8));
    }

    /**
     * Parse evidence into an array of URLs/paths.
     */
    public function getEvidenceList(): array
    {
        if (empty($this->evidence)) {
            return [];
        }

        // Check if JSON array
        if (str_starts_with(trim($this->evidence), '[') && str_ends_with(trim($this->evidence), ']')) {
            $decoded = json_decode($this->evidence, true);
            if (is_array($decoded)) {
                return array_filter($decoded);
            }
        }

        // Comma-separated or single string
        if (str_contains($this->evidence, ',')) {
            return array_map('trim', explode(',', $this->evidence));
        }

        return [trim($this->evidence)];
    }

    /**
     * Parse seller response evidence into array.
     */
    public function getSellerEvidenceList(): array
    {
        if (empty($this->sellerResponseEvidence)) {
            return [];
        }

        if (str_starts_with(trim($this->sellerResponseEvidence), '[') && str_ends_with(trim($this->sellerResponseEvidence), ']')) {
            $decoded = json_decode($this->sellerResponseEvidence, true);
            if (is_array($decoded)) {
                return array_filter($decoded);
            }
        }

        return [trim($this->sellerResponseEvidence)];
    }
}

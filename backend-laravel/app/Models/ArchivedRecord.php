<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ArchivedRecord extends Model
{
    use HasFactory;

    protected $table = 'archived_records';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'item_type',
        'item_id',
        'name',
        'identifier',
        'reason',
        'metadata',
        'archived_by',
    ];

    protected $casts = [
        'metadata' => 'array',
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

    /**
     * Archive an entity before deletion.
     */
    public static function archive(string $type, $entity, ?string $reason = null, ?string $archivedBy = null): self
    {
        $name = 'Unknown';
        $identifier = null;
        $metadata = [];
        $itemId = null;

        if ($entity instanceof Model) {
            $itemId = (string) $entity->getKey();
            $metadata = $entity->toArray();
        } elseif (is_array($entity)) {
            $itemId = $entity['id'] ?? null;
            $metadata = $entity;
        }

        switch ($type) {
            case 'product':
                $name = $entity->name ?? ($metadata['name'] ?? 'Untitled Product');
                $identifier = $entity->sku ?? ($metadata['sku'] ?? null);
                break;
            case 'category':
                $name = $entity->name ?? ($metadata['name'] ?? 'Untitled Category');
                $identifier = is_array($entity->target_group ?? null) ? implode(', ', $entity->target_group) : null;
                break;
            case 'customer':
                $name = $entity->name ?? ($metadata['name'] ?? 'Customer Account');
                $identifier = $entity->email ?? ($metadata['email'] ?? null);
                break;
            case 'seller':
                $name = $entity->shopName ?? $entity->name ?? ($metadata['shopName'] ?? $metadata['name'] ?? 'Artisan Seller');
                $identifier = $entity->email ?? ($metadata['email'] ?? null);
                break;
        }

        return self::create([
            'id'          => (string) Str::uuid(),
            'item_type'   => $type,
            'item_id'     => $itemId,
            'name'        => $name,
            'identifier'  => $identifier,
            'reason'      => $reason ?: 'Administrative deletion',
            'metadata'    => $metadata,
            'archived_by' => $archivedBy ?: (auth()->user()->name ?? 'Administrator'),
        ]);
    }
}

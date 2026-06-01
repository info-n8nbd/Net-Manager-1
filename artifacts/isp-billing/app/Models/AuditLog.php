<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TenantAware;

class AuditLog extends Model
{
    use TenantAware;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'entity_name',
        'before_values',
        'after_values',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected $casts = [
        'before_values' => 'array',
        'after_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForEntity($query, string $entityType, $entityId)
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public static function log(
        string $action,
        string $entityType,
        $entityId,
        ?string $entityName = null,
        ?array $before = null,
        ?array $after = null,
        ?string $notes = null
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'before_values' => $before,
            'after_values' => $after,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'notes' => $notes,
        ]);
    }

    public static function logCreate(string $entityType, $entityId, ?string $entityName = null, ?array $data = null): self
    {
        return self::log('create', $entityType, $entityId, $entityName, null, $data);
    }

    public static function logUpdate(string $entityType, $entityId, ?string $entityName = null, ?array $before = null, ?array $after = null): self
    {
        return self::log('update', $entityType, $entityId, $entityName, $before, $after);
    }

    public static function logDelete(string $entityType, $entityId, ?string $entityName = null, ?array $data = null): self
    {
        return self::log('delete', $entityType, $entityId, $entityName, $data, null);
    }

    public static function logAction(string $action, string $entityType, $entityId, ?string $entityName = null, ?array $data = null): self
    {
        return self::log($action, $entityType, $entityId, $entityName, null, $data);
    }
}

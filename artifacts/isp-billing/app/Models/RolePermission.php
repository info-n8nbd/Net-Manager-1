<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;
use App\Traits\TenantAware;

class RolePermission extends Model
{
    use TenantAware;

    protected $fillable = [
        'role_id',
        'permission_key',
        'is_granted',
        'granted_by',
        'granted_at',
        'revoked_by',
        'revoked_at',
        'reason',
    ];

    protected $casts = [
        'is_granted' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function systemPermission(): BelongsTo
    {
        return $this->belongsTo(SystemPermission::class, 'permission_key', 'key');
    }

    public function grantedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function revokedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function scopeGranted($query)
    {
        return $query->where('is_granted', true);
    }

    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }
}

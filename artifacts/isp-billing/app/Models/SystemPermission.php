<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemPermission extends Model
{
    protected $fillable = [
        'key',
        'type',
        'label',
        'parent_key',
        'entity',
        'action',
        'order',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    const TYPE_MENU = 'menu';
    const TYPE_ACTION = 'action';

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'permission_key', 'key');
    }

    public function children()
    {
        return SystemPermission::where('parent_key', $this->key)->orderBy('order')->get();
    }

    public function parent()
    {
        return $this->parent_key ? SystemPermission::where('key', $this->parent_key)->first() : null;
    }

    public function scopeMenus($query)
    {
        return $query->where('type', self::TYPE_MENU);
    }

    public function scopeActions($query)
    {
        return $query->where('type', self::TYPE_ACTION);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_key');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class MenuPermission extends Model
{
    use TenantAware;

    protected $fillable = ['role_id', 'menu_key', 'submenu_key', 'action_key', 'allowed'];

    protected $casts = [
        'allowed' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class);
    }
}

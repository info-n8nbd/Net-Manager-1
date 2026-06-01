<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class UserMenuPermission extends Model
{
    use TenantAware;

    protected $fillable = ['user_id', 'menu_key', 'submenu_key', 'action_key', 'override'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ManagerAssignment extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = [
        'user_id', 'branch_id', 'permissions', 'allowed_zones',
        'can_manage_billing', 'can_manage_clients', 'can_manage_staff',
        'can_view_reports', 'can_manage_resellers', 'is_active',
    ];

    protected $casts = [
        'permissions' => 'json',
        'allowed_zones' => 'json',
        'can_manage_billing' => 'boolean',
        'can_manage_clients' => 'boolean',
        'can_manage_staff' => 'boolean',
        'can_view_reports' => 'boolean',
        'can_manage_resellers' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}

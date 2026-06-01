<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantAware;

class Branch extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'name', 'code', 'address', 'phone', 'email', 'contact_person',
        'manager_id', 'is_active', 'is_main', 'settings', 'company_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_main' => 'boolean',
        'settings' => 'json',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function zones()
    {
        return $this->hasMany(Zone::class);
    }

    public function pops()
    {
        return $this->hasMany(Pop::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function managerAssignment()
    {
        return $this->hasOne(ManagerAssignment::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Department extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = ['name', 'description', 'is_active', 'company_id'];

    protected $casts = ['is_active' => 'boolean'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function designations()
    {
        return $this->hasMany(Designation::class);
    }
}

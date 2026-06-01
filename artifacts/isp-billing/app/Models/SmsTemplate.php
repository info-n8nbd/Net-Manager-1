<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class SmsTemplate extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = ['code', 'name', 'type', 'content', 'is_active', 'company_id'];

    protected $casts = ['is_active' => 'boolean'];
}

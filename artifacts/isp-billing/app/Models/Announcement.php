<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Announcement extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'title',
        'content',
        'target',
        'is_active',
        'start_date',
        'end_date',
        'created_by',
        'company_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTarget($query, $target)
    {
        return $query->where(function ($q) use ($target) {
            $q->where('target', 'all')
              ->orWhere('target', $target);
        });
    }
}

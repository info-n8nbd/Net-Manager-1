<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpRestriction extends Model
{
    protected $fillable = [
        'user_id',
        'role',
        'ip_address',
        'type',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

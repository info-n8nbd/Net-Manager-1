<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Task extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'title', 'description', 'due_date', 'priority', 'status',
        'assigned_to', 'created_by', 'completed_at', 'notes', 'company_id'
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

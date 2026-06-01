<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrityCheckLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'check_type',
        'status',
        'clients_checked',
        'bills_checked',
        'issues_found',
        'auto_corrected',
        'requires_review',
        'details',
        'duration_seconds',
        'run_by',
    ];

    protected $casts = [
        'clients_checked' => 'integer',
        'bills_checked' => 'integer',
        'issues_found' => 'integer',
        'auto_corrected' => 'integer',
        'requires_review' => 'integer',
        'duration_seconds' => 'float',
    ];

    public function runBy()
    {
        return $this->belongsTo(User::class, 'run_by');
    }

    public function getDetailsArrayAttribute(): array
    {
        return json_decode($this->details, true) ?? [];
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeWithIssues($query)
    {
        return $query->where('requires_review', '>', 0);
    }
}

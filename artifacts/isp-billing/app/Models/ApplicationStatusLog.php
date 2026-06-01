<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusLog extends Model
{
    protected $fillable = [
        'application_id',
        'from_status',
        'to_status',
        'note',
        'changed_by',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(NewConnectionApplication::class, 'application_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function getActionLabelAttribute(): string
    {
        if ($this->from_status === null) {
            return 'Application Created';
        }
        
        return match($this->to_status) {
            'submitted' => 'Submitted for Review',
            'under_review' => 'Taken Under Review',
            'need_correction' => 'Correction Requested',
            'approved' => 'Application Approved',
            'rejected' => 'Application Rejected',
            default => 'Status Changed to ' . ucfirst($this->to_status),
        };
    }
}

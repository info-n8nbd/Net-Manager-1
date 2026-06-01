<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Ticket extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'ticket_no', 'client_id', 'category_id', 'subject', 'description',
        'priority', 'status', 'assigned_to', 'technician_id', 'sla_deadline',
        'sla_breached', 'satisfaction_rating', 'satisfaction_feedback',
        'is_reopened', 'reopen_count', 'resolved_at', 'closed_at', 'metadata', 'company_id'
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'sla_breached' => 'boolean',
        'is_reopened' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($ticket) {
            if (empty($ticket->ticket_no)) {
                // Use a unique identifier to avoid race conditions
                $uniqueId = now()->format('YmdHis') . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
                $ticket->ticket_no = 'TKT-' . $uniqueId;
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function technicianAssignments()
    {
        return $this->hasMany(TechnicianAssignment::class);
    }

    public function feedback()
    {
        return $this->hasOne(TicketFeedback::class);
    }

    public function setSlaDeadline(): void
    {
        $slaConfig = TicketSlaConfig::getForPriority($this->priority);
        if ($slaConfig) {
            $this->sla_deadline = now()->addHours($slaConfig->resolution_hours);
            $this->save();
        }
    }

    public function checkSlaBreach(): bool
    {
        if ($this->sla_deadline && now()->isAfter($this->sla_deadline) && !$this->sla_breached) {
            $this->sla_breached = true;
            $this->save();
            return true;
        }
        return false;
    }

    public function reopen(): void
    {
        $this->status = 'open';
        $this->is_reopened = true;
        $this->reopen_count++;
        $this->resolved_at = null;
        $this->closed_at = null;
        $this->save();
    }
}

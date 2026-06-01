<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePerformance extends Model
{
    protected $fillable = [
        'employee_id', 'month', 'year', 'tickets_assigned', 'tickets_resolved',
        'avg_resolution_time', 'sla_met', 'sla_breached', 'collection_amount',
        'collection_count', 'rating_score', 'remarks'
    ];

    protected $casts = [
        'avg_resolution_time' => 'decimal:2',
        'collection_amount' => 'decimal:2',
        'rating_score' => 'decimal:2'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getSlaComplianceRate(): float
    {
        $total = $this->sla_met + $this->sla_breached;
        return $total > 0 ? ($this->sla_met / $total) * 100 : 0;
    }

    public function getResolutionRate(): float
    {
        return $this->tickets_assigned > 0
            ? ($this->tickets_resolved / $this->tickets_assigned) * 100
            : 0;
    }
}

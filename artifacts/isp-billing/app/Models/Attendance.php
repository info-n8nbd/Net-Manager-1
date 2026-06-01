<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Attendance extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = [
        'employee_id', 'attendance_date', 'check_in', 'check_out',
        'lunch_start', 'lunch_end', 'overtime_hours', 'late_minutes',
        'early_leave_minutes', 'total_work_hours', 'status', 'remarks',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'overtime_hours' => 'decimal:2',
        'total_work_hours' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getWorkDurationAttribute(): ?string
    {
        if (!$this->check_in || !$this->check_out) return null;
        $in = \Carbon\Carbon::parse($this->check_in);
        $out = \Carbon\Carbon::parse($this->check_out);
        $diff = $in->diff($out);
        return $diff->format('%H:%I');
    }

    public function getLunchDurationAttribute(): ?string
    {
        if (!$this->lunch_start || !$this->lunch_end) return null;
        $start = \Carbon\Carbon::parse($this->lunch_start);
        $end = \Carbon\Carbon::parse($this->lunch_end);
        $diff = $start->diff($end);
        return $diff->format('%H:%I');
    }

    public function getEffectiveHoursAttribute(): float
    {
        if (!$this->check_in || !$this->check_out) return 0;
        $in = \Carbon\Carbon::parse($this->check_in);
        $out = \Carbon\Carbon::parse($this->check_out);
        $totalMinutes = $in->diffInMinutes($out);

        if ($this->lunch_start && $this->lunch_end) {
            $lunchStart = \Carbon\Carbon::parse($this->lunch_start);
            $lunchEnd = \Carbon\Carbon::parse($this->lunch_end);
            $totalMinutes -= $lunchStart->diffInMinutes($lunchEnd);
        }

        return round($totalMinutes / 60, 2);
    }
}

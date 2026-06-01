<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Employee extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'user_id', 'employee_id', 'name', 'email', 'phone', 'address', 'nid',
        'nid_photo_front', 'nid_photo_back',
        'date_of_birth', 'gender', 'department_id', 'designation_id', 'joining_date',
        'basic_salary', 'bank_account', 'bank_name', 'status', 'photo', 'cv_file', 'zone_ids', 'company_id'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'basic_salary' => 'decimal:2',
        'zone_ids' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($employee) {
            if (empty($employee->employee_id)) {
                $companyId = $employee->company_id ?? auth()->user()?->company_id;
                $lastEmpId = static::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->max('employee_id');
                $nextNum = 1;
                if ($lastEmpId && preg_match('/EMP-(\d+)/', $lastEmpId, $matches)) {
                    $nextNum = intval($matches[1]) + 1;
                }
                $employee->employee_id = 'EMP-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class StaffSubmission extends Model
{
    use TenantAware;

    protected $fillable = [
        'submission_no',
        'staff_user_id',
        'amount',
        'submission_date',
        'payment_method',
        'remarks',
        'status',
        'received_by',
        'received_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'submission_date' => 'date',
        'received_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($submission) {
            if (empty($submission->submission_no)) {
                // Use a unique identifier to avoid race conditions
                $uniqueId = now()->format('YmdHis') . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
                $submission->submission_no = 'SUB-' . $uniqueId;
            }
        });
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'submission_id');
    }
}

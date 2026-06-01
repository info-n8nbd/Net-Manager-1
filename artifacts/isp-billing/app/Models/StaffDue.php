<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class StaffDue extends Model
{
    use TenantAware;

    protected $fillable = [
        'user_id',
        'company_id',
        'total_collected',
        'total_submitted',
        'current_due',
        'last_collection_date',
        'last_submission_date',
    ];

    protected $casts = [
        'total_collected' => 'decimal:2',
        'total_submitted' => 'decimal:2',
        'current_due' => 'decimal:2',
        'last_collection_date' => 'date',
        'last_submission_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

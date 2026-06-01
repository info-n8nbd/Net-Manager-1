<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Event extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'title', 'description', 'event_date', 'type', 'is_holiday',
        'reseller_id', 'created_by', 'company_id',
    ];

    protected $casts = [
        'event_date' => 'date',
        'is_holiday' => 'boolean',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

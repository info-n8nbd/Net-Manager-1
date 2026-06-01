<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageChangeRequest extends Model
{
    protected $fillable = [
        'client_id', 'current_package_id', 'requested_package_id', 'request_date',
        'effective_date', 'status', 'client_remarks', 'admin_remarks', 'approved_by'
    ];

    protected $casts = [
        'request_date' => 'date',
        'effective_date' => 'date'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function currentPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'current_package_id');
    }

    public function requestedPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'requested_package_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}

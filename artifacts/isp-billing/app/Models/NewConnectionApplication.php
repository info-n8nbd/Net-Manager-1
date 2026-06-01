<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\TenantAware;

class NewConnectionApplication extends Model
{
    use TenantAware;

    

    protected $fillable = [
        'application_number',
        'full_name_bangla',
        'full_name_english',
        'father_husband_name',
        'mother_name',
        'date_of_birth',
        'gender',
        'occupation',
        'mobile_number',
        'alternative_mobile',
        'email',
        'nid_number',
        'nid_front_image',
        'nid_back_image',
        'nid_verification_status',
        'division',
        'district',
        'thana_upazila',
        'district_id',
        'upazila_id',
        'union_ward',
        'zone_id',
        'sub_zone_id',
        'box_id',
        'house_road_floor',
        'latitude',
        'longitude',
        'nearby_landmark',
        'connection_type',
        'connection_medium',
        'package_id',
        'mikrotik_profile_name',
        'bandwidth_speed',
        'billing_cycle',
        'installation_charge',
        'signup_fee',
        'payment_method',
        'next_billing_date',
        'onu_required',
        'onu_model',
        'onu_serial',
        'olt_port',
        'olt_device_id',
        'router_required',
        'fiber_required',
        'cable_length_meters',
        'device_notes',
        'preferred_connection_date',
        'preferred_time_slot',
        'ip_type',
        'technician_id',
        'technician_assigned_area',
        'technician_note',
        'installation_feasibility',
        'house_owner_permission',
        'installation_place_photo',
        'customer_signature',
        'status',
        'admin_note',
        'rejection_reason',
        'correction_request',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'created_client_id',
        'mikrotik_username',
        'mikrotik_device_id',
        'mikrotik_created',
        'mikrotik_creation_log',
        'expire_date',
        'auto_inactive_day',
        'fiber_code', 'fiber_color', 'splitter_number', 'splitter_color', 'onu_mac',
        'fiber_vendor', 'fiber_core_type', 'onu_brand',
        'agent_id', 'commission_percent',
        'is_free', 'free_reason',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'expire_date' => 'date',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'onu_required' => 'boolean',
        'router_required' => 'boolean',
        'fiber_required' => 'boolean',
        'is_free' => 'boolean',
        'installation_feasibility' => 'boolean',
        'mikrotik_created' => 'boolean',
        'installation_charge' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function upazila(): BelongsTo
    {
        return $this->belongsTo(Upazila::class);
    }

    public function subZone(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Area::class, 'sub_zone_id');
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Box::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function mikrotikDevice(): BelongsTo
    {
        return $this->belongsTo(MikrotikDevice::class);
    }

    public function oltDevice(): BelongsTo
    {
        return $this->belongsTo(\App\Models\OltDevice::class, 'olt_device_id');
    }

    public function createdClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'created_client_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'application_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ApplicationStatusLog::class, 'application_id');
    }

    public function nidVerification(): HasOne
    {
        return $this->hasOne(NidVerification::class, 'application_id');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['submitted', 'under_review']);
    }

    public function scopeByTechnician($query, $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    public function scopeByZone($query, $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    public static function generateApplicationNumber(): string
    {
        $lastApp = static::withoutGlobalScopes()->orderBy('id', 'desc')->first();
        $sequence = $lastApp ? ($lastApp->id + 1) : 1;
        return 'APP-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function canBeEdited(bool $isAdmin = false): bool
    {
        if ($isAdmin) {
            return true;
        }
        return in_array($this->status, ['draft', 'need_correction']);
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this->status, ['draft', 'need_correction']);
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['submitted', 'under_review', 'need_correction']);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'draft' => 'badge-secondary',
            'submitted' => 'badge-info',
            'under_review' => 'badge-warning',
            'need_correction' => 'badge-danger',
            'approved' => 'badge-success',
            'rejected' => 'badge-dark',
            default => 'badge-secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Pending',
            'under_review' => 'Under Review',
            'need_correction' => 'Need Correction',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }
}

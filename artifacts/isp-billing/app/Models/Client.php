<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Client extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'user_id', 'client_id', 'name', 'last_name', 'profile_picture', 'father_husband_name', 'mother_name', 'date_of_birth', 'gender', 'email', 'phone', 'alt_phone', 
        'address', 'nearby_landmark', 'nid', 'nid_front_image', 'nid_back_image',
        'zone_id', 'area_id', 'package_id', 'username', 'pppoe_password', 'mac_address',
        'ip_address', 'connection_date', 'expire_date', 'billing_date', 'billing_cycle', 'monthly_fee',
        'installation_charge', 'cable_length_meters', 'onu_required', 'onu_source', 'router_required', 'fiber_required', 'preferred_payment_method',
        'permanent_discount', 'google_map_location', 'google_map_lat', 'google_map_lng',
        'mac_binding_enabled', 'custom_billing_cycle', 'balance', 'due_amount', 'status', 'connection_status', 
        'reseller_id', 'agent_id', 'remarks', 'technician_note', 'connection_type', 'connection_medium', 'uplink_device_id', 'signup_fee', 'signup_fee_paid',
        'payment_deadline', 'auto_disconnect_days', 'is_locked', 'lock_reason', 'lock_date',
        'queue_max_limit', 'queue_burst_limit', 'youtube_bandwidth', 'facebook_bandwidth',
        'auto_inactive_note', 'last_inactive_date', 'last_active_date', 'bill_calculation_type', 'application_id',
        'last_online', 'last_logout', 'session_uptime', 'session_ip', 'session_mac',
        'olt_device_id', 'olt_port', 'onu_serial', 'onu_model', 'onu_status',
        'onu_rx_power', 'onu_tx_power', 'onu_signal_quality', 'onu_down_reason',
        'onu_last_down', 'onu_last_check', 'onu_temperature', 'onu_firmware', 'onu_uptime_seconds',
        'mikrotik_device_id', 'mikrotik_queue_id', 'total_upload_bytes', 'total_download_bytes', 'bandwidth_last_updated',
        'mikrotik_profile_name', 'previous_mikrotik_profile',
        'box_id',
        'district_id', 'upazila_id',
        'deleted_reason', 'deleted_by', 'deleted_at_custom',
        'temp_deadline_date', 'temp_deadline_month', 'temp_deadline_reason',
        'temp_deadline_applied_by', 'temp_deadline_applied_at', 'sms_opt_out',
        'router_brand',
        'occupation',
        'is_free', 'free_reason', 'commission_percent',
        'client_type', 'custom_status', 'assigned_to', 'assigned_technicians', 'effective_to', 'non_effective_from',
        'last_status_change',
        'fiber_code', 'fiber_color', 'splitter_number', 'splitter_color', 'onu_mac',
        'fiber_vendor', 'fiber_core_type', 'onu_brand',
        'auto_renewal',
        'billing_starts_at',
        'provision_status',
        'provision_log',
        'provision_attempted_at',
        'ztp_enabled',
        'recharge_blocked',
    ];

    protected $casts = [
        'connection_date' => 'date',
        'expire_date' => 'date',
        'billing_starts_at' => 'date',
        'monthly_fee' => 'decimal:2',
        'balance' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'date_of_birth' => 'date',
        'last_online' => 'datetime',
        'last_logout' => 'datetime',
        'onu_last_down' => 'datetime',
        'onu_last_check' => 'datetime',
        'bandwidth_last_updated' => 'datetime',
        'is_free' => 'boolean',
        'auto_renewal' => 'boolean',
        'effective_to' => 'date',
        'non_effective_from' => 'date',
        'last_status_change' => 'datetime',
        'assigned_technicians' => 'array',
        'recharge_blocked' => 'boolean',
    ];

    public function getPppoeUsernameAttribute(): ?string
    {
        return $this->username;
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('exclude_deleted', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('clients.status', '!=', 'deleted');
        });

        static::saving(function ($client) {
            // is_free is set explicitly by the user (Free Client toggle)
            // Do NOT auto-set based on installation_charge — zero installation charge != free monthly client
            if ($client->is_free) {
                $client->due_amount = 0;
            }
        });

        static::creating(function ($client) {
            if (empty($client->company_id) && auth()->check()) {
                $client->company_id = auth()->user()->company_id;
            }
            if (empty($client->client_id)) {
                if ($client->reseller_id) {
                    $client->client_id = static::generateResellerClientId($client->reseller_id);
                } else {
                    $client->client_id = static::generateClientId($client->zone_id);
                }
            }
        });
    }

    public static function generateResellerClientId(int $resellerId): string
    {
        $reseller = \App\Models\Reseller::find($resellerId);
        $prefix = $reseller && $reseller->client_prefix ? $reseller->client_prefix : 'R' . $resellerId . '-';
        $serialStart = $reseller && $reseller->serial_start_from ? (int) $reseller->serial_start_from : 1;

        $maxNum = static::withoutGlobalScopes()
            ->where('reseller_id', $resellerId)
            ->whereRaw("client_id ~ '[0-9]+$'")
            ->selectRaw("MAX(CAST(SUBSTRING(client_id FROM '[0-9]+$') AS BIGINT)) as max_num")
            ->value('max_num') ?? 0;

        $nextId = max($serialStart, $maxNum + 1);
        $clientId = $prefix . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);

        while (static::withoutGlobalScopes()->where('client_id', $clientId)->exists()) {
            $nextId++;
            $clientId = $prefix . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
        }

        return $clientId;
    }

    public static function generateClientId($zoneId = null): string
    {
        $configuredStart = (int) SystemSetting::get('client_code_start', 1);
        $digits = (int) SystemSetting::get('client_code_digits', 5);

        // Resolve current company_id for scoping
        $companyId = null;
        if (auth()->check() && auth()->user()->company_id) {
            $companyId = auth()->user()->company_id;
        } elseif (\App\Services\TenantContext::get()) {
            $companyId = \App\Services\TenantContext::get()->id;
        }

        $baseQuery = static::withoutGlobalScopes()->whereNull('reseller_id');
        if ($companyId) {
            $baseQuery->where('company_id', $companyId);
        }

        $maxNumeric = (clone $baseQuery)->whereRaw("client_id ~ '^[0-9]+$'")
            ->selectRaw("MAX(CAST(client_id AS BIGINT)) as max_num")
            ->value('max_num') ?? 0;

        $maxFromPrefixed = (clone $baseQuery)->whereRaw("client_id ~ '[0-9]+$'")
            ->selectRaw("MAX(CAST(SUBSTRING(client_id FROM '[0-9]+$') AS BIGINT)) as max_num")
            ->value('max_num') ?? 0;

        $maxId = max($maxNumeric, $maxFromPrefixed);
        $nextId = max($configuredStart, $maxId + 1);

        $clientId = str_pad((string) $nextId, $digits, '0', STR_PAD_LEFT);

        // Uniqueness check scoped to current company
        $existsQuery = static::withoutGlobalScopes()->where('client_id', $clientId);
        if ($companyId) {
            $existsQuery->where('company_id', $companyId);
        }
        while ($existsQuery->exists()) {
            $nextId++;
            $clientId = str_pad((string) $nextId, $digits, '0', STR_PAD_LEFT);
            $existsQuery = static::withoutGlobalScopes()->where('client_id', $clientId);
            if ($companyId) {
                $existsQuery->where('company_id', $companyId);
            }
        }

        return $clientId;
    }

    public static function getLastGeneratedClientCode(): ?string
    {
        $companyId = null;
        if (auth()->check() && auth()->user()->company_id) {
            $companyId = auth()->user()->company_id;
        } elseif (\App\Services\TenantContext::get()) {
            $companyId = \App\Services\TenantContext::get()->id;
        }

        $query = static::withoutGlobalScopes()->whereRaw("client_id ~ '^[0-9]+$'");
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        return $query->orderByRaw("CAST(client_id AS BIGINT) DESC")->value('client_id');
    }

    public static function getLastGeneratedUsername(): ?string
    {
        return static::whereNotNull('username')
            ->where('username', '!=', '')
            ->orderBy('id', 'desc')
            ->value('username');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function box()
    {
        return $this->belongsTo(Box::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function upazila()
    {
        return $this->belongsTo(Upazila::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function resellerClient()
    {
        return $this->hasOne(\App\Models\ResellerClient::class, 'client_id');
    }

    public function agent()
    {
        return $this->belongsTo(\App\Models\Agent::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function mikrotikDevice()
    {
        return $this->belongsTo(\App\Models\MikrotikDevice::class, 'mikrotik_device_id');
    }

    public function oltDevice()
    {
        return $this->belongsTo(\App\Models\OltDevice::class, 'olt_device_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }

    public function application()
    {
        return $this->belongsTo(NewConnectionApplication::class, 'application_id');
    }

    public function serviceCharges()
    {
        return $this->hasMany(ClientServiceCharge::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function additionalPayments()
    {
        return $this->hasMany(ClientAdditionalPayment::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function documents()
    {
        return $this->hasMany(ClientDocument::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(ClientStatusLog::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ClientActivityLog::class)->orderBy('created_at', 'desc');
    }

    public function lineShifts()
    {
        return $this->hasMany(ClientLineShift::class);
    }

    public function reconnections()
    {
        return $this->hasMany(ClientReconnection::class);
    }

    public function packageChangeRequests()
    {
        return $this->hasMany(PackageChangeRequest::class);
    }

    public function productSerials()
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function advancePayments()
    {
        return $this->hasMany(AdvancePayment::class);
    }

    public function getEffectiveMonthlyFee(): float
    {
        return max(0, $this->monthly_fee - ($this->permanent_discount ?? 0));
    }

    /**
     * Get complete billing data using centralized BillingCalculatorService
     * This is the SINGLE SOURCE OF TRUTH for all billing calculations
     */
    public function getBillingData(): array
    {
        return \App\Services\BillingCalculatorService::getDisplayData($this);
    }
    
    /**
     * Get basic billing summary (without bill details)
     */
    public function getBillingSummary(): array
    {
        return \App\Services\BillingCalculatorService::calculate($this);
    }

    /**
     * Calculate due amount using Betternet's simple approach:
     * Due = Total Bills - Total Payments
     */
    public function calculateDueAmount(): float
    {
        return \App\Services\BillingCalculatorService::calculate($this)['due'];
    }

    /**
     * Get available advance (for backward compatibility)
     * In simplified system, this is negative due (overpayment)
     */
    public function getAvailableAdvance(): float
    {
        return \App\Services\BillingCalculatorService::calculate($this)['advance'];
    }

    /**
     * Get effective due - same as calculateDueAmount in simplified system
     */
    public function getEffectiveDue(): float
    {
        return $this->calculateDueAmount();
    }

    /**
     * Check if client has actual due
     */
    public function hasActualDue(): bool
    {
        return $this->calculateDueAmount() > 0;
    }

    /**
     * Sync due_amount and balance fields from calculated values
     */
    public function syncDueAmount(): void
    {
        \App\Services\BillingCalculatorService::syncClientCache($this);
    }
}

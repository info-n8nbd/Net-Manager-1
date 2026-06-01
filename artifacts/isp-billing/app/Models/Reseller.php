<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Reseller extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'user_id', 'reseller_id', 'name', 'company_name', 'email', 'phone',
        'address', 'area_name_en', 'area_name_bn', 'nid', 'type',
        'commission_rate', 'wallet_balance',
        'billing_type', 'credit_limit', 'over_due_limit', 'minimum_recharge_days',
        'auto_recharge', 'auto_bill_generate', 'recharge_enabled', 'recharge_day_type', 'single_recharge_day', 'flexible_min_days',
        'terminate_hour', 'terminate_minute',
        'serial_start_from', 'client_prefix', 'ppp_short_code', 'agent_id', 'referral_commission_percent',
        'thana', 'status', 'company_id', 'allowed_features',
        'raw_bandwidth', 'youtube_bandwidth', 'facebook_bandwidth', 'bandwidth_monthly_rate',
        'intl_rate',
        'ggc_bandwidth', 'ggc_rate',
        'fna_bandwidth', 'fna_rate',
        'cdn_bandwidth', 'cdn_rate',
        'bdix_bandwidth', 'bdix_rate',
        'cache_bandwidth', 'cache_rate',
        'terminated_at', 'termination_reason', 'mikrotik_device_id'
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'wallet_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'terminated_at' => 'datetime',
        'auto_recharge' => 'boolean',
        'auto_bill_generate' => 'boolean',
        'recharge_enabled' => 'boolean',
        'allowed_features' => 'array',
    ];

    /**
     * All available reseller panel features that admin can enable/disable per reseller.
     */
    public static array $availableFeatures = [
        'clients'         => 'View Clients',
        'client_create'   => 'Add New Clients',
        'client_edit'     => 'Edit Clients',
        'collection'      => 'Collect Payments',
        'billing'         => 'Generate Bills',
        'due_report'      => 'Due Report',
        'reports'         => 'Collection Report',
        'packages'        => 'View Assigned Packages',
        'packages_manage' => 'Manage Own Packages',
        'wallet'          => 'Wallet Recharge',
        'device_control'  => 'MikroTik Device Control',
        'zones'           => 'Manage Zones & Areas',
        'tickets'         => 'Support Tickets',
        'online_clients'  => 'View Online Clients',
        'network'         => 'Network / OLT View',
        'sub_resellers'   => 'Sub-Resellers',
        'sms'             => 'SMS Notifications',
        'recharge_correction' => 'Credited & Recharge Transactions',
        'recharge_reversal'   => 'Recharge Reversal',
        'client_delete_restore' => 'Delete & Restore Clients',
        // Bandwidth Reseller Portal Permissions
        'view_bw_amount'      => 'View Bandwidth Amounts (Mbps)',
        'view_bw_price'       => 'View Bandwidth Pricing & Rates',
        'view_billing_ledger' => 'View Billing Ledger',
        'view_adjustments'    => 'View Mid-Month Adjustments',
        'view_payments'       => 'View Recent Payments',
        'view_category_breakdown' => 'View Category Breakdown (Invoice Details)',
        'view_my_invoices'        => 'View My Invoices (Billing & Transactions)',
    ];

    /**
     * Default features enabled for a new reseller.
     */
    public static function defaultFeatures(): array
    {
        return ['clients', 'collection', 'billing', 'due_report', 'reports', 'packages', 'wallet'];
    }

    /**
     * Check if this reseller has a specific feature enabled.
     * If allowed_features is null (not configured), return true for all features (backward compat).
     */
    public function hasFeature(string $feature): bool
    {
        if ($this->allowed_features === null) {
            return true;
        }
        return in_array($feature, (array) $this->allowed_features);
    }

    /**
     * Get the list of enabled features (falls back to defaults if not set).
     */
    public function getEnabledFeatures(): array
    {
        return $this->allowed_features ?? static::defaultFeatures();
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($reseller) {
            if (empty($reseller->reseller_id)) {
                // Only consider RSL-XXXX format (digits only after prefix) to avoid
                // sub-reseller IDs like RSL-SUB-0001 or SR-00001 breaking the CAST
                $lastReseller = static::whereRaw("reseller_id ~ '^RSL-[0-9]+$'")
                    ->orderByRaw("CAST(SUBSTRING(reseller_id FROM 5) AS INTEGER) DESC")
                    ->first();
                $nextNumber = 1;
                if ($lastReseller && $lastReseller->reseller_id) {
                    $lastNumber = (int) substr($lastReseller->reseller_id, 4);
                    $nextNumber = $lastNumber + 1;
                }
                $reseller->reseller_id = 'RSL-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function packages()
    {
        return $this->hasMany(ResellerPackage::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(ResellerWalletTransaction::class);
    }

    public function mikrotikDevice()
    {
        return $this->belongsTo(MikrotikDevice::class, 'mikrotik_device_id');
    }

    public function creditWallet(float $amount, string $description, ?string $reference = null, ?int $createdBy = null): ResellerWalletTransaction
    {
        $this->wallet_balance += $amount;
        $this->save();

        return $this->walletTransactions()->create([
            'type' => 'credit',
            'amount' => $amount,
            'balance_after' => $this->wallet_balance,
            'description' => $description,
            'reference' => $reference,
            'created_by' => $createdBy
        ]);
    }

    public function debitWallet(float $amount, string $description, ?string $reference = null, ?int $createdBy = null): ?ResellerWalletTransaction
    {
        if ($this->billing_type === 'prepaid' && $this->wallet_balance < $amount) {
            return null;
        }

        $this->wallet_balance -= $amount;
        $this->save();

        return $this->walletTransactions()->create([
            'type' => 'debit',
            'amount' => $amount,
            'balance_after' => $this->wallet_balance,
            'description' => $description,
            'reference' => $reference,
            'created_by' => $createdBy
        ]);
    }

    public function canDebit(float $amount): bool
    {
        if ($this->billing_type === 'postpaid') {
            return ($this->wallet_balance + $this->credit_limit) >= $amount;
        }
        return $this->wallet_balance >= $amount;
    }

    public function parentReseller()
    {
        return $this->belongsTo(Reseller::class, 'parent_reseller_id');
    }

    public function subResellers()
    {
        return $this->hasMany(Reseller::class, 'parent_reseller_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function billingConfig()
    {
        return $this->hasOne(ResellerBillingConfig::class);
    }

    public function bills()
    {
        return $this->hasMany(ResellerBill::class);
    }

    public function clientRecharges()
    {
        return $this->hasMany(ResellerClientRecharge::class);
    }

    public function billPayments()
    {
        return $this->hasMany(ResellerBillPayment::class);
    }

    public function dailyBills()
    {
        return $this->hasMany(ResellerDailyBill::class);
    }

    public function isSubReseller()
    {
        return $this->reseller_level === 'sub_reseller';
    }

    /**
     * Bandwidth categories with labels, Mbps, rate and calculated subtotal.
     */
    public static array $bandwidthCategoryLabels = [
        'intl'  => 'International (Raw)',
        'ggc'   => 'GGC (Google Global Cache)',
        'fna'   => 'FNA (Facebook Network Appliance)',
        'cdn'   => 'CDN (Content Delivery Network)',
        'bdix'  => 'BDIX (Bangladesh Internet Exchange)',
        'cache' => 'Private Cache / Mirror',
    ];

    public function getBandwidthCategories(): array
    {
        $categories = [];

        $definitions = [
            'intl'  => ['bandwidth_field' => 'raw_bandwidth',   'rate_field' => 'intl_rate'],
            'ggc'   => ['bandwidth_field' => 'ggc_bandwidth',   'rate_field' => 'ggc_rate'],
            'fna'   => ['bandwidth_field' => 'fna_bandwidth',   'rate_field' => 'fna_rate'],
            'cdn'   => ['bandwidth_field' => 'cdn_bandwidth',   'rate_field' => 'cdn_rate'],
            'bdix'  => ['bandwidth_field' => 'bdix_bandwidth',  'rate_field' => 'bdix_rate'],
            'cache' => ['bandwidth_field' => 'cache_bandwidth', 'rate_field' => 'cache_rate'],
        ];

        foreach ($definitions as $key => $def) {
            $mbps = (float) ($this->{$def['bandwidth_field']} ?? 0);
            $rate = (float) ($this->{$def['rate_field']} ?? 0);
            if ($mbps > 0 || $rate > 0) {
                $categories[$key] = [
                    'label'    => static::$bandwidthCategoryLabels[$key],
                    'mbps'     => $mbps,
                    'rate'     => $rate,
                    'subtotal' => $mbps * $rate,
                ];
            }
        }

        // Include custom (non-system) bandwidth categories from DB allocations
        if ($this->id) {
            $customAllocations = \App\Models\ResellerBwAllocation::with('category')
                ->where('reseller_id', $this->id)
                ->get();
            foreach ($customAllocations as $alloc) {
                if ($alloc->category && ! $alloc->category->is_system) {
                    $mbps = (float) ($alloc->mbps ?? 0);
                    $rate = (float) ($alloc->rate ?? 0);
                    if ($mbps > 0 || $rate > 0) {
                        $categories['custom_' . $alloc->category_id] = [
                            'label'    => $alloc->category->label,
                            'mbps'     => $mbps,
                            'rate'     => $rate,
                            'subtotal' => $mbps * $rate,
                        ];
                    }
                }
            }
        }

        return $categories;
    }

    public function pop()
    {
        return $this->hasOne(Pop::class);
    }

    public function calculateBandwidthTotal(): float
    {
        return array_sum(array_column($this->getBandwidthCategories(), 'subtotal'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\ResellerPackage;
use App\Models\ResellerBill;
use App\Models\Client;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Models\MikrotikDevice;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellerController extends Controller
{
    public function index(Request $request)
    {
        // Default to MAC tab
        if (!request()->has("type") && !request()->has("status")) {
            return redirect()->route("admin.reseller.index", ["type" => "mac"]);
        }
        $statusFilter = $request->get('status', 'active');
        if ($statusFilter === 'terminated') {
            $statusFilter = 'suspended';
        }

        $inactiveStatuses = ['suspended', 'inactive'];

        $query = Reseller::withCount([
            'clients',
            'clients as active_clients_count' => function ($query) {
                $query->where('status', 'active');
            },
            'clients as inactive_clients_count' => function ($query) {
                $query->where('status', '!=', 'active');
            },
            'packages as packages_count',
        ])->with(['mikrotikDevice', 'agent']);

        if ($statusFilter === 'suspended') {
            $query->whereIn('status', $inactiveStatuses);
        } else {
            $query->where('status', 'active');
        }

        $typeFilter = $request->get('type', '');
        if (in_array($typeFilter, ['mac', 'bandwidth'])) {
            $query->where('type', $typeFilter);
        }

        $resellers = $query->orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20)->appends($request->query());

        // Batch-calculate financial data for displayed resellers (avoid N+1)
        $resellerIds = $resellers->pluck('id')->toArray();

        $totalRechargesByReseller = \App\Models\ResellerClientRecharge::whereIn('reseller_id', $resellerIds)
            ->where('transaction_type', 'recharge')
            ->groupBy('reseller_id')
            ->selectRaw('reseller_id, COALESCE(SUM(recharge_amount), 0) as total')
            ->pluck('total', 'reseller_id');

        $totalCurtailsByReseller = \App\Models\ResellerClientRecharge::whereIn('reseller_id', $resellerIds)
            ->whereIn('transaction_type', ['curtailed_with_benefits', 'recharge_refund', 'recharge_reversal'])
            ->groupBy('reseller_id')
            ->selectRaw('reseller_id, COALESCE(SUM(recharge_amount), 0) as total')
            ->pluck('total', 'reseller_id');

        $totalPaymentsByReseller = \App\Models\ResellerBillPayment::whereIn('reseller_id', $resellerIds)
            ->groupBy('reseller_id')
            ->selectRaw('reseller_id, COALESCE(SUM(amount), 0) as total')
            ->pluck('total', 'reseller_id');

        $totalDiscountsByReseller = \App\Models\ResellerBillPayment::whereIn('reseller_id', $resellerIds)
            ->groupBy('reseller_id')
            ->selectRaw('reseller_id, COALESCE(SUM(discount_amount), 0) as total')
            ->pluck('total', 'reseller_id');

        // Build per-reseller summary map
        // For bandwidth resellers: use ResellerBill (monthly rate-based billing)
        // For MAC resellers: use ResellerClientRecharge (per-client recharge billing)
        $bandwidthResellerIds = $resellers->where('type', 'bandwidth')->pluck('id')->toArray();
        $bwBillsByReseller = \App\Models\ResellerBill::whereIn('reseller_id', $bandwidthResellerIds)
            ->groupBy('reseller_id')
            ->selectRaw('reseller_id, COALESCE(SUM(total_recharge), 0) as total_billed, COALESCE(SUM(due_amount), 0) as total_due')
            ->get()->keyBy('reseller_id');

        $resellerFinancials = [];
        foreach ($resellerIds as $rid) {
            $reseller = $resellers->firstWhere('id', $rid);
            if ($reseller && $reseller->type === 'bandwidth') {
                // Bandwidth: calculate from ResellerBill records
                $bwData    = $bwBillsByReseller[$rid] ?? null;
                $billed    = $bwData ? (float) $bwData->total_billed : 0;
                $due       = $bwData ? (float) $bwData->total_due    : 0;
                $payments  = (float) ($totalPaymentsByReseller[$rid] ?? 0);
                $discounts = (float) ($totalDiscountsByReseller[$rid] ?? 0);
                $resellerFinancials[$rid] = [
                    'total_recharge' => $billed,
                    'due_to_admin'   => max(0, $due),
                ];
            } else {
                // MAC: calculate from ResellerClientRecharge records
                $recharge  = (float) ($totalRechargesByReseller[$rid] ?? 0);
                $curtails  = (float) ($totalCurtailsByReseller[$rid] ?? 0);
                $payments  = (float) ($totalPaymentsByReseller[$rid] ?? 0);
                $discounts = (float) ($totalDiscountsByReseller[$rid] ?? 0);
                $resellerFinancials[$rid] = [
                    'total_recharge' => $recharge,
                    'due_to_admin'   => max(0, $recharge - $curtails - $payments - $discounts),
                ];
            }
        }

        $allResellers = Reseller::withCount([
            'clients',
            'clients as active_clients_count' => function ($query) {
                $query->where('status', 'active');
            },
        ])->get();

        $totalResellers = $allResellers->count();
        $activeResellers = $allResellers->where('status', 'active')->count();
        $terminatedResellers = $allResellers->whereIn('status', ['suspended', 'inactive'])->count();
        // Filter stats by active tab type
        $statsResellers = $typeFilter ? $allResellers->where("type", $typeFilter) : $allResellers;
        $totalClients = $statsResellers->sum("clients_count");
        $totalActiveClients = $statsResellers->sum('active_clients_count');
        $totalInactiveClients = $totalClients - $totalActiveClients;
        $totalWalletBalance = $statsResellers->sum('wallet_balance');

        // Global due = sum of all reseller dues to admin (calculated live)
        $allResellerIds = $allResellers->pluck('id')->toArray();
        $allBandwidthIds = $allResellers->where('type', 'bandwidth')->pluck('id')->toArray();
        $allMacIds       = $allResellers->where('type', '!=', 'bandwidth')->pluck('id')->toArray();

        // MAC resellers: due from ResellerClientRecharge
        $allTotalRecharges = (float) \App\Models\ResellerClientRecharge::whereIn('reseller_id', $allMacIds)->where('transaction_type', 'recharge')->sum('recharge_amount');
        $allTotalCurtails  = (float) \App\Models\ResellerClientRecharge::whereIn('reseller_id', $allMacIds)->whereIn('transaction_type', ['curtailed_with_benefits', 'recharge_refund', 'recharge_reversal'])->sum('recharge_amount');
        $allTotalMacPayments  = (float) \App\Models\ResellerBillPayment::whereIn('reseller_id', $allMacIds)->sum('amount');
        $allTotalMacDiscounts = (float) \App\Models\ResellerBillPayment::whereIn('reseller_id', $allMacIds)->sum('discount_amount');
        $macDue = max(0, $allTotalRecharges - $allTotalCurtails - $allTotalMacPayments - $allTotalMacDiscounts);

        // Bandwidth resellers: due from ResellerBill
        $bwTotalDue = (float) \App\Models\ResellerBill::whereIn('reseller_id', $allBandwidthIds)->sum('due_amount');

        $totalDue = $macDue + max(0, $bwTotalDue);
        $allTotalPayments  = (float) \App\Models\ResellerBillPayment::whereIn('reseller_id', $allResellerIds)->sum('amount');
        $allTotalDiscounts = (float) \App\Models\ResellerBillPayment::whereIn('reseller_id', $allResellerIds)->sum('discount_amount');

        $totalCollection = Payment::whereIn('client_id', Client::whereNotNull('reseller_id')->pluck('id'))->sum('amount');
        $prepaidCount    = $allResellers->where('billing_type', 'prepaid')->count();
        $postpaidCount   = $allResellers->where('billing_type', 'postpaid')->count();
        $macCount        = $allResellers->where('type', 'mac')->count();
        $bandwidthCount  = $allResellers->where('type', 'bandwidth')->count();

        // Tab-specific due amount for summary bar
        $tabTotalDue = $typeFilter === 'bandwidth' ? max(0, $bwTotalDue)
                     : ($typeFilter === 'mac' ? $macDue : $totalDue);

        // Custom BW categories and their allocations for the displayed resellers
        $customDbCategories = \App\Models\BandwidthCategory::where('is_system', false)
            ->orderBy('sort_order')->orderBy('id')->get();
        $customAllocsByReseller = $customDbCategories->isNotEmpty()
            ? \App\Models\ResellerBwAllocation::whereIn('reseller_id', $resellerIds)
                ->whereIn('category_id', $customDbCategories->pluck('id'))
                ->where('mbps', '>', 0)
                ->get()
                ->groupBy('reseller_id')
            : collect();

        return view('admin.reseller.index', compact(
            'resellers', 'totalResellers', 'activeResellers', 'terminatedResellers', 'totalClients',
            'totalActiveClients', 'totalInactiveClients', 'totalWalletBalance', 'totalDue', 'tabTotalDue', 'totalCollection',
            'prepaidCount', 'postpaidCount', 'macCount', 'bandwidthCount', 'statusFilter', 'typeFilter', 'resellerFinancials',
            'customDbCategories', 'customAllocsByReseller'
        ));
    }

    public function create()
    {
        $packages = Package::where('is_active', true)->get();
        $mikrotikDevices = MikrotikDevice::where('is_active', true)->orderBy('name')->get();
        $agents = \App\Models\Agent::where('status', 'active')->orderBy('name')->get();
        $initialType = in_array(request('type'), ['mac', 'bandwidth']) ? request('type') : 'mac';
        $customDbCategories = \App\Models\BandwidthCategory::where('is_system', false)->orderBy('sort_order')->orderBy('id')->get();
        $availableFeatures = Reseller::$availableFeatures;
        return view('admin.reseller.create', compact('packages', 'mikrotikDevices', 'agents', 'initialType', 'customDbCategories', 'availableFeatures'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:100|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|string|max:20',
            'type' => 'required|in:mac,bandwidth',
            'billing_type' => 'nullable|in:postpaid,prepaid',
            'mikrotik_device_id' => 'nullable|exists:mikrotik_devices,id',
            'recharge_day_type' => 'nullable|in:single,multi,flexible',
            'single_recharge_day' => 'nullable|integer|min:1|max:31',
            'minimum_recharge_days' => 'nullable|integer|min:1|max:365',
            'flexible_min_days' => 'nullable|integer|min:1|max:365',
            'terminate_hour' => 'nullable|integer|min:0|max:23',
            'terminate_minute' => 'nullable|integer|min:0|max:59',
            'serial_start_from' => 'nullable|integer|min:0',
            'client_prefix' => 'nullable|string|max:20',
            'ppp_short_code' => 'nullable|string|max:30',
            'agent_id' => 'nullable|exists:agents,id',
            'referral_commission_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            $companyId = auth()->user()->company_id;
            if (!$companyId) {
                return back()->withInput()->with('error', 'Company context is required to create a reseller.');
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_type' => 'reseller',
                'is_active' => ($request->status ?? 'active') === 'active',
                'company_id' => $companyId,
            ]);

            $resellerCreateData = [
                'user_id' => $user->id,
                'company_id' => $companyId,
                'name' => $request->name,
                'company_name' => $request->company_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'area_name_en' => $request->area_name_en,
                'area_name_bn' => $request->area_name_bn,
                'nid' => $request->nid,
                'type' => $request->type,
                'commission_rate' => 0,
                'billing_type' => $request->billing_type ?? 'prepaid',
                'credit_limit' => $request->credit_limit ?? 0,
                'over_due_limit' => $request->over_due_limit ?? 0,
                'minimum_recharge_days' => $request->minimum_recharge_days ?? 30,
                'auto_recharge' => $request->auto_recharge === 'yes',
                'auto_bill_generate' => $request->auto_bill_generate === 'yes',
                'recharge_day_type' => $request->recharge_day_type ?? 'multi',
                'single_recharge_day' => $request->single_recharge_day,
                'flexible_min_days' => $request->flexible_min_days ?? 1,
                'terminate_hour' => $request->terminate_hour ?? 0,
                'terminate_minute' => $request->terminate_minute ?? 5,
                'serial_start_from' => $request->serial_start_from ?? 0,
                'client_prefix' => $request->client_prefix,
                'ppp_short_code' => $request->ppp_short_code,
                'agent_id' => $request->agent_id,
                'referral_commission_percent' => $request->referral_commission_percent,
                'thana' => $request->thana,
                'status' => $request->status ?? 'active',
            ];

            if ($request->type === 'bandwidth') {
                $resellerCreateData['raw_bandwidth']        = $request->raw_bandwidth;
                $resellerCreateData['intl_rate']            = $request->intl_rate;
                $resellerCreateData['ggc_bandwidth']        = $request->ggc_bandwidth;
                $resellerCreateData['ggc_rate']             = $request->ggc_rate;
                $resellerCreateData['fna_bandwidth']        = $request->fna_bandwidth;
                $resellerCreateData['fna_rate']             = $request->fna_rate;
                $resellerCreateData['cdn_bandwidth']        = $request->cdn_bandwidth;
                $resellerCreateData['cdn_rate']             = $request->cdn_rate;
                $resellerCreateData['bdix_bandwidth']       = $request->bdix_bandwidth;
                $resellerCreateData['bdix_rate']            = $request->bdix_rate;
                $resellerCreateData['cache_bandwidth']      = $request->cache_bandwidth;
                $resellerCreateData['cache_rate']           = $request->cache_rate;
                // Calculate base total (standard categories only — reseller has no ID yet)
                $tmpReseller = new \App\Models\Reseller($resellerCreateData);
                $resellerCreateData['bandwidth_monthly_rate'] = $tmpReseller->calculateBandwidthTotal();
                // Store custom BW data for saving after reseller is created
                $customBwData = $request->input('bw_custom', []);
            }

            $resellerCreateData['mikrotik_device_id'] = $request->mikrotik_device_id;

            $reseller = Reseller::create($resellerCreateData);

            // Save custom BW category allocations and recalculate total
            if ($reseller->type === 'bandwidth' && !empty($customBwData)) {
                foreach ($customBwData as $catId => $vals) {
                    $mbps = (float)($vals['mbps'] ?? 0);
                    $rate = (float)($vals['rate'] ?? 0);
                    if ($mbps > 0 || $rate > 0) {
                        \App\Models\ResellerBwAllocation::updateOrCreate(
                            ['reseller_id' => $reseller->id, 'category_id' => (int)$catId],
                            ['mbps' => $mbps, 'rate' => $rate, 'company_id' => $reseller->company_id]
                        );
                    }
                }
                // Recalculate total now that custom allocations are saved
                $reseller->bandwidth_monthly_rate = $reseller->calculateBandwidthTotal();
                $reseller->save();
            }

            $permissions = $request->input('permissions', []);
            $existingPermissions = \Spatie\Permission\Models\Permission::whereIn('name', $permissions)
                ->where('guard_name', 'web')
                ->pluck('name')
                ->toArray();
            $user->syncPermissions($existingPermissions);
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            // Save portal features for all reseller types
            if ($request->has('mac_features_submitted') || $reseller->type === 'bandwidth') {
                $valid    = array_keys(Reseller::$availableFeatures);
                $selected = array_values(array_intersect($request->input('features', []), $valid));
                $reseller->update(['allowed_features' => $selected ?: []]);
            }

            DB::commit();
            return redirect()->route('admin.reseller.index')->with('success', 'Reseller "' . $reseller->name . '" created successfully with login credentials.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create reseller: ' . $e->getMessage());
        }
    }

    public function show(Reseller $reseller)
    {
        $reseller->load(['clients', 'packages.package', 'walletTransactions', 'user']);

        $bwBillHistory = collect();
        $bwAdjustments = collect();

        if ($reseller->type === 'bandwidth') {
            $bwBillHistory = \App\Models\ResellerBill::where('reseller_id', $reseller->id)
                ->orderBy('bill_year', 'asc')
                ->orderBy('bill_month', 'asc')
                ->get(['bill_month', 'bill_year', 'total_recharge', 'total_paid', 'due_amount', 'bandwidth_breakdown']);

            $bwAdjustments = \App\Models\BandwidthAdjustment::where('reseller_id', $reseller->id)
                ->orderBy('effective_date', 'desc')
                ->get();
        }

        return view('admin.reseller.show', compact('reseller', 'bwBillHistory', 'bwAdjustments'));
    }

    public function edit(Reseller $reseller)
    {
        $mikrotikDevices = MikrotikDevice::where('is_active', true)->orderBy('name')->get();
        $agents = \App\Models\Agent::where('status', 'active')
            ->when($reseller->agent_id, fn($q) => $q->orWhere('id', $reseller->agent_id))
            ->orderBy('name')->get();
        // Pass custom BW categories and allocations for edit form
        $customDbCategories = \App\Models\BandwidthCategory::where('is_system', false)
            ->orderBy('sort_order')->orderBy('id')->get();
        $customAllocations = $customDbCategories->isNotEmpty()
            ? \App\Models\ResellerBwAllocation::where('reseller_id', $reseller->id)
                ->whereIn('category_id', $customDbCategories->pluck('id'))->get()->keyBy('category_id')
            : collect();
        $availableFeatures = Reseller::$availableFeatures;
        $enabledFeatures   = $reseller->getEnabledFeatures();
        return view('admin.reseller.edit', compact('reseller', 'mikrotikDevices', 'agents', 'customDbCategories', 'customAllocations', 'availableFeatures', 'enabledFeatures'));
    }

    public function update(Request $request, Reseller $reseller)
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:100|unique:users,email,' . ($reseller->user_id ?? 'NULL'),
            'phone' => 'required|string|max:20',
            'type' => 'required|in:mac,bandwidth',
            'mikrotik_device_id' => 'nullable|exists:mikrotik_devices,id',
            'ppp_short_code' => 'nullable|string|max:30',
            'agent_id' => 'nullable|exists:agents,id',
            'referral_commission_percent' => 'nullable|numeric|min:0|max:100',
            'recharge_day_type' => 'nullable|in:single,multi,flexible',
        ];

        if ($reseller->user) {
            $validationRules['password'] = 'nullable|string|min:6|confirmed';
        } else {
            $validationRules['password'] = 'required|string|min:6|confirmed';
        }

        $request->validate($validationRules);

        DB::beginTransaction();
        try {
            $resellerData = [
                'name' => $request->name,
                'company_name' => $request->company_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'area_name_en' => $request->area_name_en,
                'area_name_bn' => $request->area_name_bn,
                'nid' => $request->nid,
                'type' => $request->type,
                'commission_rate' => 0,
                'billing_type' => $request->billing_type ?? 'prepaid',
                'credit_limit' => $request->credit_limit ?? 0,
                'over_due_limit' => $request->over_due_limit ?? 0,
                'minimum_recharge_days' => $request->minimum_recharge_days ?? 30,
                'auto_recharge' => $request->auto_recharge === 'yes',
                'auto_bill_generate' => $request->auto_bill_generate === 'yes',
                'recharge_day_type' => $request->recharge_day_type ?? 'multi',
                'single_recharge_day' => $request->single_recharge_day,
                'flexible_min_days' => $request->flexible_min_days ?? 1,
                'terminate_hour' => $request->terminate_hour ?? 0,
                'terminate_minute' => $request->terminate_minute ?? 5,
                'serial_start_from' => $request->serial_start_from ?? 0,
                'client_prefix' => $request->client_prefix,
                'ppp_short_code' => $request->ppp_short_code,
                'agent_id' => $request->agent_id,
                'referral_commission_percent' => $request->referral_commission_percent,
                'thana' => $request->thana,
                'status' => $request->status ?? 'active',
            ];

            if ($request->type === 'bandwidth') {
                $resellerData['raw_bandwidth']   = $request->raw_bandwidth;
                $resellerData['intl_rate']       = $request->intl_rate;
                $resellerData['ggc_bandwidth']   = $request->ggc_bandwidth;
                $resellerData['ggc_rate']        = $request->ggc_rate;
                $resellerData['fna_bandwidth']   = $request->fna_bandwidth;
                $resellerData['fna_rate']        = $request->fna_rate;
                $resellerData['cdn_bandwidth']   = $request->cdn_bandwidth;
                $resellerData['cdn_rate']        = $request->cdn_rate;
                $resellerData['bdix_bandwidth']  = $request->bdix_bandwidth;
                $resellerData['bdix_rate']       = $request->bdix_rate;
                $resellerData['cache_bandwidth'] = $request->cache_bandwidth;
                $resellerData['cache_rate']      = $request->cache_rate;
                // Save custom BW category allocations FIRST so calculateBandwidthTotal()
                // picks them up when computing the monthly rate
                $submittedCustomIds = [];
                foreach ($request->input('bw_custom', []) as $catId => $vals) {
                    $mbps = (float)($vals['mbps'] ?? 0);
                    $rate = (float)($vals['rate'] ?? 0);
                    \App\Models\ResellerBwAllocation::updateOrCreate(
                        ['reseller_id' => $reseller->id, 'category_id' => (int)$catId],
                        ['mbps' => $mbps, 'rate' => $rate, 'company_id' => $reseller->company_id]
                    );
                    $submittedCustomIds[] = (int)$catId;
                }
                // Zero-out allocations that were removed from the form
                \App\Models\ResellerBwAllocation::where('reseller_id', $reseller->id)
                    ->when(!empty($submittedCustomIds), fn($q) => $q->whereNotIn('category_id', $submittedCustomIds))
                    ->update(['mbps' => 0, 'rate' => 0]);
                // Now calculate total — getBandwidthCategories() will load the freshly-saved custom allocs
                $tmpReseller = $reseller->fill($resellerData);
                $resellerData['bandwidth_monthly_rate'] = $tmpReseller->calculateBandwidthTotal();
            }

            $resellerData['mikrotik_device_id'] = $request->mikrotik_device_id;

            $reseller->update($resellerData);

            if ($reseller->user) {
                $userData = [
                    'name' => $request->name,
                    'email' => $request->email,
                    'is_active' => $request->status === 'active',
                ];
                
                if ($request->filled('password')) {
                    $userData['password'] = Hash::make($request->password);
                }
                
                $reseller->user->update($userData);
                
                $permissions = $request->input('permissions', []);
                $existingPermissions = \Spatie\Permission\Models\Permission::whereIn('name', $permissions)
                    ->where('guard_name', 'web')
                    ->pluck('name')
                    ->toArray();
                $reseller->user->syncPermissions($existingPermissions);
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            } else {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'user_type' => 'reseller',
                    'is_active' => $request->status === 'active',
                    'company_id' => $reseller->company_id ?? auth()->user()->company_id,
                ]);
                $reseller->update(['user_id' => $user->id]);
                
                $permissions = $request->input('permissions', []);
                $existingPermissions = \Spatie\Permission\Models\Permission::whereIn('name', $permissions)
                    ->where('guard_name', 'web')
                    ->pluck('name')
                    ->toArray();
                $user->syncPermissions($existingPermissions);
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            }

            DB::commit();

            // For bandwidth resellers, immediately recalculate current-month bill
            // so any rate/Mbps change is reflected in invoices right away.
            if ($reseller->type === 'bandwidth') {
                try {
                    $billingService = new \App\Services\ResellerBillingService();
                    $billingService->generateBandwidthBillWithAdjustments(
                        $reseller->fresh(), now()->month, now()->year
                    );
                } catch (\Exception $e) {
                    \Log::warning("Bill auto-recalc failed for reseller {$reseller->name}: " . $e->getMessage());
                }
            }

            // Save portal features for all reseller types
            if ($request->has('mac_features_submitted') || $reseller->fresh()->type === 'bandwidth') {
                $valid    = array_keys(Reseller::$availableFeatures);
                $selected = array_values(array_intersect($request->input('features', []), $valid));
                $reseller->update(['allowed_features' => $selected ?: []]);
            }

            $resellerType = $reseller->fresh()->type ?? 'mac';
            return redirect()->route('admin.reseller.index', ['type' => $resellerType])->with('success', 'Reseller "' . $reseller->name . '" updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update reseller: ' . $e->getMessage());
        }
    }

    public function destroy(Reseller $reseller)
    {
        DB::beginTransaction();
        try {
            if ($reseller->clients()->count() > 0) {
                return back()->with('error', 'Cannot delete reseller with assigned clients. Reassign or remove clients first.');
            }

            if ($reseller->user) {
                $reseller->user->delete();
            }

            $reseller->delete();

            DB::commit();
            return redirect()->route('admin.reseller.index')->with('success', 'Reseller deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete reseller: ' . $e->getMessage());
        }
    }

    public function terminate(Request $request, Reseller $reseller)
    {
        $request->validate([
            'termination_reason' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $reseller->update([
                'status' => 'suspended',
                'terminated_at' => now(),
                'termination_reason' => $request->termination_reason,
            ]);

            if ($reseller->user) {
                $reseller->user->update(['is_active' => false]);
            }

            $clients = $reseller->clients()->where('status', 'active')->get();
            foreach ($clients as $client) {
                try {
                    $mikrotik = new MikroTikService();
                    $mikrotik->suspendClient($client, 'reseller_termination', auth()->id(), false);
                } catch (\Exception $e) {
                    Log::warning("Failed to disable client {$client->username} during reseller termination: " . $e->getMessage());
                    $client->update(['status' => 'inactive']);
                }
            }

            DB::commit();
            return redirect()->route('admin.reseller.show', $reseller)->with('success', 'Reseller "' . $reseller->name . '" has been terminated. ' . $clients->count() . ' client(s) disabled.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to terminate reseller: ' . $e->getMessage());
        }
    }

    public function reactivate(Reseller $reseller)
    {
        DB::beginTransaction();
        try {
            $reseller->update([
                'status' => 'active',
                'terminated_at' => null,
                'termination_reason' => null,
            ]);

            if ($reseller->user) {
                $reseller->user->update(['is_active' => true]);
            }

            DB::commit();
            return redirect()->route('admin.reseller.show', $reseller)->with('success', 'Reseller "' . $reseller->name . '" has been reactivated. Clients can be re-enabled individually.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reactivate reseller: ' . $e->getMessage());
        }
    }

    public function clients(Request $request, ?Reseller $reseller = null)
    {
        $query = Client::with(['reseller', 'zone', 'package'])->whereNotNull('reseller_id');
        
        if ($reseller) {
            $query->where('reseller_id', $reseller->id);
        } elseif ($request->reseller_id) {
            $query->where('reseller_id', $request->reseller_id);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%")
                  ->orWhere('client_id', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        $clients = $query->orderBy('created_at', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20)->withQueryString();
        $resellers = \App\Models\Reseller::orderBy('name')->get();

        $resellerPackageMap = [];
        $clientResellerPairs = $clients->filter(fn($c) => $c->reseller_id && $c->package_id)
            ->map(fn($c) => ['reseller_id' => $c->reseller_id, 'package_id' => $c->package_id]);
        if ($clientResellerPairs->isNotEmpty()) {
            $rps = \App\Models\ResellerPackage::where('is_active', true)
                ->where(function ($q) use ($clientResellerPairs) {
                    foreach ($clientResellerPairs->unique(fn($p) => $p['reseller_id'] . '-' . $p['package_id']) as $pair) {
                        $q->orWhere(function ($q2) use ($pair) {
                            $q2->where('reseller_id', $pair['reseller_id'])
                               ->where('package_id', $pair['package_id']);
                        });
                    }
                })->get();
            foreach ($rps as $rp) {
                $resellerPackageMap[$rp->reseller_id . '-' . $rp->package_id] = $rp;
            }
        }

        return view('admin.reseller.clients', compact('clients', 'reseller', 'resellers', 'resellerPackageMap'));
    }

    public function wallet(Reseller $reseller)
    {
        $transactions = $reseller->walletTransactions()->latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.reseller.wallet', compact('reseller', 'transactions'));
    }

    public function creditWallet(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string',
            'reference' => 'nullable|string'
        ]);

        $reseller->creditWallet(
            $validated['amount'],
            $validated['description'],
            $validated['reference'],
            auth()->id()
        );

        $currentMonth = now()->month;
        $currentYear = now()->year;
        $resellerBill = ResellerBill::firstOrCreate(
            [
                'reseller_id' => $reseller->id,
                'bill_month' => $currentMonth,
                'bill_year' => $currentYear,
            ],
            [
                'total_recharge' => 0,
                'total_paid' => 0,
                'due_amount' => 0,
            ]
        );

        $resellerBill->total_recharge += $validated['amount'];
        $resellerBill->due_amount = $resellerBill->total_recharge - $resellerBill->total_paid;
        $resellerBill->updateStatus();

        return redirect()->back()->with('success', '৳' . number_format($validated['amount'], 2) . ' credited to wallet.');
    }

    public function debitWallet(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string',
            'reference' => 'nullable|string'
        ]);

        if (!$reseller->canDebit($validated['amount'])) {
            return redirect()->back()->with('error', 'Insufficient wallet balance or credit limit.');
        }

        $reseller->debitWallet(
            $validated['amount'],
            $validated['description'],
            $validated['reference'],
            auth()->id()
        );

        return redirect()->back()->with('success', '৳' . number_format($validated['amount'], 2) . ' debited from wallet.');
    }

    public function packages(Reseller $reseller)
    {
        $resellerPackages = $reseller->packages()->with('package')->get();
        $allPackages = Package::where('is_active', true)->get();
        return view('admin.reseller.packages', compact('reseller', 'resellerPackages', 'allPackages'));
    }

    public function storePackage(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'reseller_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0'
        ]);

        ResellerPackage::updateOrCreate(
            ['reseller_id' => $reseller->id, 'package_id' => $validated['package_id']],
            [
                'reseller_price' => $validated['reseller_price'],
                'selling_price' => $validated['selling_price'],
                'is_active' => true,
                'company_id' => $reseller->company_id,
            ]
        );

        return redirect()->back()->with('success', 'Package rate saved.');
    }

    public function updatePackage(Request $request, Reseller $reseller, ResellerPackage $resellerPackage)
    {
        abort_unless($resellerPackage->reseller_id === $reseller->id, 403);

        $validated = $request->validate([
            'reseller_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0'
        ]);

        $resellerPackage->update([
            'reseller_price' => $validated['reseller_price'],
            'selling_price' => $validated['selling_price'],
        ]);

        return redirect()->back()->with('success', 'Package rate updated.');
    }

    public function deletePackage(Reseller $reseller, ResellerPackage $resellerPackage)
    {
        abort_unless($resellerPackage->reseller_id === $reseller->id, 403);

        $resellerPackage->delete();
        return redirect()->back()->with('success', 'Package removed.');
    }

    public function collectionReport(Request $request)
    {
        $resellers = Reseller::with('clients')->where('type', '!=', 'bandwidth')->get()->map(function ($r) use ($request) {
            $clientIds = $r->clients->pluck('id');
            
            $paymentsQuery = Payment::whereIn('client_id', $clientIds);
            
            if ($request->start_date) {
                $paymentsQuery->whereDate('payment_date', '>=', $request->start_date);
            }
            if ($request->end_date) {
                $paymentsQuery->whereDate('payment_date', '<=', $request->end_date);
            }

            $r->total_collection = $paymentsQuery->sum('amount');
            $r->payment_count = $paymentsQuery->count();
            
            return $r;
        });

        return view('admin.reseller.collection-report', compact('resellers'));
    }

    public function bwCollectionReport(Request $request)
    {
        $resellers = Reseller::where('type', 'bandwidth')->get()->map(function ($r) use ($request) {
            $billsQuery = \App\Models\ResellerBill::where('reseller_id', $r->id);

            if ($request->start_date) {
                $billsQuery->where(function($q) use ($request) {
                    $q->whereRaw("STR_TO_DATE(CONCAT(bill_year,'-',LPAD(bill_month,2,'0'),'-01'), '%Y-%m-%d') >= ?", [$request->start_date]);
                });
            }
            if ($request->end_date) {
                $billsQuery->where(function($q) use ($request) {
                    $q->whereRaw("STR_TO_DATE(CONCAT(bill_year,'-',LPAD(bill_month,2,'0'),'-28'), '%Y-%m-%d') <= ?", [$request->end_date]);
                });
            }

            $bills = $billsQuery->get();
            $r->total_billed   = $bills->sum('total_recharge');
            $r->total_paid     = $bills->sum('total_paid');
            $r->total_due      = $bills->sum('total_due');
            $r->bill_count     = $bills->count();
            
            return $r;
        });

        return view('admin.reseller.bw-collection-report', compact('resellers'));
    }

    public function funding(Request $request)
    {
        $billingService = new \App\Services\ResellerBillingService();

        $resellers = Reseller::with('clients')->where('type', '!=', 'bandwidth')->get()->map(function ($reseller) use ($billingService) {
            $hasBills = \App\Models\ResellerBill::where('reseller_id', $reseller->id)->exists();
            // BW resellers bill by bandwidth rate, not client recharges — skip MAC-style auto-bill
            if ($reseller->type !== 'bandwidth') {
                $hasRecharges = \App\Models\ResellerClientRecharge::where('reseller_id', $reseller->id)->exists();
                if (!$hasBills && $hasRecharges) {
                    $months = \App\Models\ResellerClientRecharge::where('reseller_id', $reseller->id)
                        ->selectRaw('DISTINCT bill_month, bill_year')
                        ->get();
                    foreach ($months as $m) {
                        $billingService->updateResellerBillOnRecharge($reseller, $m->bill_month, $m->bill_year);
                    }
                    $billingService->recalculateResellerOverallDue($reseller);
                }
            } elseif (!$hasBills) {
                // BW reseller has no bill yet — generate one now from bandwidth rates
                $billingService->generateMonthlyBill($reseller, now()->month, now()->year);
            }

            $totals = \App\Models\ResellerBillPayment::where('reseller_id', $reseller->id)
                ->selectRaw('COALESCE(SUM(amount), 0) as total_paid, COALESCE(SUM(discount_amount), 0) as total_discount')
                ->first();
            $reseller->summary_total_paid      = (float) $totals->total_paid;
            $reseller->summary_total_discount  = (float) $totals->total_discount;
            $reseller->summary_due             = \App\Services\ResellerBillingService::getResellerDueToAdmin($reseller);

            if ($reseller->type === 'bandwidth') {
                $reseller->summary_total_billed = (float) \App\Models\ResellerBill::where('reseller_id', $reseller->id)->sum('total_recharge');
            } else {
                $totalRecharges = (float) \App\Models\ResellerClientRecharge::where('reseller_id', $reseller->id)
                    ->where('transaction_type', 'recharge')
                    ->sum('recharge_amount');
                $totalDeductions = (float) \App\Models\ResellerClientRecharge::where('reseller_id', $reseller->id)
                    ->whereIn('transaction_type', ['curtailed_with_benefits', 'recharge_refund', 'recharge_reversal'])
                    ->sum('recharge_amount');
                $reseller->summary_total_billed = max(0, $totalRecharges - $totalDeductions);
            }
            return $reseller;
        });

        // Only show payments for MAC resellers (not bandwidth)
        $macResellerIds = Reseller::where('type', '!=', 'bandwidth')->pluck('id');

        $query = \App\Models\ResellerBillPayment::with(['reseller', 'receivedByUser'])
            ->whereIn('reseller_id', $macResellerIds)
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->reseller_id) {
            $query->where('reseller_id', $request->reseller_id);
        }
        if ($request->date_from) {
            $query->where('payment_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('payment_date', '<=', $request->date_to);
        }


        $payments = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:25)->withQueryString();

        $paymentMethods = auth()->user()->allowedPaymentMethods();

        return view('admin.reseller.funding', compact('resellers', 'payments', 'paymentMethods'));
    }

    public function storeFunding(Request $request)
    {
        $request->validate([
            'reseller_id' => 'required|exists:resellers,id',
            'amount'      => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date'   => 'required|date',
        ]);

        $reseller = Reseller::findOrFail($request->reseller_id);
        $billingService = new \App\Services\ResellerBillingService();
        $billingService->recordPayment($reseller, $request->only([
            'amount', 'discount_amount', 'payment_method', 'payment_reference', 'payment_date', 'notes',
        ]));

        return back()->with('success', 'Payment of ৳' . number_format($request->amount, 2) . ' recorded for ' . $reseller->name . '.');
    }

    public function updateFundingPayment(Request $request, \App\Models\ResellerBillPayment $payment)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date'   => 'required|date',
            'discount_amount'=> 'nullable|numeric|min:0',
        ]);

        $payment->update([
            'amount'           => $request->amount,
            'discount_amount'  => $request->discount_amount ?? 0,
            'payment_method'   => $request->payment_method,
            'payment_reference'=> $request->payment_reference,
            'payment_date'     => $request->payment_date,
            'notes'            => $request->notes,
        ]);

        return back()->with('success', 'Payment updated successfully.');
    }

    public function deleteFundingPayment(\App\Models\ResellerBillPayment $payment)
    {
        $payment->delete();
        return back()->with('success', 'Payment deleted successfully.');
    }


    public function bwFunding(\Illuminate\Http\Request $request)
    {
        $billingService = new \App\Services\ResellerBillingService();
        $resellers = \App\Models\Reseller::where("type", "bandwidth")->get()->map(function ($reseller) use ($billingService) {
            $hasBills = \App\Models\ResellerBill::where("reseller_id", $reseller->id)->exists();
            if (!$hasBills) {
                $billingService->generateMonthlyBill($reseller, now()->month, now()->year);
            }
            $totals = \App\Models\ResellerBillPayment::where("reseller_id", $reseller->id)
                ->selectRaw("COALESCE(SUM(amount), 0) as total_paid, COALESCE(SUM(discount_amount), 0) as total_discount")
                ->first();
            $reseller->summary_total_paid     = (float) $totals->total_paid;
            $reseller->summary_total_discount = (float) $totals->total_discount;
            $reseller->summary_due            = \App\Services\ResellerBillingService::getResellerDueToAdmin($reseller);
            $reseller->summary_total_billed   = (float) \App\Models\ResellerBill::where("reseller_id", $reseller->id)->sum("total_recharge");
            return $reseller;
        });
        $bwResellerIds = \App\Models\Reseller::where("type", "bandwidth")->pluck("id");
        $query = \App\Models\ResellerBillPayment::with(["reseller", "receivedByUser"])
            ->whereIn("reseller_id", $bwResellerIds)
            ->orderBy("payment_date", "desc")
            ->orderBy("id", "desc");
        if ($request->reseller_id) { $query->where("reseller_id", $request->reseller_id); }
        if ($request->date_from)   { $query->where("payment_date", ">=", $request->date_from); }
        if ($request->date_to)     { $query->where("payment_date", "<=", $request->date_to); }
        $payments = $query->paginate(
            in_array((int) request()->per_page, [20, 50, 100, 200]) ? (int) request()->per_page : 25
        )->withQueryString();
        $paymentMethods = auth()->user()->allowedPaymentMethods();
        return view("admin.reseller.bw-funding", compact("resellers", "payments", "paymentMethods"));
    }

    public function bwFundingStore(\Illuminate\Http\Request $request)
    {
        $request->validate([
            "reseller_id"    => "required|exists:resellers,id",
            "amount"         => "required|numeric|min:0.01",
            "payment_method" => "required|string",
            "payment_date"   => "required|date",
        ]);
        $reseller = \App\Models\Reseller::findOrFail($request->reseller_id);
        if ($reseller->type !== "bandwidth") {
            return back()->with("error", "Only for bandwidth resellers.");
        }
        $billingService = new \App\Services\ResellerBillingService();
        $billingService->recordPayment($reseller, $request->only([
            "amount", "discount_amount", "payment_method", "payment_reference", "payment_date", "notes",
        ]));
        return back()->with("success", "Payment recorded for " . $reseller->name . ".");
    }

    public function bandwidthView(Reseller $reseller)
    {
        if ($reseller->type !== 'bandwidth') {
            return redirect()->route('admin.reseller.show', $reseller)
                ->with('error', 'Bandwidth allocation is only available for bandwidth resellers.');
        }
        // Standard system categories — DB values (icon/color/label) override defaults
        $standardCategories = \App\Models\BandwidthCategory::getStandardCategories();
        $allDbCategories = \App\Models\BandwidthCategory::orderBy('sort_order')->orderBy('id')->get();
        $bwCategories = array_values($standardCategories);
        $customDbCategories = $allDbCategories->filter(fn($c) => !$c->is_system)->values();
        $customAllocations = $customDbCategories->isNotEmpty()
            ? \App\Models\ResellerBwAllocation::where('reseller_id', $reseller->id)
                ->whereIn('category_id', $customDbCategories->pluck('id'))->get()->keyBy('category_id')
            : collect();
        return view('admin.reseller.bandwidth', compact('reseller', 'bwCategories', 'customDbCategories', 'customAllocations'));
    }

    public function bandwidthAdjustments(Request $request, Reseller $reseller)
    {
        if ($reseller->type !== 'bandwidth') {
            return redirect()->route('admin.reseller.show', $reseller)->with('error', 'Only for bandwidth resellers.');
        }
        $adjustments = \App\Models\BandwidthAdjustment::where('reseller_id', $reseller->id)
            ->orderBy('effective_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->with('createdBy')
            ->get();

        $categoryKeys = ['intl', 'ggc', 'fna', 'cdn', 'bdix', 'cache'];
        $categoryMap = [];
        foreach (\App\Models\BandwidthCategory::getStandardCategories() as $key => $cat) {
            $categoryMap[$key] = [
                'label'      => $cat['label'],
                'mbps_field' => $cat['mbps_field'],
                'rate_field' => $cat['rate_field'],
                'icon'       => $cat['icon'],
                'color'      => $cat['color'],
            ];
        }

        // Include custom DB categories
        $customDbCategories = \App\Models\BandwidthCategory::where('is_system', false)->orderBy('sort_order')->orderBy('id')->get();
        foreach ($customDbCategories as $ccat) {
            $key = 'custom_' . $ccat->id;
            $categoryKeys[] = $key;
            $categoryMap[$key] = [
                'label'       => $ccat->label,
                'mbps_field'  => null,
                'rate_field'  => null,
                'icon'        => str_starts_with($ccat->icon, 'img:') ? 'fas fa-network-wired' : $ccat->icon,
                'color'       => $ccat->color,
                'custom_id'   => $ccat->id,
                'is_custom'   => true,
            ];
        }

        return view('admin.reseller.bandwidth-adjustments', compact('reseller', 'adjustments', 'categoryMap', 'categoryKeys', 'customDbCategories'));
    }

    public function storeBandwidthAdjustment(Request $request, Reseller $reseller)
    {
        if ($reseller->type !== 'bandwidth') {
            return back()->with('error', 'Only for bandwidth resellers.');
        }

        $validated = $request->validate([
            'category_key'   => ['required', \Illuminate\Validation\Rule::in(array_merge(['intl','ggc','fna','cdn','bdix','cache'], \App\Models\BandwidthCategory::where('is_system',false)->get()->map(fn($c)=>'custom_'.$c->id)->toArray()))],
            'effective_date' => 'required|date',
            'new_mbps'       => 'required|numeric|min:0',
            'reason'         => 'nullable|string|max:255',
        ]);

        $categoryMap = [];
        foreach (\App\Models\BandwidthCategory::getStandardCategories() as $key => $cat) {
            $categoryMap[$key] = ['mbps_field' => $cat['mbps_field'], 'rate_field' => $cat['rate_field']];
        }

        $catKey = $validated['category_key'];
        $isCustom = str_starts_with($catKey, 'custom_');
        if ($isCustom) {
            $customCatId = (int) str_replace('custom_', '', $catKey);
            $customCat = \App\Models\BandwidthCategory::find($customCatId);
            $alloc = \App\Models\ResellerBwAllocation::where('reseller_id', $reseller->id)->where('category_id', $customCatId)->first();
            $oldMbps = $alloc ? (float)$alloc->mbps : 0;
            $rate    = $alloc ? (float)$alloc->rate  : 0;
            $cat     = null;
        } else {
            $cat     = $categoryMap[$catKey];
            $oldMbps = (float) ($reseller->{$cat['mbps_field']} ?? 0);
            $rate    = (float) ($reseller->{$cat['rate_field']} ?? 0);
        }
        $newMbps = (float) $validated['new_mbps'];

        \App\Models\BandwidthAdjustment::create([
            'reseller_id'    => $reseller->id,
            'category_key'   => $validated['category_key'],
            'effective_date' => $validated['effective_date'],
            'old_mbps'       => $oldMbps,
            'new_mbps'       => $newMbps,
            'rate'           => $rate,
            'reason'         => $validated['reason'] ?? null,
            'created_by'     => auth()->id(),
            'company_id'     => $reseller->company_id,
        ]);

        // Apply new Mbps to reseller immediately
        if ($isCustom) {
            // Update the custom category allocation in ResellerBwAllocation
            \App\Models\ResellerBwAllocation::updateOrCreate(
                ['reseller_id' => $reseller->id, 'category_id' => $customCatId],
                ['mbps' => $newMbps, 'rate' => $rate, 'company_id' => $reseller->company_id]
            );
        } else {
            $reseller->{$cat['mbps_field']} = $newMbps;
        }
        $reseller->bandwidth_monthly_rate = $reseller->calculateBandwidthTotal();
        $reseller->save();

        // Recalculate current month bill with pro-rated segments
        $billingService = new \App\Services\ResellerBillingService();
        $billingService->generateBandwidthBillWithAdjustments($reseller, now()->month, now()->year);

        return back()->with('success', 'Bandwidth adjusted successfully. Bill recalculated with pro-rated amounts.');
    }

    public function destroyBandwidthAdjustment(Reseller $reseller, \App\Models\BandwidthAdjustment $adjustment)
    {
        if ($adjustment->reseller_id !== $reseller->id) {
            return back()->with('error', 'Not found.');
        }
        $adjustment->delete();
        return back()->with('success', 'Adjustment record deleted.');
    }

        public function billHistory(Reseller $reseller)
    {
        $reseller->load('agent');
        $billingService = new \App\Services\ResellerBillingService();
        $ledger = $billingService->getResellerLedger($reseller);
        $bills = ResellerBill::where('reseller_id', $reseller->id)
            ->orderBy('bill_year', 'desc')
            ->orderBy('bill_month', 'desc')
            ->get();

        foreach ($bills as $bill) {
            $bill->rechargeEntries = \App\Models\ResellerClientRecharge::where('reseller_id', $reseller->id)
                ->where('bill_month', $bill->bill_month)
                ->where('bill_year', $bill->bill_year)
                ->with('client')
                ->get();
            $bill->paymentEntries = \App\Models\ResellerBillPayment::where('reseller_bill_id', $bill->id)->get();
        }

        $activeClients = $reseller->clients()->where('status', 'active')->count();
        $totalClients = $reseller->clients()->count();

        return view('admin.reseller.bill-history', compact('reseller', 'ledger', 'bills', 'activeClients', 'totalClients'));
    }


    public function buildInvoiceData(ResellerBill $bill): array
    {
        $reseller = $bill->reseller;
        $rechargeEntries = \App\Models\ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->where('bill_month', $bill->bill_month)
            ->where('bill_year', $bill->bill_year)
            ->with('client')
            ->get();
        $paymentEntries = \App\Models\ResellerBillPayment::where('reseller_bill_id', $bill->id)
            ->with('receivedByUser')
            ->get();
        $adjustments = \App\Models\BandwidthAdjustment::where('reseller_id', $reseller->id)
            ->whereMonth('effective_date', $bill->bill_month)
            ->whereYear('effective_date', $bill->bill_year)
            ->orderBy('effective_date')
            ->get();
        // Load company info from App Settings → Company Profile
        $companyId = (auth()->check() && auth()->user()->company_id) ? auth()->user()->company_id : ($reseller->company_id ?? 1);
        $co = \App\Models\CompanyProfile::where('company_id', $companyId)->first();
        if (!$co) {
            $co = \App\Models\Company::find($companyId) ?? \App\Models\Company::first();
        }

        // Logo: use path saved by App Settings → Company Profile
        $logoBase64 = '';
        $logoPath = \App\Models\SystemSetting::get('site_logo', '');
        if ($logoPath) {
            $fullPath = public_path(ltrim($logoPath, '/'));
            if (file_exists($fullPath)) {
                $logoBase64 = base64_encode(file_get_contents($fullPath));
            }
        }
        if (!$logoBase64) {
            foreach (['logo.png', 'logo.jpg', 'logo.jpeg', 'logo.webp', 'logo.svg'] as $f) {
                $p = public_path('images/' . $f);
                if (file_exists($p)) { $logoBase64 = base64_encode(file_get_contents($p)); break; }
            }
        }

        return [
            'bill'            => $bill,
            'reseller'        => $reseller,
            'rechargeEntries' => $rechargeEntries,
            'paymentEntries'  => $paymentEntries,
            'adjustments'     => $adjustments,
            'companyName'     => ($co->name ?: \App\Models\SystemSetting::get('company_name', config('app.name', 'ISP Billing'))),
            'companyAddress'  => ($co->address ?: ''),
            'companyPhone'    => ($co->phone ?: ''),
            'companyAltPhone' => ($co->alt_phone ?: ''),
            'companyEmail'    => ($co->email ?: ''),
            'companyLogo'     => $logoPath,
            'logoBase64'      => $logoBase64,
        ];
    }

    public function invoiceBill(ResellerBill $bill)
    {
        $data = $this->buildInvoiceData($bill);
        return view('admin.reseller.invoice', $data);
    }

    public function downloadInvoiceBill(ResellerBill $bill)
    {
        $data = $this->buildInvoiceData($bill);
        $data['isPdf'] = true;
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reseller.invoice', $data)
            ->setPaper('a4', 'portrait');
        $month = \Carbon\Carbon::createFromDate($bill->bill_year, $bill->bill_month, 1)->format('M-Y');
        return $pdf->download("reseller-invoice-{$bill->bill_no}-{$month}.pdf");
    }

    /**
     * Show the current-month invoice for a bandwidth reseller.
     * Generates the bill if it does not yet exist.
     */

    public function currentMonthMacInvoice(\App\Models\Reseller $reseller)
    {
        if ($reseller->type !== 'mac') {
            return back()->with('error', 'This invoice is only for MAC resellers.');
        }

        $bill = \App\Models\ResellerBill::where('reseller_id', $reseller->id)
            ->where('bill_month', now()->month)
            ->where('bill_year',  now()->year)
            ->first();

        if (!$bill) {
            $billingService = new \App\Services\ResellerBillingService();
            $bill = $billingService->generateMonthlyBill($reseller, now()->month, now()->year);
        }

        return redirect()->route('admin.reseller.invoice', $bill);
    }

    public function currentMonthInvoice(Reseller $reseller)
    {
        if ($reseller->type !== 'bandwidth') {
            return back()->with('error', 'Invoice is only available for bandwidth resellers.');
        }

        $bill = \App\Models\ResellerBill::where('reseller_id', $reseller->id)
            ->where('bill_month', now()->month)
            ->where('bill_year',  now()->year)
            ->first();

        if (!$bill) {
            $billingService = new \App\Services\ResellerBillingService();
            $bill = $billingService->generateBandwidthBillWithAdjustments($reseller, now()->month, now()->year);
        }

        return redirect()->route('admin.reseller.invoice', $bill);
    }

    public function billPayment(Reseller $reseller)
    {
        $reseller->load('agent');
        $billingService = new \App\Services\ResellerBillingService();
        $activeClients = $reseller->clients()->where('status', 'active')->count();
        $totalClients = $reseller->clients()->count();

        $now = \Carbon\Carbon::now();
        $currentBill = ResellerBill::where('reseller_id', $reseller->id)
            ->where('bill_month', $now->month)
            ->where('bill_year', $now->year)
            ->first();

        $recharges = $billingService->getRechargeHistory($reseller, null, $now->month, $now->year);

        $recentPayments = \App\Models\ResellerBillPayment::where('reseller_id', $reseller->id)
            ->orderBy('payment_date', 'desc')
            ->limit(20)
            ->get();

        $paymentMethods = \App\Models\PaymentMethod::withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->where('company_id', $reseller->company_id)
            ->orderBy('id')->get();

        return view('admin.reseller.bill-payment', compact(
            'reseller', 'activeClients', 'totalClients', 'currentBill', 'recharges', 'recentPayments', 'paymentMethods'
        ));
    }

    public function storeBillPayment(Request $request, Reseller $reseller)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
        ]);

        $billingService = new \App\Services\ResellerBillingService();
        $billingService->recordPayment($reseller, $request->only([
            'amount', 'discount_amount', 'payment_method', 'payment_reference',
            'payment_date', 'notes', 'referral_commission_percent', 'referral_commission_amount',
        ]));

        return back()->with('success', 'Payment of ৳' . number_format($request->amount, 2) . ' recorded successfully.');
    }

    public function rechargeHistoryAjax(Request $request, Reseller $reseller)
    {
        $billingService = new \App\Services\ResellerBillingService();
        $recharges = $billingService->getRechargeHistory(
            $reseller,
            $request->date,
            $request->month ? (int)$request->month : null,
            $request->year ? (int)$request->year : null
        );

        return response()->json($recharges->map(function ($r) {
            return [
                'id' => $r->id,
                'client_name' => $r->client->name ?? 'N/A',
                'client_username' => $r->client->username ?? 'N/A',
                'client_id' => $r->client->client_id ?? 'N/A',
                'package_name' => $r->package_name,
                'recharge_amount' => $r->recharge_amount,
                'start_date' => $r->start_date->format('Y-m-d'),
                'end_date' => $r->end_date->format('Y-m-d'),
                'days' => $r->days,
                'recharged_by' => $r->rechargedByUser->name ?? 'System',
                'created_at' => $r->created_at->format('Y-m-d H:i:s'),
            ];
        }));
    }

    public function syncResellerClients(Reseller $reseller)
    {
        $billingService = new \App\Services\ResellerBillingService();
        $now = \Carbon\Carbon::now();
        $recharged = $billingService->syncResellerClientBilling($reseller, $now->month, $now->year);

        return back()->with('success', "Synced {$recharged} client(s) for {$reseller->name}. Monthly bill updated.");
    }

    public function syncAllResellerClients()
    {
        $billingService = new \App\Services\ResellerBillingService();
        $now = \Carbon\Carbon::now();
        $total = 0;

        $resellers = Reseller::where('status', 'active')->get();
        foreach ($resellers as $reseller) {
            $total += $billingService->syncResellerClientBilling($reseller, $now->month, $now->year);
        }

        return back()->with('success', "Synced billing for all resellers. {$total} total client recharge(s) created.");
    }

    public function clientTransactions(Request $request, Reseller $reseller)
    {
        $month = $request->month ? (int)$request->month : now()->month;
        $year = $request->year ? (int)$request->year : now()->year;

        $query = \App\Models\ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->with(['client', 'package', 'rechargedByUser']);

        if ($request->filled('month') || $request->filled('year')) {
            $query->where('bill_month', $month)->where('bill_year', $year);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', function ($cq) use ($search) {
                    $cq->where('username', 'ilike', "%{$search}%")
                        ->orWhere('name', 'ilike', "%{$search}%");
                })->orWhere('package_name', 'ilike', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50)->withQueryString();

        // Build set of recharge IDs that already have a refund, so view can hide action buttons
        $refundedIds = \App\Models\ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->where('transaction_type', 'recharge_refund')
            ->pluck('remarks')
            ->map(function ($remark) {
                if (preg_match('/Refund for recharge #(\d+)/i', $remark ?? '', $m)) {
                    return (int) $m[1];
                }
                return null;
            })
            ->filter()
            ->unique()
            ->flip()
            ->toArray(); // keyed by ID for O(1) lookup in blade

        return view('admin.reseller.client-transactions', compact('reseller', 'transactions', 'month', 'year', 'refundedIds'));
    }

    public function curtailClient(Request $request, Reseller $reseller)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'remarks' => 'nullable|string|max:500',
        ]);

        $client = \App\Models\Client::where('id', $request->client_id)
            ->where('reseller_id', $reseller->id)
            ->firstOrFail();

        $billingService = new \App\Services\ResellerBillingService();
        $billingService->curtailClient($reseller, $client, $request->only(['start_date', 'end_date', 'remarks']));

        return back()->with('success', "Curtailed transaction created for {$client->username}.");
    }

    public function correctRecharge(Request $request, Reseller $reseller)
    {
        $request->validate([
            'recharge_id'    => 'required|exists:reseller_client_recharges,id',
            'new_start_date' => 'required|date',
            'new_days'       => 'required|integer|min:1|max:365',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $original = \App\Models\ResellerClientRecharge::where('id', $request->recharge_id)
            ->where('reseller_id', $reseller->id)
            ->where('transaction_type', 'recharge')
            ->firstOrFail();

        $client = \App\Models\Client::where('id', $original->client_id)
            ->where('reseller_id', $reseller->id)
            ->firstOrFail();

        \DB::transaction(function () use ($reseller, $client, $original, $request) {
            \App\Models\ResellerClientRecharge::create([
                'reseller_id'      => $reseller->id,
                'client_id'        => $client->id,
                'bill_month'       => now()->month,
                'bill_year'        => now()->year,
                'package_id'       => $original->package_id,
                'package_name'     => $original->package_name,
                'package_price'    => $original->package_price,
                'recharge_amount'  => $original->recharge_amount,
                'daily_rate'       => $original->daily_rate,
                'credited_amount'  => -$original->credited_amount,
                'start_date'       => $original->start_date,
                'end_date'         => $original->end_date,
                'days'             => $original->days,
                'status'           => 'reversed',
                'transaction_type' => 'recharge_reversal',
                'recharged_by'     => auth()->id(),
                'remarks'          => "Admin correction reversal for Recharge #{$original->id}" . ($request->remarks ? " — {$request->remarks}" : ''),
                'company_id'       => $reseller->company_id,
            ]);

            if ($reseller->billing_type === 'prepaid') {
                $reseller->creditWallet(
                    (float) $original->recharge_amount,
                    "Admin correction reversal: {$client->username} Recharge #{$original->id}",
                    "admin_reversal:{$original->id}",
                    auth()->id()
                );
                $reseller->refresh();
            }

            $billingService = new \App\Services\ResellerBillingService();
            $newRecharge = $billingService->rechargeClient($reseller, $client, [
                'start_date' => $request->new_start_date,
                'days'       => (int) $request->new_days,
                'remarks'    => "Admin correction recharge (replaces #{$original->id})" . ($request->remarks ? " — {$request->remarks}" : ''),
            ]);

            $original->update(['remarks' => ($original->remarks ? $original->remarks . ' ' : '') . "[Admin Corrected → Recharge #{$newRecharge->id}]"]);
        });

        return back()->with('success', "Recharge #{$original->id} for {$client->username} corrected by admin. New recharge applied.");
    }

    /**
     * Refund only — credits wallet (prepaid) and logs refund record. Client remains active.
     */
    public function refundRecharge(Request $request, Reseller $reseller, \App\Models\Client $client)
    {
        abort_unless($client->reseller_id === $reseller->id, 403, 'Client does not belong to this reseller.');

        $request->validate([
            'recharge_id' => 'required|exists:reseller_client_recharges,id',
            'remarks'     => 'nullable|string|max:500',
        ]);

        $recharge = \App\Models\ResellerClientRecharge::where('id', $request->recharge_id)
            ->where('reseller_id', $reseller->id)
            ->where('client_id', $client->id)
            ->where('transaction_type', 'recharge')
            ->firstOrFail();

        $refundAmount = (float) $recharge->recharge_amount;

        \DB::transaction(function () use ($reseller, $client, $recharge, $refundAmount, $request) {
            \App\Models\ResellerClientRecharge::create([
                'reseller_id'      => $reseller->id,
                'client_id'        => $client->id,
                'bill_month'       => now()->month,
                'bill_year'        => now()->year,
                'package_id'       => $recharge->package_id,
                'package_name'     => $recharge->package_name,
                'package_price'    => $recharge->package_price,
                'recharge_amount'  => $refundAmount,
                'daily_rate'       => $recharge->daily_rate,
                'credited_amount'  => -$refundAmount,
                'start_date'       => $recharge->start_date,
                'end_date'         => $recharge->end_date,
                'days'             => $recharge->days,
                'status'           => 'refunded',
                'transaction_type' => 'recharge_refund',
                'recharged_by'     => auth()->id(),
                'remarks'          => $request->remarks ?: "Refund for recharge #" . $recharge->id . " — {$recharge->start_date} to {$recharge->end_date}",
                'company_id'       => $reseller->company_id,
            ]);

            // Subtract refunded days from client's expire_date (client stays active)
            if ($client->expire_date && $recharge->days > 0) {
                $newExpire = \Carbon\Carbon::parse($client->expire_date)->subDays($recharge->days);
                $client->expire_date = $newExpire->toDateString();
                $client->save();
            }

            if ($reseller->billing_type === 'prepaid') {
                $reseller->creditWallet(
                    $refundAmount,
                    "Refund: {$client->name} ({$client->username}) recharge #{$recharge->id}",
                    "refund:{$recharge->id}",
                    auth()->id()
                );
            }

            \App\Services\ClientActivityService::log(
                client: $client,
                actionType: 'updated',
                oldValue: null,
                newValue: null,
                reason: "Recharge refunded (financial only). Amount: ৳{$refundAmount}. Recharge ID: {$recharge->id}. Client remains active.",
                performedBy: auth()->id()
            );
        });

        $walletNote = $reseller->billing_type === 'prepaid'
            ? " ৳" . number_format($refundAmount, 2) . " credited to reseller wallet."
            : '';

        return back()->with('success', "Refund processed for {$client->name}.{$walletNote} Client remains active.");
    }

    // ==================== REVERSE CLIENT RECHARGE (Admin – Client Left) ====================
    public function reverseClientRecharge(Request $request, Reseller $reseller, \App\Models\Client $client)
    {
        abort_unless($client->reseller_id === $reseller->id, 403, 'Client does not belong to this reseller.');

        $request->validate([
            'recharge_id'  => 'required|exists:reseller_client_recharges,id',
            'reverse_from' => 'required|date',
            'remarks'      => 'nullable|string|max:500',
        ]);

        $original = \App\Models\ResellerClientRecharge::where('id', $request->recharge_id)
            ->where('reseller_id', $reseller->id)
            ->where('client_id', $client->id)
            ->where('transaction_type', 'recharge')
            ->firstOrFail();

        $billingService = new \App\Services\ResellerBillingService();
        $reversal = $billingService->reverseClientRecharge($reseller, $original, $request->only(['reverse_from', 'remarks']));

        $msg = "Recharge #{$original->id} for {$client->username} reversed. {$reversal->days} unused days";
        if ($reseller->billing_type === 'prepaid') {
            $msg .= " — ৳" . number_format($reversal->recharge_amount, 2) . " credited to reseller wallet.";
        } else {
            $msg .= " — ৳" . number_format($reversal->recharge_amount, 2) . " deducted from due.";
        }

        return back()->with('success', $msg);
    }

    // ==================== ADMIN REVERSED TRANSACTIONS LIST ====================
    public function reversedTransactions(Request $request, Reseller $reseller)
    {
        $query = \App\Models\ResellerClientRecharge::where('reseller_id', $reseller->id)
            ->whereIn('transaction_type', ['client_left_reversal', 'recharge_reversal', 'recharge_refund'])
            ->with(['client', 'rechargedByUser']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', function ($cq) use ($search) {
                    $cq->where('username', 'ilike', "%{$search}%")
                        ->orWhere('name', 'ilike', "%{$search}%");
                })->orWhere('package_name', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        $reversals = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        return view('admin.reseller.reversed-transactions', compact('reseller', 'reversals'));
    }

    /**
     * Deactivate only — sets client inactive and disables on MikroTik. No financial change.
     */
    public function deactivateResellerClient(Request $request, Reseller $reseller, \App\Models\Client $client)
    {
        abort_unless($client->reseller_id === $reseller->id, 403, 'Client does not belong to this reseller.');

        $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        $oldStatus = $client->status;

        $client->update([
            'status'      => 'inactive',
            'expire_date' => now()->subDay()->toDateString(),
        ]);

        \App\Models\ClientStatusLog::logStatusChange(
            client: $client,
            oldStatus: $oldStatus,
            newStatus: 'inactive',
            reason: $request->remarks ?: "Deactivated by admin from reseller transactions page.",
            source: 'manual',
            changedBy: auth()->id()
        );

        \App\Services\ClientActivityService::log(
            client: $client,
            actionType: 'deactivated',
            oldValue: $oldStatus,
            newValue: 'inactive',
            reason: $request->remarks ?: "Client deactivated by admin (no refund). Reseller: {$reseller->name}.",
            performedBy: auth()->id()
        );

        $mikrotikMsg = '';
        try {
            $device = $client->mikrotikDevice;
            if (!$device) {
                $device = \App\Models\MikrotikDevice::where('is_active', true)
                    ->where('company_id', $reseller->company_id)
                    ->first();
            }
            if ($device) {
                $mkService = new \App\Services\SimpleMikroTikService();
                if ($mkService->connectFromDevice($device)) {
                    $result = $mkService->disableClientByType($client);
                    $mkService->disconnect();
                    $mikrotikMsg = in_array('!done', $result ?? [])
                        ? ' Disabled on MikroTik.'
                        : ' Note: MikroTik disable may have failed.';
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("MikroTik disable failed during deactivate for client {$client->id}: " . $e->getMessage());
        }

        return back()->with('success', "{$client->name} ({$client->username}) deactivated successfully.{$mikrotikMsg}");
    }

    public function loginAsReseller(Reseller $reseller)
    {
        if (!$reseller->user) {
            return back()->with('error', 'This reseller has no login account.');
        }

        $adminId   = auth()->id();
        $adminName = auth()->user()->name;

        auth()->login($reseller->user);

        \Illuminate\Support\Facades\Session::put('impersonate_original_user_id', $adminId);
        \Illuminate\Support\Facades\Session::put('impersonate_original_user_name', $adminName);
        \Illuminate\Support\Facades\Session::put('impersonate_reseller_id', $reseller->id);
        session()->save();

        $redirect = $reseller->type === 'bandwidth'
            ? route('reseller.bandwidth-dashboard')
            : route('reseller.dashboard');

        return redirect($redirect);
    }

    /**
     * Show feature control page for a specific reseller.
     */
    public function featureControl(Reseller $reseller)
    {
        $availableFeatures = Reseller::$availableFeatures;
        $enabledFeatures   = $reseller->getEnabledFeatures();
        return view('admin.reseller.feature-control', compact('reseller', 'availableFeatures', 'enabledFeatures'));
    }

    /**
     * Save feature control settings for a reseller.
     */
    public function saveFeatureControl(Request $request, Reseller $reseller)
    {
        $valid = array_keys(Reseller::$availableFeatures);
        $selected = array_values(array_intersect($request->input('features', []), $valid));

        $reseller->update(['allowed_features' => $selected]);

        return back()->with('success', 'Feature settings saved for ' . $reseller->name . '.');
    }

    public function generateBillNow(\Illuminate\Http\Request $request, Reseller $reseller)
    {
        $billingService = new \App\Services\ResellerBillingService();
        $month = (int)($request->input('month', now()->month));
        $year  = (int)($request->input('year',  now()->year));
        try {
            $billingService->generateMonthlyBill($reseller, $month, $year);
            $monthLabel = \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y');
            return back()->with('success', 'Bill generated successfully for ' . $reseller->name . ' — ' . $monthLabel . '.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Manual bill generation failed for reseller ' . $reseller->id . ': ' . $e->getMessage());
            return back()->with('error', 'Bill generation failed: ' . $e->getMessage());
        }
    }


    public function showGenerateBill(Reseller $reseller)
    {
        return view('admin.reseller.generate-bill', compact('reseller'));
    }
    public function toggleAutoBill(\Illuminate\Http\Request $request, Reseller $reseller)
    {
        $reseller->update(['auto_bill_generate' => !$reseller->auto_bill_generate]);
        $status = $reseller->auto_bill_generate ? 'enabled' : 'disabled';
        return back()->with('success', 'Auto bill generation ' . $status . ' for ' . $reseller->name . '.');
    }

    public function toggleRechargeEnabled(\Illuminate\Http\Request $request, Reseller $reseller)
    {
        $reseller->update(['recharge_enabled' => !$reseller->recharge_enabled]);
        $label  = $reseller->recharge_enabled ? 'ENABLED' : 'DISABLED';
        $type   = $reseller->recharge_enabled ? 'success' : 'warning';
        return back()->with($type, "Instant Recharge {$label} for {$reseller->name}.");
    }

    public function toggleRechargeBlock(Reseller $reseller, \App\Models\Client $client)
    {
        if ($client->reseller_id !== $reseller->id) {
            abort(403, 'Unauthorized');
        }
        $blocked = !$client->recharge_blocked;
        $client->update(['recharge_blocked' => $blocked]);

        $msg = $blocked
            ? "Recharge BLOCKED for client \"{$client->name}\"."
            : "Recharge UNBLOCKED for client \"{$client->name}\".";

        \App\Services\ClientActivityService::log($client, 'recharge_block', null, $blocked ? 'blocked' : 'unblocked', $msg);

        return back()->with($blocked ? 'warning' : 'success', $msg);
    }

}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Zone;
use App\Models\Area;
use App\Models\Box;
use App\Models\Package;
use App\Models\User;
use App\Models\MikrotikDevice;
use App\Models\ActivityLog;
use App\Models\ClientServiceCharge;
use App\Models\Bill;
use App\Services\ClientActivityService;
use App\Services\BillingService;
use App\Services\ClientFilterService;
use App\Events\ClientStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    public function autocomplete(Request $request)
    {
        $search = trim($request->get('q', ''));
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $search = str_replace(['%', '_'], ['\%', '\_'], $search);

        $isNumeric = preg_match('/^[0-9\-\+]+$/', $search);
        $isMac = preg_match('/^[0-9a-fA-F:.\-]+$/', $search) && strlen($search) >= 5;

        $query = Client::where('status', '!=', 'deleted')
            ->select('id', 'name', 'client_id', 'phone', 'username', 'status', 'due_amount');

        if ($isNumeric) {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'ilike', "%{$search}%")
                  ->orWhere('client_id', 'ilike', "%{$search}%")
                  ->orWhere('nid', 'ilike', "%{$search}%");
            });
        } elseif ($isMac) {
            $query->where(function ($q) use ($search) {
                $q->where('mac_address', 'ilike', "%{$search}%")
                  ->orWhere('ip_address', 'ilike', "%{$search}%")
                  ->orWhere('client_id', 'ilike', "%{$search}%");
            });
        } else {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('client_id', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $clients = $query->orderByRaw("CASE WHEN name ILIKE ? THEN 0 WHEN client_id ILIKE ? THEN 1 WHEN phone ILIKE ? THEN 2 ELSE 3 END", ["{$search}%", "{$search}%", "{$search}%"])
            ->limit(10)
            ->get();

        return response()->json($clients->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'client_id' => $c->client_id,
                'phone' => $c->phone,
                'username' => $c->username,
                'status' => $c->status,
                'due' => $c->due_amount ? '৳' . number_format($c->due_amount, 0) : '৳0',
                'url' => route('admin.clients.show', $c->id),
            ];
        }));
    }

    public function searchClientsApi(Request $request)
    {
        $search = trim($request->input('q', ''));
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $query = \DB::table('clients')
            ->where(function($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                  ->orWhere('username', 'ilike', '%' . $search . '%')
                  ->orWhere('client_id', 'ilike', '%' . $search . '%')
                  ->orWhere('phone', 'ilike', '%' . $search . '%');
            })
            ->whereNull('deleted_at');

        $user = auth()->user();
        $isSuperAdmin = $user && ($user->user_type === 'super_admin' || (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')));
        if ($user && $user->user_type === 'reseller') {
            $query->where('reseller_id', $user->id);
        } elseif ($user && $user->user_type === 'sub_reseller') {
            $resellerId = $user->parent_reseller_id ?? $user->id;
            $query->where('reseller_id', $resellerId);
        } elseif ($user && $user->user_type === 'technician') {
            $query->where('zone_id', $user->zone_id);
        } elseif ($user && in_array($user->user_type, ['admin', 'employee'])) {
            $query->where('company_id', $user->company_id);
        }

        if ($isSuperAdmin && $request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $clients = $query->select('id', 'name', 'username', 'client_id', 'phone', 'status', 'mikrotik_profile_name', 'package_id', 'monthly_fee')
            ->orderBy('name')
            ->limit(15)
            ->get();

        $packageIds = $clients->pluck('package_id')->filter()->unique()->values();
        $packages = [];
        if ($packageIds->isNotEmpty()) {
            $packages = \DB::table('packages')->whereIn('id', $packageIds)->pluck('name', 'id');
        }

        $clients->transform(function($c) use ($packages) {
            $c->package_name = $packages[$c->package_id] ?? '-';
            return $c;
        });

        return response()->json($clients);
    }

    public function index(Request $request)
    {
        session(['last_client_list_url' => url()->current()]);
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->user_type === 'super_admin' || (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')));
        
        $query = Client::with(['zone', 'area', 'box', 'package', 'mikrotikDevice', 'reseller']);

        $this->applyZoneFilter($query);

        // Super admin company filters
        if ($isSuperAdmin && $request->filled('company_id')) {
            // Filter by specific company
            $query->where('company_id', $request->company_id);
        } elseif ($isSuperAdmin && $request->filled('no_company')) {
            // Explicitly show super admin's own clients
            $query->where('company_id', $user->company_id);
        } elseif ($isSuperAdmin && !$request->filled('all_co')) {
            // DEFAULT: show super admin's own clients when no filter selected
            $query->where('company_id', $user->company_id);
        }

        // Non-super-admin: always restrict to their own company
        if (!$isSuperAdmin && $user && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        if (!$request->reseller_id) {
            $query->whereNull('reseller_id');
        }
        
        ClientFilterService::applyLocationFilters($query, $request);
        
        if ($request->status === 'due') {
            $query->where('due_amount', '>', 0)->whereNotIn('status', ['deleted']);
        } elseif ($request->status) {
            $query->where('status', $request->status);
        } else {
            $query->whereNotIn('status', ['deleted']);
        }
        
        if ($request->package_id) {
            $query->where('package_id', $request->package_id);
        }
        
        if ($request->connection_type) {
            $query->where('connection_type', $request->connection_type);
        }
        
        if ($request->connection_medium) {
            $query->where('connection_medium', $request->connection_medium);
        }
        
        if ($request->mikrotik_device_id) {
            $query->where('mikrotik_device_id', $request->mikrotik_device_id);
        }
        
        if ($request->reseller_id) {
            $query->where('reseller_id', $request->reseller_id);
        }
        
        if ($request->billing_date) {
            $query->where('billing_date', $request->billing_date);
        }
        
        if ($request->due_filter == 'with_due') {
            $query->where('due_amount', '>', 0);
        } elseif ($request->due_filter == 'no_due') {
            $query->where(function($q) {
                $q->where('due_amount', '<=', 0)->orWhereNull('due_amount');
            });
        }
        
        if ($request->mac_filter == 'with_mac') {
            $query->whereNotNull('mac_address')->where('mac_address', '!=', '');
        } elseif ($request->mac_filter == 'no_mac') {
            $query->where(function($q) {
                $q->whereNull('mac_address')->orWhere('mac_address', '');
            });
        }
        
        if ($request->free_filter == 'free') {
            $query->where('is_free', true);
        } elseif ($request->free_filter == 'paid') {
            $query->where(function($q) {
                $q->where('is_free', false)->orWhereNull('is_free');
            });
        }

        if ($request->free_monthly == '1') {
            $query->where('is_free', true);
        }

        if ($request->inst_due == '1') {
            $query->where('is_free', false)
                  ->where('signup_fee_paid', false)
                  ->where('installation_charge', '>', 0);
        }
        
        if ($request->onu_filter) {
            if ($request->onu_filter == 'company') {
                $query->where('onu_required', true);
            } elseif ($request->onu_filter == 'client') {
                $query->where(function($q) {
                    $q->where('onu_required', false)->orWhereNull('onu_required');
                });
            }
        }
        
        if ($request->billing_status) {
            $currentMonth = now()->month;
            $currentYear = now()->year;
            if ($request->billing_status == 'generated') {
                $query->whereHas('bills', function($q) use ($currentMonth, $currentYear) {
                    $q->whereMonth('bill_date', $currentMonth)->whereYear('bill_date', $currentYear);
                });
            } elseif ($request->billing_status == 'not_generated') {
                $query->whereDoesntHave('bills', function($q) use ($currentMonth, $currentYear) {
                    $q->whereMonth('bill_date', $currentMonth)->whereYear('bill_date', $currentYear);
                });
            }
        }
        
        if ($request->payment_status) {
            switch ($request->payment_status) {
                case 'paid':
                    $query->where(function($q) { $q->where('due_amount', '<=', 0)->orWhereNull('due_amount'); });
                    break;
                case 'unpaid':
                    $query->where('due_amount', '>', 0);
                    break;
                case 'partial':
                    $query->where('due_amount', '>', 0)->where('balance', '>', 0);
                    break;
                case 'advance':
                    $query->where('balance', '>', 0)->where(function($q) { $q->where('due_amount', '<=', 0)->orWhereNull('due_amount'); });
                    break;
                case 'overdue':
                    $query->where('due_amount', '>', 0)->where('status', 'active');
                    break;
            }
        }
        
        if ($request->mikrotik_status) {
            if ($request->mikrotik_status === 'disabled') {
                $query->whereRaw("LOWER(mikrotik_profile_name) = 'inactive'");
            } elseif ($request->mikrotik_status === 'enabled') {
                $query->where(function($q) {
                    $q->whereNull('mikrotik_profile_name')
                      ->orWhereRaw("LOWER(mikrotik_profile_name) != 'inactive'");
                });
            }
        }
        
        if ($request->client_type) {
            $query->where('client_type', $request->client_type);
        }
        
        if ($request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        if ($request->custom_status) {
            $query->where('custom_status', $request->custom_status);
        }
        
        if ($request->date) {
            $query->whereDate('connection_date', $request->date);
        }
        
        if ($request->date_from) {
            $query->whereDate('connection_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('connection_date', '<=', $request->date_to);
        }
        
        if ($request->expire_from) {
            $query->whereDate('expire_date', '>=', $request->expire_from);
        }
        if ($request->expire_to) {
            $query->whereDate('expire_date', '<=', $request->expire_to);
        }
        
        if ($request->effective_from) {
            $query->whereDate('effective_to', '>=', $request->effective_from);
        }
        if ($request->effective_to_date) {
            $query->whereDate('effective_to', '<=', $request->effective_to_date);
        }
        
        if ($request->non_effective_from) {
            $query->whereDate('non_effective_from', '>=', $request->non_effective_from);
        }
        if ($request->non_effective_to) {
            $query->whereDate('non_effective_from', '<=', $request->non_effective_to);
        }
        
        if ($request->search) {
            $search = str_replace(['%', '_'], ['\%', '\_'], trim($request->search));
            $isNumeric = preg_match('/^[0-9\-\+]+$/', $search);
            $isMac = preg_match('/^[0-9a-fA-F:.\-]+$/', $search) && strlen($search) >= 5;

            $query->where(function($q) use ($search, $isNumeric, $isMac) {
                if ($isNumeric) {
                    $q->where('phone', 'ilike', "%{$search}%")
                      ->orWhere('client_id', 'ilike', "%{$search}%")
                      ->orWhere('nid', 'ilike', "%{$search}%");
                } elseif ($isMac) {
                    $q->where('mac_address', 'ilike', "%{$search}%")
                      ->orWhere('ip_address', 'ilike', "%{$search}%")
                      ->orWhere('client_id', 'ilike', "%{$search}%");
                } else {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('client_id', 'ilike', "%{$search}%")
                      ->orWhere('phone', 'ilike', "%{$search}%")
                      ->orWhere('username', 'ilike', "%{$search}%")
                      ->orWhere('email', 'ilike', "%{$search}%")
                      ->orWhere('mac_address', 'ilike', "%{$search}%")
                      ->orWhere('nid', 'ilike', "%{$search}%")
                      ->orWhere('address', 'ilike', "%{$search}%");
                }
            });
        }
        
        if ($request->sort_by) {
            // Handle name_desc as special alias
            if ($request->sort_by === 'name_desc') {
                $query->orderBy('name', 'desc');
            } else {
                $sortDefaults = [
                    'name'            => 'asc',
                    'created_at'      => 'desc',
                    'due_amount'      => 'desc',
                    'monthly_fee'     => 'desc',
                    'connection_date' => 'desc',
                    'expire_date'     => 'asc',
                    'billing_date'    => 'asc',
                    'client_id'       => 'asc',
                    'last_online'     => 'desc',
                    'status'          => 'asc',
                    'zone_id'         => 'asc',
                ];
                if (array_key_exists($request->sort_by, $sortDefaults)) {
                    $defaultDir = $sortDefaults[$request->sort_by];
                    $dir = ($request->sort_dir === 'asc' || $request->sort_dir === 'desc') ? $request->sort_dir : $defaultDir;
                    $query->orderBy($request->sort_by, $dir);
                } else {
                    $query->orderBy('created_at', 'desc');
                }
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Per-page: use request param (if valid), else session memory, else default 20
        if ($request->has('per_page') && in_array((int)$request->per_page, [20, 50, 100, 200])) {
            $perPage = (int)$request->per_page;
            session(['client_list_per_page' => $perPage]);
        } else {
            $perPage = in_array((int)session('client_list_per_page'), [20, 50, 100, 200])
                ? (int)session('client_list_per_page')
                : 20;
        }
        $clients = $query->paginate($perPage);
        
        // Determine which company's data to show in filter dropdowns (super admin context)
        $filterCompanyId = null;
        if ($isSuperAdmin) {
            if ($request->filled('company_id')) {
                $filterCompanyId = $request->company_id;
            } elseif (!$request->filled('all_co')) {
                $filterCompanyId = $user->company_id;
            }
            // all_co = null means show all companies' data
        } else {
            $filterCompanyId = $user->company_id;
        }

        $zones = Zone::whereNull('reseller_id')->where('is_active', true)
            ->when($filterCompanyId, fn($q) => $q->where('company_id', $filterCompanyId))
            ->orderBy('name')->get();
        $packages = Package::where('is_active', true)
            ->when($filterCompanyId, fn($q) => $q->where('company_id', $filterCompanyId))
            ->orderBy('name')->get();
        $companies = $isSuperAdmin ? \App\Models\Company::orderBy('name')->get() : collect();
        $boxes = Box::where('status', 'active')
            ->when($filterCompanyId, fn($q) => $q->where('company_id', $filterCompanyId))
            ->orderBy('name')->get();
        $areas = Area::whereNull('reseller_id')->where('is_active', true)
            ->when($filterCompanyId, fn($q) => $q->where('company_id', $filterCompanyId))
            ->orderBy('name')->get();
        $mikrotikDevices = MikrotikDevice::where('is_active', true)
            ->when($filterCompanyId, fn($q) => $q->whereHas('zone', fn($zq) => $zq->where('zones.company_id', $filterCompanyId)))
            ->orderBy('name')->get();
        $resellers = Client::whereNotNull('reseller_id')->distinct('reseller_id')->pluck('reseller_id');
        $resellerUsers = \App\Models\User::whereIn('id', $resellers)->orderBy('name')->get();
        
        $connectionTypes = Client::whereNotNull('connection_type')->where('connection_type', '!=', '')->distinct()->pluck('connection_type');
        $connectionMediums = Client::whereNotNull('connection_medium')->where('connection_medium', '!=', '')->distinct()->pluck('connection_medium');
        $billingDates = Client::whereNotNull('billing_date')->distinct()->orderBy('billing_date')->pluck('billing_date');
        $staffUsers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['admin', 'technician', 'manager', 'staff', 'super-admin']);
        })->orderBy('name')->get();
        $customStatuses = Client::whereNotNull('custom_status')->where('custom_status', '!=', '')->distinct()->orderBy('custom_status')->pluck('custom_status');
        
        $hasAdvancedFilter = $request->status || $request->package_id || $request->connection_type || 
            $request->connection_medium || $request->mikrotik_device_id || $request->reseller_id || 
            $request->billing_date || $request->due_filter || $request->mac_filter || $request->free_filter || $request->free_monthly || 
            $request->billing_status || $request->payment_status || $request->mikrotik_status || 
            $request->client_type || $request->assigned_to || $request->custom_status || $request->date || 
            $request->date_from || $request->date_to || $request->expire_from || $request->expire_to || 
            $request->effective_from || $request->effective_to_date || $request->non_effective_from || 
            $request->non_effective_to || $request->zone_id || $request->area_id || $request->box_id;

        if ($hasAdvancedFilter) {
            $countQuery = Client::with([]);
            if ($isSuperAdmin && $request->filled('company_id')) {
                $countQuery->where('company_id', $request->company_id);
            } elseif ($isSuperAdmin && $request->filled('no_company')) {
                $countQuery->where('company_id', $user->company_id);
            } elseif ($isSuperAdmin && !$request->filled('all_co')) {
                $countQuery->where('company_id', $user->company_id);
            }
            ClientFilterService::applyLocationFilters($countQuery, $request);
            if ($request->status === 'due') {
                $countQuery->where('due_amount', '>', 0)->whereNotIn('status', ['deleted']);
            } elseif ($request->status) {
                $countQuery->where('status', $request->status);
            } else {
                $countQuery->whereNotIn('status', ['deleted']);
            }
            if ($request->package_id) { $countQuery->where('package_id', $request->package_id); }
            if ($request->connection_type) { $countQuery->where('connection_type', $request->connection_type); }
            if ($request->connection_medium) { $countQuery->where('connection_medium', $request->connection_medium); }
            if ($request->mikrotik_device_id) { $countQuery->where('mikrotik_device_id', $request->mikrotik_device_id); }
            if ($request->reseller_id) { $countQuery->where('reseller_id', $request->reseller_id); }
            if ($request->billing_date) { $countQuery->where('billing_date', $request->billing_date); }
            if ($request->due_filter == 'with_due') { $countQuery->where('due_amount', '>', 0); }
            elseif ($request->due_filter == 'no_due') { $countQuery->where(function($q) { $q->where('due_amount', '<=', 0)->orWhereNull('due_amount'); }); }
            if ($request->mac_filter == 'with_mac') { $countQuery->whereNotNull('mac_address')->where('mac_address', '!=', ''); }
            elseif ($request->mac_filter == 'no_mac') { $countQuery->where(function($q) { $q->whereNull('mac_address')->orWhere('mac_address', ''); }); }
            if ($request->free_filter == 'free') { $countQuery->where('is_free', true); }
            elseif ($request->free_filter == 'paid') { $countQuery->where(function($q) { $q->where('is_free', false)->orWhereNull('is_free'); }); }
            if ($request->free_monthly == '1') { $countQuery->where('is_free', true); }
            if ($request->inst_due == '1') { $countQuery->where('is_free', false)->where('signup_fee_paid', false)->where('installation_charge', '>', 0); }
            if ($request->onu_filter == 'company') { $countQuery->where('onu_required', true); }
            elseif ($request->onu_filter == 'client') { $countQuery->where(function($q) { $q->where('onu_required', false)->orWhereNull('onu_required'); }); }
            if ($request->billing_status) {
                $cm = now()->month; $cy = now()->year;
                if ($request->billing_status == 'generated') { $countQuery->whereHas('bills', function($q) use ($cm, $cy) { $q->whereMonth('bill_date', $cm)->whereYear('bill_date', $cy); }); }
                elseif ($request->billing_status == 'not_generated') { $countQuery->whereDoesntHave('bills', function($q) use ($cm, $cy) { $q->whereMonth('bill_date', $cm)->whereYear('bill_date', $cy); }); }
            }
            if ($request->payment_status) {
                switch ($request->payment_status) {
                    case 'paid': $countQuery->where(function($q) { $q->where('due_amount', '<=', 0)->orWhereNull('due_amount'); }); break;
                    case 'unpaid': $countQuery->where('due_amount', '>', 0); break;
                    case 'partial': $countQuery->where('due_amount', '>', 0)->where('balance', '>', 0); break;
                    case 'advance': $countQuery->where('balance', '>', 0)->where(function($q) { $q->where('due_amount', '<=', 0)->orWhereNull('due_amount'); }); break;
                    case 'overdue': $countQuery->where('due_amount', '>', 0)->where('status', 'active'); break;
                }
            }
            if ($request->mikrotik_status) {
                if ($request->mikrotik_status === 'disabled') {
                    $countQuery->whereRaw("LOWER(mikrotik_profile_name) = 'inactive'");
                } elseif ($request->mikrotik_status === 'enabled') {
                    $countQuery->where(function($q) {
                        $q->whereNull('mikrotik_profile_name')
                          ->orWhereRaw("LOWER(mikrotik_profile_name) != 'inactive'");
                    });
                }
            }
            if ($request->client_type) { $countQuery->where('client_type', $request->client_type); }
            if ($request->assigned_to) { $countQuery->where('assigned_to', $request->assigned_to); }
            if ($request->custom_status) { $countQuery->where('custom_status', $request->custom_status); }
            if ($request->date_from) { $countQuery->whereDate('connection_date', '>=', $request->date_from); }
            if ($request->date_to) { $countQuery->whereDate('connection_date', '<=', $request->date_to); }
            if ($request->expire_from) { $countQuery->whereDate('expire_date', '>=', $request->expire_from); }
            if ($request->expire_to) { $countQuery->whereDate('expire_date', '<=', $request->expire_to); }
            if ($request->effective_from) { $countQuery->whereDate('effective_to', '>=', $request->effective_from); }
            if ($request->effective_to_date) { $countQuery->whereDate('effective_to', '<=', $request->effective_to_date); }
            if ($request->non_effective_from) { $countQuery->whereDate('non_effective_from', '>=', $request->non_effective_from); }
            if ($request->non_effective_to) { $countQuery->whereDate('non_effective_from', '<=', $request->non_effective_to); }

            $totalClients = (clone $countQuery)->count();
            $activeClients = (clone $countQuery)->where('status', 'active')->count();
            $inactiveClients = (clone $countQuery)->where('status', 'inactive')->count();
            $dueClients = (clone $countQuery)->where('due_amount', '>', 0)->count();
            $onlineClients = (clone $countQuery)->whereNotNull('session_uptime')->where('session_uptime', '!=', '')->count();
            $offlineClients = $totalClients - $onlineClients;
            $instFreeClients = (clone $countQuery)->where('is_free', true)->count();
            $instDueClients = (clone $countQuery)->where('is_free', false)->where('signup_fee_paid', false)->where('installation_charge', '>', 0)->count();
            $totalDueAmount = (clone $countQuery)->sum('due_amount');
            $totalMonthlyRevenue = (clone $countQuery)->where('status', 'active')->sum('monthly_fee');
            $monthlyNewClients = (clone $countQuery)->whereMonth('connection_date', now()->month)->whereYear('connection_date', now()->year)->count();
            $monthlyInactiveClients = (clone $countQuery)->where('status', 'inactive')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count();
            $todayInactiveClients = (clone $countQuery)->where('status', 'inactive')->whereDate('last_inactive_date', today())->count();
            $todayActiveClients = (clone $countQuery)->where('status', 'active')->whereDate('updated_at', today())->count();
            $todayNewClients = (clone $countQuery)->whereDate('connection_date', today())->count();
        } else {
            $baseQ = Client::where('status', '!=', 'deleted')->whereNull('reseller_id');
            if ($isSuperAdmin && $request->filled('company_id')) {
                $baseQ->where('company_id', $request->company_id);
            } elseif ($isSuperAdmin && $request->filled('no_company')) {
                $baseQ->where('company_id', $user->company_id);
            } elseif ($isSuperAdmin && !$request->filled('all_co')) {
                $baseQ->where('company_id', $user->company_id);
            } elseif (!$isSuperAdmin) {
                $baseQ->where('company_id', $user->company_id);
            }
            $totalClients = (clone $baseQ)->count();
            $activeClients = (clone $baseQ)->where('status', 'active')->count();
            $inactiveClients = (clone $baseQ)->where('status', 'inactive')->count();
            $dueClients = (clone $baseQ)->where('due_amount', '>', 0)->count();
            $onlineClients = (clone $baseQ)->whereNotNull('session_uptime')->where('session_uptime', '!=', '')->count();
            $offlineClients = $totalClients - $onlineClients;
            $instFreeClients = (clone $baseQ)->where('is_free', true)->count();
            $instDueClients = (clone $baseQ)->where('is_free', false)->where('signup_fee_paid', false)->where('installation_charge', '>', 0)->count();
            $totalDueAmount = (clone $baseQ)->sum('due_amount');
            $totalMonthlyRevenue = (clone $baseQ)->where('status', 'active')->sum('monthly_fee');
            $monthlyNewClients = (clone $baseQ)->whereMonth('connection_date', now()->month)->whereYear('connection_date', now()->year)->count();
            $monthlyInactiveClients = (clone $baseQ)->where('status', 'inactive')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count();
            $todayInactiveClients = (clone $baseQ)->where('status', 'inactive')->whereDate('last_inactive_date', today())->count();
            $todayActiveClients = (clone $baseQ)->where('status', 'active')->whereDate('updated_at', today())->count();
            $todayNewClients = (clone $baseQ)->whereDate('connection_date', today())->count();
        }
        
        $filteredCount = $clients->total();

        $selectedAreas = [];
        if ($request->zone_id) {
            $selectedAreas = Area::where('zone_id', $request->zone_id)->where('is_active', true)->orderBy('name')->get();
        }
        $selectedBoxes = [];
        if ($request->area_id) {
            $selectedBoxes = Box::where('area_id', $request->area_id)->where('status', 'active')->orderBy('name')->get();
        }
        
        $_profileCompanyId = auth()->user()?->company_id;
        $availableProfiles = \App\Models\MikrotikProfile::withoutGlobalScopes()
            ->where('is_active', true)
            ->when($_profileCompanyId && !auth()->user()?->isSuperAdmin(), fn($q) => $q->where('company_id', $_profileCompanyId))
            ->orderBy('name')
            ->pluck('name');

        $paymentReasons = \App\Models\DropdownOption::getOptions('payment_reasons');
        $deadlineReasons = \App\Models\DropdownOption::getOptions('deadline_extend_reasons');
        $disableReasons = \App\Models\DropdownOption::getOptions('client_disable_reasons');
        $enableReasons = \App\Models\DropdownOption::getOptions('client_enable_reasons');

        return response()->view('admin.clients.index', compact(
            'clients', 'zones', 'packages', 'boxes', 'areas', 'mikrotikDevices',
            'resellerUsers', 'connectionTypes', 'connectionMediums', 'billingDates',
            'totalClients', 'activeClients', 'inactiveClients', 'dueClients', 'onlineClients', 'offlineClients', 'instFreeClients', 'instDueClients', 'totalDueAmount', 'totalMonthlyRevenue', 'monthlyNewClients', 'monthlyInactiveClients', 'todayInactiveClients', 'todayActiveClients', 'todayNewClients',
            'filteredCount',
            'selectedAreas', 'selectedBoxes', 'perPage', 'staffUsers', 'customStatuses', 'availableProfiles',
            'paymentReasons', 'deadlineReasons', 'disableReasons', 'enableReasons',
            'companies', 'isSuperAdmin'
        ))->header('Cache-Control', 'no-cache, no-store, must-revalidate')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }

    public function companyStatsApi(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->user_type === 'super_admin' || (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')));
        if (!$isSuperAdmin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $q = Client::where('status', '!=', 'deleted')->whereNull('reseller_id');

        if ($request->filled('company_id')) {
            $q->where('company_id', $request->company_id);
        } elseif ($request->filled('all_co')) {
            // no company filter – show all
        } else {
            $q->where('company_id', $user->company_id);
        }

        $total    = (clone $q)->count();
        $online   = (clone $q)->where('connection_status', 'online')->count();
        $offline  = $total - $online;

        return response()->json([
            'total'            => $total,
            'active'           => (clone $q)->where('status', 'active')->count(),
            'inactive'         => (clone $q)->where('status', 'inactive')->count(),
            'due'              => (clone $q)->where('due_amount', '>', 0)->count(),
            'online'           => $online,
            'offline'          => $offline,
            'inst_free'        => (clone $q)->where('is_free', true)->count(),
            'inst_due'         => (clone $q)->where('is_free', false)->where('signup_fee_paid', false)->where('installation_charge', '>', 0)->count(),
            'monthly_new'      => (clone $q)->whereMonth('connection_date', now()->month)->whereYear('connection_date', now()->year)->count(),
            'monthly_inactive' => (clone $q)->where('status', 'inactive')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count(),
            'today_inactive'   => (clone $q)->where('status', 'inactive')->whereDate('last_inactive_date', today())->count(),
            'today_active'     => (clone $q)->where('status', 'active')->whereDate('updated_at', today())->count(),
            'today_new'        => (clone $q)->whereDate('connection_date', today())->count(),
        ]);
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'integer|exists:clients,id',
            'action' => 'required|in:activate,deactivate,change_package,change_zone,change_area,change_box,mikrotik_enable,mikrotik_disable,mikrotik_sync',
            'package_id' => 'required_if:action,change_package|nullable|exists:packages,id',
            'zone_id' => ['required_if:action,change_zone', 'nullable', \Illuminate\Validation\Rule::exists('zones', 'id')->where('company_id', auth()->user()->company_id)],
            'area_id' => ['required_if:action,change_area', 'nullable', \Illuminate\Validation\Rule::exists('areas', 'id')->where('company_id', auth()->user()->company_id)],
            'box_id' => ['required_if:action,change_box', 'nullable', \Illuminate\Validation\Rule::exists('boxes', 'id')->where('company_id', auth()->user()->company_id)],
        ]);

        $query = Client::whereIn('id', $request->client_ids)
            ->where('status', '!=', 'deleted');
        $this->applyZoneFilter($query);
        $clients = $query->get();

        if ($clients->isEmpty()) {
            return back()->with('error', 'No valid clients selected.');
        }

        $count = 0;

        switch ($request->action) {
            case 'activate':
                foreach ($clients as $client) {
                    if ($client->status !== 'active') {
                        $oldStatus = $client->status;
                        $client->status = 'active';
                        $client->last_active_date = now();
                        $client->last_status_change = now();
                        $client->auto_inactive_note = null;
                        $client->save();
                        // Restore MikroTik profile and enable on router
                        try {
                            $mkService = new \App\Services\SimpleMikroTikService();
                            $mkService->syncClientStatusOnRouter($client, 'active', $oldStatus, 'Bulk activate', auth()->id());
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::warning("Bulk activate MikroTik sync failed for client {$client->id}: " . $e->getMessage());
                        }
                        $count++;
                    }
                }
                return back()->with('success', "{$count} client(s) activated successfully.");

            case 'deactivate':
                foreach ($clients as $client) {
                    if ($client->status !== 'inactive') {
                        $oldStatus = $client->status;
                        $client->status = 'inactive';
                        $client->last_inactive_date = now();
                        $client->connection_status = 'offline';
                        $client->save();
                        // Disable MikroTik profile on router
                        try {
                            $mkService = new \App\Services\SimpleMikroTikService();
                            $mkService->syncClientStatusOnRouter($client, 'inactive', $oldStatus, 'Bulk deactivate', auth()->id());
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::warning("Bulk deactivate MikroTik sync failed for client {$client->id}: " . $e->getMessage());
                        }
                        $count++;
                    }
                }
                return back()->with('success', "{$count} client(s) deactivated successfully.");

            case 'change_package':
                $newPackage = Package::findOrFail($request->package_id);
                $billingService = app(\App\Services\BillingService::class);
                foreach ($clients as $client) {
                    if ($client->package_id != $newPackage->id) {
                        $billingService->updateCurrentBillForPackageChange($client, $newPackage, 'admin_bulk');
                        $client->package_id = $newPackage->id;
                        $client->monthly_fee = $newPackage->price;
                        $client->save();
                        $count++;
                    }
                }
                return back()->with('success', "{$count} client(s) moved to package: {$newPackage->name}.");

            case 'change_zone':
                $newZone = Zone::findOrFail($request->zone_id);
                foreach ($clients as $client) {
                    if ($client->zone_id != $newZone->id) {
                        $client->zone_id = $newZone->id;
                        $client->area_id = null;
                        $client->box_id = null;
                        $client->save();
                        $count++;
                    }
                }
                return back()->with('success', "{$count} client(s) moved to zone: {$newZone->name}.");

            case 'change_area':
                $newArea = \App\Models\Area::findOrFail($request->area_id);
                foreach ($clients as $client) {
                    if ($client->area_id != $newArea->id) {
                        $client->zone_id = $newArea->zone_id;
                        $client->area_id = $newArea->id;
                        $client->box_id = null;
                        $client->save();
                        $count++;
                    }
                }
                return back()->with('success', "{$count} client(s) moved to sub zone: {$newArea->name}.");

            case 'change_box':
                $newBox = \App\Models\Box::findOrFail($request->box_id);
                foreach ($clients as $client) {
                    if ($client->box_id != $newBox->id) {
                        $client->zone_id = $newBox->area->zone_id ?? $client->zone_id;
                        $client->area_id = $newBox->area_id;
                        $client->box_id = $newBox->id;
                        $client->save();
                        $count++;
                    }
                }
                return back()->with('success', "{$count} client(s) moved to TJ/Box: {$newBox->name}.");

            case 'mikrotik_disable':
                $inactiveProfileName = 'Inactive';
                $mikrotikService = new \App\Services\SimpleMikroTikService();
                foreach ($clients as $client) {
                    $currentProfile = $client->mikrotik_profile_name
                        ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);
                    if (!$currentProfile || strtolower($currentProfile) === strtolower($inactiveProfileName)) {
                        continue;
                    }
                    $device = $client->mikrotikDevice ?? \App\Models\MikrotikDevice::where('is_active', true)->first();
                    if ($device && $client->username) {
                        try {
                            if ($mikrotikService->connectFromDevice($device)) {
                                $mikrotikService->changePPPoEProfile($client->username, $inactiveProfileName);
                                $mikrotikService->disconnect();
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Bulk MikroTik disable failed for {$client->username}: " . $e->getMessage());
                        }
                    }
                    $client->update([
                        'previous_mikrotik_profile' => $currentProfile,
                        'mikrotik_profile_name' => $inactiveProfileName,
                    ]);
                    \App\Models\ClientStatusLog::logStatusChange(
                        client: $client,
                        oldStatus: $client->status,
                        newStatus: $client->status,
                        reason: "Bulk MikroTik disable: profile set to {$inactiveProfileName}",
                        source: 'manual',
                        changedBy: auth()->id()
                    );
                    $count++;
                }
                return back()->with('success', "{$count} client(s) MikroTik profile set to Inactive.");

            case 'mikrotik_enable':
                $inactiveProfileName = 'Inactive';
                $mikrotikService = new \App\Services\SimpleMikroTikService();
                foreach ($clients as $client) {
                    $currentProfile = $client->mikrotik_profile_name;
                    if (!$currentProfile || strtolower($currentProfile) !== strtolower($inactiveProfileName)) {
                        continue;
                    }
                    $newProfile = $client->previous_mikrotik_profile
                        ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);
                    if (!$newProfile) continue;

                    $device = $client->mikrotikDevice ?? \App\Models\MikrotikDevice::where('is_active', true)->first();
                    if ($device && $client->username) {
                        try {
                            if ($mikrotikService->connectFromDevice($device)) {
                                $mikrotikService->changePPPoEProfile($client->username, $newProfile);
                                $mikrotikService->disconnect();
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Bulk MikroTik enable failed for {$client->username}: " . $e->getMessage());
                        }
                    }
                    $client->update([
                        'mikrotik_profile_name' => $newProfile,
                        'previous_mikrotik_profile' => null,
                    ]);
                    \App\Models\ClientStatusLog::logStatusChange(
                        client: $client,
                        oldStatus: $client->status,
                        newStatus: $client->status,
                        reason: "Bulk MikroTik enable: profile restored to {$newProfile}",
                        source: 'manual',
                        changedBy: auth()->id()
                    );
                    $count++;
                }
                return back()->with('success', "{$count} client(s) MikroTik profile restored to active.");

            case 'mikrotik_sync':
                $mikrotikService = new \App\Services\SimpleMikroTikService();
                $synced = 0;
                $failed = 0;
                foreach ($clients as $client) {
                    $profileToSync = $client->mikrotik_profile_name
                        ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);
                    if (!$profileToSync || !$client->username) continue;

                    $device = $client->mikrotikDevice ?? \App\Models\MikrotikDevice::where('is_active', true)->first();
                    if (!$device) continue;

                    try {
                        if ($mikrotikService->connectFromDevice($device)) {
                            $result = $mikrotikService->changePPPoEProfile($client->username, $profileToSync);
                            $mikrotikService->disconnect();
                            if ($result) {
                                $synced++;
                            } else {
                                $failed++;
                            }
                        } else {
                            $failed++;
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Bulk MikroTik sync failed for {$client->username}: " . $e->getMessage());
                        $failed++;
                    }
                    $count++;
                }
                $msg = "{$synced} client(s) MikroTik profile synced successfully.";
                if ($failed > 0) $msg .= " {$failed} failed.";
                return back()->with('success', $msg);
        }

        return back()->with('error', 'Invalid action.');
    }


    public function exportFilteredClients(Request $request)
    {
        $query = Client::with(['zone', 'area', 'box', 'package', 'mikrotikDevice', 'reseller', 'assignedTo'])
            ->where('status', '!=', 'deleted');

        $this->applyZoneFilter($query);

        if (!$request->reseller_id) {
            $query->whereNull('reseller_id');
        }

        \App\Services\ClientFilterService::applyLocationFilters($query, $request);

        if ($request->status === 'due') {
            $query->where('due_amount', '>', 0)->whereNotIn('status', ['deleted']);
        } elseif ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->package_id) { $query->where('package_id', $request->package_id); }
        if ($request->connection_type) { $query->where('connection_type', $request->connection_type); }
        if ($request->connection_medium) { $query->where('connection_medium', $request->connection_medium); }
        if ($request->mikrotik_device_id) { $query->where('mikrotik_device_id', $request->mikrotik_device_id); }
        if ($request->reseller_id) { $query->where('reseller_id', $request->reseller_id); }
        if ($request->billing_date) { $query->where('billing_date', $request->billing_date); }
        if ($request->assigned_to) { $query->where('assigned_to', $request->assigned_to); }
        if ($request->expire_from) { $query->whereDate('expire_date', '>=', $request->expire_from); }
        if ($request->expire_to) { $query->whereDate('expire_date', '<=', $request->expire_to); }
        if ($request->inst_due == '1') {
            $query->where('is_free', false)->where('signup_fee_paid', false)->where('installation_charge', '>', 0);
        }
        if ($request->billing_status) {
            $currentMonth = now()->month; $currentYear = now()->year;
            if ($request->billing_status == 'generated') {
                $query->whereHas('bills', function($q) use ($currentMonth, $currentYear) {
                    $q->whereMonth('bill_date', $currentMonth)->whereYear('bill_date', $currentYear);
                });
            } elseif ($request->billing_status == 'not_generated') {
                $query->whereDoesntHave('bills', function($q) use ($currentMonth, $currentYear) {
                    $q->whereMonth('bill_date', $currentMonth)->whereYear('bill_date', $currentYear);
                });
            }
        }
        if ($request->payment_status) {
            switch ($request->payment_status) {
                case 'paid': $query->where(function($q) { $q->where('due_amount', '<=', 0)->orWhereNull('due_amount'); }); break;
                case 'unpaid': $query->where('due_amount', '>', 0); break;
                case 'overdue': $query->where('due_amount', '>', 0)->where('status', 'active'); break;
            }
        }
        if ($request->mikrotik_status === 'disabled') {
            $query->whereRaw("LOWER(mikrotik_profile_name) = 'inactive'");
        } elseif ($request->mikrotik_status === 'enabled') {
            $query->where(function($q) { $q->whereNull('mikrotik_profile_name')->orWhereRaw("LOWER(mikrotik_profile_name) != 'inactive'"); });
        }
        if ($request->client_type) { $query->where('client_type', $request->client_type); }

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('client_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $clients = $query->orderBy('name')->get();

        $sanitize = function($val) {
            $val = (string) $val;
            if ($val !== '' && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"])) {
                $val = "'" . $val;
            }
            return '"' . str_replace('"', '""', $val) . '"';
        };

        $statusLabel = $request->status ? strtoupper($request->status) : 'ALL';
        $filename = 'clients_' . $statusLabel . '_' . date('Y-m-d') . '.csv';

        $csv = "Client ID,Name,Phone,Username,Zone,Sub Zone,TJ/Box,Package,Monthly Fee,Due,Balance,Status,Billing Date,Expire Date,Connection Date,Cable Type,MikroTik,Reseller,Assigned Staff\n";
        foreach ($clients as $c) {
            $csv .= implode(',', [
                $sanitize($c->client_id ?? $c->id),
                $sanitize($c->name),
                $sanitize($c->phone ?? ''),
                $sanitize($c->username ?? ''),
                $sanitize($c->zone->name ?? ''),
                $sanitize($c->area->name ?? ''),
                $sanitize($c->box->name ?? ''),
                $sanitize($c->package->name ?? ''),
                $c->monthly_fee ?? 0,
                $c->due_amount ?? 0,
                $c->balance ?? 0,
                $sanitize($c->status),
                $c->billing_date ?? '',
                $c->expire_date ? \Carbon\Carbon::parse($c->expire_date)->format('d-m-Y') : '',
                $c->connection_date ? \Carbon\Carbon::parse($c->connection_date)->format('d-m-Y') : '',
                $sanitize($c->connection_medium ?? ''),
                $sanitize($c->mikrotikDevice->name ?? ''),
                $sanitize($c->reseller->name ?? ''),
                $sanitize($c->assignedTo->name ?? ''),
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportClients(Request $request)
    {
        $query = Client::with(['zone', 'area', 'package', 'mikrotikDevice', 'reseller'])
            ->where('status', '!=', 'deleted');
        $this->applyZoneFilter($query);

        if ($request->has('client_ids') && is_array($request->client_ids)) {
            $query->whereIn('id', $request->client_ids);
        }

        $clients = $query->orderBy('created_at', 'desc')->get();

        $sanitize = function($val) {
            $val = (string) $val;
            if ($val !== '' && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"])) {
                $val = "'" . $val;
            }
            return '"' . str_replace('"', '""', $val) . '"';
        };

        $csv = "Client ID,Name,Username,Phone,Zone,Sub Zone,Address,Package,Monthly Fee,Due,Status,Billing Date,Expire Date,Connection Date,MikroTik\n";
        foreach ($clients as $c) {
            $csv .= implode(',', [
                $sanitize($c->client_id ?? $c->id),
                $sanitize($c->name),
                $sanitize($c->username ?? ''),
                $sanitize($c->phone ?? ''),
                $sanitize($c->zone->name ?? ''),
                $sanitize($c->area->name ?? ''),
                $sanitize($c->address ?? ''),
                $sanitize($c->package->name ?? ''),
                $c->monthly_fee ?? 0,
                $c->due_amount ?? 0,
                $sanitize($c->status),
                $c->billing_date ?? '',
                $c->expire_date ? \Carbon\Carbon::parse($c->expire_date)->format('d-m-Y') : '',
                $c->connection_date ? \Carbon\Carbon::parse($c->connection_date)->format('d-m-Y') : '',
                $sanitize($c->mikrotikDevice->name ?? ''),
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="clients_' . date('Y-m-d') . '.csv"',
        ]);
    }

    public function suggestCredentials(Request $request)
    {
        $nextClientCode = Client::generateClientId();

        $lastUsername = Client::getLastGeneratedUsername();
        $nextUsername = null;

        if ($lastUsername && preg_match('/^(\D*)(\d+)$/', $lastUsername, $m)) {
            $nextNum = (int)$m[2] + 1;
            $nextUsername = $m[1] . str_pad((string)$nextNum, strlen($m[2]), '0', STR_PAD_LEFT);
        } elseif ($lastUsername) {
            $nextUsername = $lastUsername . '2';
        } else {
            $nextUsername = 'user' . str_pad((string)(Client::withoutGlobalScopes()->count() + 1), 4, '0', STR_PAD_LEFT);
        }

        while (Client::withoutGlobalScopes()->where('username', $nextUsername)->exists()) {
            if (preg_match('/^(\D*)(\d+)$/', $nextUsername, $m)) {
                $nextUsername = $m[1] . ((int)$m[2] + 1);
            } else {
                $nextUsername .= '2';
            }
        }

        return response()->json([
            'next_client_code' => $nextClientCode,
            'next_username'    => $nextUsername,
        ]);
    }

    public function create()
    {
        $company = \App\Services\TenantContext::get();
        if ($company && !$company->canAddMoreClients()) {
            return redirect()->route('admin.clients.index')
                ->with('error', 'Client limit reached! Your plan allows maximum ' . $company->max_clients . ' clients. Please upgrade your plan or contact support.');
        }

        $zones = Zone::with(['areas' => function($q){ $q->whereNull('reseller_id'); }])->whereNull('reseller_id')->where('is_active', true)->get();
        $packages = Package::where('is_active', true)->get();
        $boxes = Box::with('zone')->where('status', 'active')->orderBy('name')->get();
        $mikrotikDevices = MikrotikDevice::where('is_active', true)->orderBy('name')->get();
        $divisions = \App\Models\District::where('is_active', true)
            ->whereNotNull('division')->where('division', '!=', '')
            ->distinct()->orderBy('division')->pluck('division');
        $districts = \App\Models\District::where('is_active', true)->orderBy('name')->get();
        $upazilas = \App\Models\Upazila::where('is_active', true)->orderBy('name')->get();
        $_profileCompanyId = auth()->user()?->company_id;
        $availableProfiles = \App\Models\MikrotikProfile::withoutGlobalScopes()
            ->where('is_active', true)
            ->when($_profileCompanyId && !auth()->user()?->isSuperAdmin(), fn($q) => $q->where('company_id', $_profileCompanyId))
            ->orderBy('name')
            ->pluck('name');
        $agents = \App\Models\Agent::where('status', 'active')->orderBy('name')->get();
        $technicians = \App\Models\Employee::whereHas('user.roles', function($q){ $q->where('name', 'Field Technician'); })
            ->where('status', 'active')->orderBy('name')->get();
        $oltDevices = \App\Models\OltDevice::where('is_active', true)->orderBy('name')->get();
        return view('admin.clients.create', compact('zones', 'packages', 'boxes', 'mikrotikDevices', 'oltDevices', 'divisions', 'districts', 'upazilas', 'availableProfiles', 'agents', 'technicians'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Super Admin') && !\App\Services\MenuPermissionService::canAction($user, 'client_list', 'add_new')) {
            abort(403, 'You do not have permission to add new clients.');
        }

        $company = \App\Services\TenantContext::get();
        if ($company && !$company->canAddMoreClients()) {
            return redirect()->route('admin.clients.index')
                ->with('error', 'Client limit reached! Your plan allows maximum ' . $company->max_clients . ' clients. Please upgrade your plan or contact support.');
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'username' => ['required', 'string', \Illuminate\Validation\Rule::unique('clients', 'username')],
            'client_id' => ['nullable', 'string', 'max:50', \Illuminate\Validation\Rule::unique('clients', 'client_id')->where('company_id', auth()->user()->company_id)],
            'zone_id' => ['nullable', \Illuminate\Validation\Rule::exists('zones', 'id')],
            'area_id' => ['nullable', \Illuminate\Validation\Rule::exists('areas', 'id')],
            'box_id' => ['nullable', \Illuminate\Validation\Rule::exists('boxes', 'id')],
            'package_id' => 'required|exists:packages,id',
            'profile_picture' => 'nullable|image|max:5120',
            'nid_front_image' => 'nullable|image|max:5120',
            'nid_back_image' => 'nullable|image|max:5120',
            'agent_id' => 'nullable|exists:agents,id',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $request->merge(['name' => trim($request->first_name . ' ' . $request->last_name)]);

        DB::beginTransaction();
        try {
            $user = null;
            if ($request->email) {
                $existingUser = User::where('email', $request->email)->first();
                if ($existingUser) {
                    $user = $existingUser;
                } else {
                    $user = User::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'password' => Hash::make($request->password ?? \Illuminate\Support\Str::random(12)),
                        'phone' => $request->phone,
                        'user_type' => 'client',
                    ]);
                }
            }

            $package = Package::find($request->package_id);
            
            $nidFrontImage = null;
            $nidBackImage = null;
            $profilePicture = null;
            if ($request->hasFile('profile_picture')) {
                $profilePicture = $request->file('profile_picture')->store('clients/profile', 'public');
            }
            if ($request->hasFile('nid_front_image')) {
                $nidFrontImage = $request->file('nid_front_image')->store('nid_images', 'public');
            }
            if ($request->hasFile('nid_back_image')) {
                $nidBackImage = $request->file('nid_back_image')->store('nid_images', 'public');
            }
            
            $clientData = [
                'user_id' => $user?->id,
                'name' => $request->name,
                'profile_picture' => $profilePicture,
                'email' => $request->email,
                'phone' => $request->phone,
                'alt_phone' => $request->alt_phone,
                'address' => $request->address,
                'nid' => $request->nid,
                'occupation' => $request->occupation,
                'father_husband_name' => $request->father_husband_name,
                'mother_name' => $request->mother_name,
                'date_of_birth' => $request->date_of_birth ? $this->parseFlexibleDate($request->date_of_birth) : null,
                'gender' => $request->gender,
                'nid_front_image' => $nidFrontImage,
                'nid_back_image' => $nidBackImage,
                'zone_id' => $request->zone_id,
                'area_id' => $request->area_id,
                'box_id' => $request->box_id,
                'package_id' => $request->package_id,
                'username' => $request->username,
                'pppoe_password' => $request->pppoe_password,
                'mac_address' => $request->mac_address,
                'ip_address' => $request->ip_address,
                'mikrotik_device_id' => $request->mikrotik_device_id,
                'mac_binding_enabled' => $request->has('mac_binding_enabled'),
                'connection_type' => $request->connection_type ?? 'pppoe',
                'connection_medium' => $request->connection_medium ?? 'utp',
                'connection_date' => $request->connection_date ? $this->parseFlexibleDate($request->connection_date) : now()->format('Y-m-d'),
                'expire_date' => $request->expire_date
                    ? $this->parseFlexibleDate($request->expire_date)
                    : (($package->validity_days && $package->validity_days != 30)
                        ? \Carbon\Carbon::parse($request->connection_date ? $this->parseFlexibleDate($request->connection_date) : now()->format('Y-m-d'))->addDays($package->validity_days)->format('Y-m-d')
                        : \Carbon\Carbon::parse($request->connection_date ? $this->parseFlexibleDate($request->connection_date) : now()->format('Y-m-d'))->addMonthNoOverflow()->format('Y-m-d')),
                'billing_date' => $request->billing_date ?? 1,
                'billing_starts_at' => ($request->billing_starts_at && strlen($request->billing_starts_at) >= 7)
                    ? \Carbon\Carbon::parse($request->billing_starts_at . '-01')->startOfMonth()->format('Y-m-d')
                    : null,
                'payment_deadline' => $request->payment_deadline ?? 10,
                'auto_disconnect_days' => $request->auto_disconnect_days ?? 15,
                'monthly_fee' => $package->price,
                'permanent_discount' => $request->permanent_discount ?? 0,
                'signup_fee' => 0,
                'signup_fee_paid' => false,
                'queue_max_limit' => $request->queue_max_limit,
                'queue_burst_limit' => $request->queue_burst_limit,
                'youtube_bandwidth' => $request->youtube_bandwidth,
                'facebook_bandwidth' => $request->facebook_bandwidth,
                'google_map_location' => $request->google_map_location,
                'google_map_lat' => $request->google_map_lat,
                'google_map_lng' => $request->google_map_lng,
                'district_id' => $request->district_id,
                'upazila_id' => $request->upazila_id,
                'bill_calculation_type' => $request->bill_calculation_type ?? 'auto',
                'status' => $request->status ?? 'pending',
                'remarks' => $request->remarks,
                'fiber_code' => $request->fiber_code,
                'fiber_color' => $request->fiber_color,
                'splitter_number' => $request->splitter_number,
                'splitter_color' => $request->splitter_color,
                'onu_mac' => $request->onu_mac,
                'onu_brand' => $request->onu_brand,
                'fiber_vendor' => $request->fiber_vendor,
                'fiber_core_type' => $request->fiber_core_type,
                'mikrotik_profile_name' => $request->mikrotik_profile_name,
                'agent_id' => $request->agent_id,
                'commission_percent' => $request->commission_percent,
                'client_type' => $request->client_type ?? 'regular',
                'nearby_landmark' => $request->nearby_landmark,
                'billing_cycle' => $request->billing_cycle ?? 'monthly',
                'installation_charge' => (float) ($request->installation_charge ?? 0),
                'cable_length_meters' => (float) ($request->cable_length_meters ?? 0),
                'onu_required' => $request->boolean('onu_required'),
                'router_required' => $request->boolean('router_required'),
                'fiber_required' => $request->boolean('fiber_required'),
                'assigned_to' => $request->assigned_technicians ? $request->assigned_technicians[0] : null,
                'assigned_technicians' => $request->assigned_technicians ?: null,
                'technician_note' => $request->technician_note,
            ];

            if ($request->filled('client_id')) {
                $clientData['client_id'] = $request->client_id;
            }

            $client = Client::create($clientData);

            ActivityLog::log('create', 'clients', "Created client: {$client->name}");

            $installAmount = (float)$client->installation_charge ?: (float)$client->signup_fee ?: 0;
            $existingBaseCharge = \App\Models\ClientServiceCharge::where('client_id', $client->id)
                ->where('charge_type', 'Installation Charge')
                ->whereNull('paid_by')
                ->first();
            if (!$existingBaseCharge) {
                \App\Models\ClientServiceCharge::create([
                    'client_id' => $client->id,
                    'charge_type' => 'Installation Charge',
                    'amount' => $installAmount,
                    'description' => $installAmount > 0 ? 'Installation charge' : 'Free installation',
                    'payment_method' => null,
                    'paid_by' => null,
                    'charge_date' => now()->toDateString(),
                ]);
            }

            if ($client->status === 'active') {
                ClientActivityService::logConnection($client, 'New client connection activated');
            }
            
            // Zero-Touch Provisioning (ZTP): async background job for MikroTik + ONU
            if ($client->status === 'active' && \App\Models\SystemSetting::get('ztp_enabled', '1') === '1') {
                try {
                    $client->update(['provision_status' => 'pending']);
                    \App\Jobs\ZeroTouchProvisioningJob::dispatch($client->id, 'new_client')
                        ->onQueue('default')
                        ->delay(now()->addSeconds(5));
                    Log::info("[ZTP] Dispatched for client #{$client->id} ({$client->name})");
                } catch (\Exception $ztpError) {
                    Log::warning("[ZTP] Dispatch failed for {$client->name}: " . $ztpError->getMessage());
                }
            }

            if ($client->status === 'active' && $client->phone) {
                try {
                    $smsService = app(\App\Services\SmsService::class);
                    $smsService->sendNewClientCredentials($client, $request->pppoe_password);
                } catch (\Exception $smsError) {
                    Log::warning("New client SMS failed for {$client->name}: " . $smsError->getMessage());
                }
            }
            
            // PREPAID SYSTEM: Always generate first bill for new clients
            $billGenerated = false;
            try {
                $billingService = app(BillingService::class);
                $billingService->generateBill($client, now()->month, now()->year, suppressSms: true);
                Log::info("Auto-generated first bill for new client: {$client->name}");
                $billGenerated = true;
            } catch (\Exception $billError) {
                Log::warning("Failed to auto-generate bill for new client {$client->name}: " . $billError->getMessage());
            }
            
            DB::commit();

            try {
                (new \App\Services\TelegramService())->sendClientCreationNotification(
                    $client->client_id ?? (string) $client->id,
                    $client->name ?? '',
                    $package->name ?? 'N/A',
                    $client->zone?->name ?? 'N/A',
                    auth()->user()->name ?? 'System',
                    $client->phone,
                    $client->username ?? '',
                    $client->address ?? '',
                    $client->connection_type ?? '',
                    (float) ($package->price ?? 0),
                    $client->mikrotikDevice?->identity ?? $client->mikrotikDevice?->host ?? '',
                    (int) ($client->payment_deadline ?? 0),
                    (float)$client->installation_charge ?: (float)$client->signup_fee ?: 0
                );
            } catch (\Throwable $e) {
                \Log::warning('Telegram client creation notification failed: ' . $e->getMessage());
            }
            
            $message = $billGenerated 
                ? 'Client created successfully with first month bill.' 
                : 'Client created successfully (bill generation failed - please generate manually).';
            return redirect()->route('admin.clients.index')->with($billGenerated ? 'success' : 'warning', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating client: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Client $client)
    {
        $client->load(['district', 'upazila', 'zone', 'area', 'package', 'agent', 'bills.payments', 'payments.paymentMethod', 'payments.collectedBy', 'tickets', 'activityLogs.performedByUser', 'serviceCharges.paidByUser']);

        $mac = $client->mac_address ?: ($client->session_mac ?: null);

        if ($client->username && $client->mikrotik_device_id) {
            try {
                $mikrotikService = new \App\Services\SimpleMikroTikService();
                $device = $client->mikrotikDevice;
                if ($device && $mikrotikService->connectFromDevice($device)) {
                    $session = $mikrotikService->getActiveSession($client->username);
                    if ($session) {
                        $updateData = [
                            'connection_status' => 'online',
                            'last_online' => now(),
                        ];
                        if (!empty($session['address'])) $updateData['session_ip'] = $session['address'];
                        if (!empty($session['caller-id'])) {
                            $updateData['session_mac'] = $session['caller-id'];
                            if (!$mac) $mac = $session['caller-id'];
                        }
                        if (!empty($session['uptime'])) $updateData['session_uptime'] = $session['uptime'];
                        $client->update($updateData);
                    } else {
                        if ($client->connection_status === 'online') {
                            $client->update([
                                'connection_status' => 'offline',
                                'session_ip' => null,
                                'session_mac' => null,
                                'session_uptime' => null,
                                'last_logout' => now(),
                            ]);
                        }
                    }
                    if (!$mac) {
                        $secret = $mikrotikService->findPPPoESecret($client->username);
                        if ($secret && !empty($secret['caller-id']) && $secret['caller-id'] !== '') {
                            $mac = $secret['caller-id'];
                        }
                    }
                    $mikrotikService->disconnect();
                }
            } catch (\Exception $e) {
                Log::debug('MikroTik sync failed for client ' . $client->id . ': ' . $e->getMessage());
            }
        }

        if ($mac && $mac !== $client->mac_address) {
            $client->update(['mac_address' => $mac]);
            $client->refresh();
        } else {
            $client->refresh();
        }

        $macForLookup = $client->mac_address ?: $mac;
        if (!$client->router_brand && $macForLookup) {
            $vendor = \App\Services\MacVendorService::lookup($macForLookup);
            if ($vendor) {
                $client->update(['router_brand' => $vendor]);
                $client->refresh();
            }
        }

        $clientLabel = $client->name . ' (' . $client->username . ')';
        $usernamePattern = '%(' . $client->username . ')%';
        $assignedProducts = \App\Models\Instrument::withoutGlobalScope('tenant')
            ->where('status', 'in_use')
            ->where(function($q) use ($client, $clientLabel, $usernamePattern) {
                $q->where('client_id', $client->id)
                  ->orWhere('assigned_to', (string) $client->id)
                  ->orWhere('assigned_to', $clientLabel)
                  ->orWhere('assigned_to', 'LIKE', $usernamePattern);
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        $outLogs = DB::table('instrument_out_logs')
            ->leftJoin('users as creator', 'instrument_out_logs.created_by', '=', 'creator.id')
            ->leftJoin('users as tech', 'instrument_out_logs.technician_id', '=', 'tech.id')
            ->where(function($q) use ($client, $clientLabel, $usernamePattern) {
                $q->where('instrument_out_logs.client_id', $client->id)
                  ->orWhere('instrument_out_logs.issued_to', (string) $client->id)
                  ->orWhere('instrument_out_logs.issued_to', $clientLabel)
                  ->orWhere('instrument_out_logs.issued_to', 'LIKE', $usernamePattern);
            })
            ->select('instrument_out_logs.*', 'creator.name as assigned_by_name', 'tech.name as technician_name')
            ->orderBy('out_date', 'desc')
            ->orderBy('instrument_out_logs.created_at', 'desc')
            ->get();

        $assignedByMap = [];
        $installedByMap = [];
        $chargeMap = [];
        foreach ($outLogs as $log) {
            if ($log->instrument_ids) {
                $ids = json_decode($log->instrument_ids, true) ?? [];
                foreach ($ids as $id) {
                    if ($log->assigned_by_name) {
                        $assignedByMap[$id] = $log->assigned_by_name;
                    }
                    if ($log->technician_name) {
                        $installedByMap[$id] = $log->technician_name;
                    }
                    $logChargeType   = $log->charge_type ?? 'free';
                    $logChargeAmount = (float) ($log->charge_amount ?? 0);
                    if ($logChargeType !== 'free' && $logChargeAmount > 0) {
                        if (!isset($chargeMap[$id])) {
                            $chargeMap[$id] = [
                                'type'   => $logChargeType,
                                'amount' => $logChargeAmount,
                                'status' => $log->payment_status ?? 'na',
                            ];
                        }
                    }
                }
            }
        }

        // Load connection change timeline
        $connectionTimeline = DB::table('client_timeline')
            ->where('client_id', $client->id)
            ->whereIn('event_type', ['connection_change', 'connection_upgrade'])
            ->orderBy('created_at', 'desc')
            ->get();

        $fiberUsages = \App\Models\FiberUsage::with(['fiberStock', 'technician'])
            ->where('client_id', $client->id)
            ->orderBy('used_date', 'desc')
            ->get();

        $fiberOutLogs = DB::table('fiber_histories')
            ->leftJoin('fiber_stocks', 'fiber_histories.fiber_stock_id', '=', 'fiber_stocks.id')
            ->where('fiber_histories.type', 'fiber_out')
            ->where(function($q) use ($client) {
                $q->where('fiber_histories.details', 'LIKE', '%PPPoE: ' . $client->username . '%')
                  ->orWhere('fiber_histories.details', 'LIKE', '%client: ' . $client->name . '%');
            })
            ->select('fiber_histories.*', 'fiber_stocks.fiber_code', 'fiber_stocks.fiber_type', 'fiber_stocks.core')
            ->orderBy('fiber_histories.date', 'desc')
            ->get();

        foreach ($fiberOutLogs as $fLog) {
            $fLog->technician_name = null;
            $fLog->assigned_by_name = null;
            if ($fLog->details) {
                if (preg_match('/Technician:\s*([^|]+)/', $fLog->details, $m)) {
                    $fLog->technician_name = trim($m[1]);
                }
                if (preg_match('/Assigned By:\s*([^|]+)/', $fLog->details, $m)) {
                    $fLog->assigned_by_name = trim($m[1]);
                }
            }
        }

        $addPaymentTypes = \App\Models\ClientAdditionalPayment::getTypes();
        $addPaymentMethods = auth()->user()->allowedPaymentMethods();

        $onuRecord = null;
        $oltDevice = null;
        if ($client->olt_device_id) {
            $oltDevice = \App\Models\OltDevice::find($client->olt_device_id);
        }
        // Priority: onu_id > onu_serial/mac > client_id
        if ($client->onu_id) {
            $onuRecord = DB::table("onus")->where("id", $client->onu_id)->first();
        }
        if (!$onuRecord && ($client->onu_serial || $client->onu_mac)) {
            $onuRecord = DB::table("onus")
                ->where(function ($q) use ($client) {
                    if ($client->onu_serial) $q->orWhere("serial_number", $client->onu_serial);
                    if ($client->onu_mac) $q->orWhere("mac_address", $client->onu_mac);
                })
                ->first();
        }
        if (!$onuRecord && $client->id) {
            $onuRecord = DB::table("onus")->where("client_id", $client->id)->latest("updated_at")->first();
        }
        if ($onuRecord && !$onuRecord->client_id && $client->id) {
            DB::table("onus")->where("id", $onuRecord->id)->update(["client_id" => $client->id, "updated_at" => now()]);
        }
        if (!$oltDevice && ($onuRecord->olt_device_id ?? null)) {
            $oltDevice = \App\Models\OltDevice::find($onuRecord->olt_device_id);
        }

        $clientSales = \App\Models\Sale::with(['items', 'creator'])
            ->where('client_id', $client->id)
            ->orderByDesc('sale_date')
            ->get();

        $paymentReasons = \App\Models\DropdownOption::getOptions('payment_reasons');
        $deadlineReasons = \App\Models\DropdownOption::getOptions('deadline_extend_reasons');
        $deleteReasons = \App\Models\DropdownOption::getOptions('client_delete_reasons');

        return view('admin.clients.show', compact('client', 'assignedProducts', 'outLogs', 'fiberUsages', 'fiberOutLogs', 'assignedByMap', 'installedByMap', 'chargeMap', 'connectionTimeline', 'addPaymentTypes', 'addPaymentMethods', 'onuRecord', 'oltDevice', 'clientSales', 'paymentReasons', 'deadlineReasons', 'deleteReasons'));
    }

    public function changeConnectionType(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        $request->validate([
            'new_connection_medium' => 'required|in:utp,fiber',
            'change_charge'         => 'nullable|numeric|min:0',
            'payment_status'        => 'nullable|in:paid,due,waived,free',
            'notes'                 => 'nullable|string|max:1000',
        ]);

        $oldMedium = $client->connection_medium ?? 'utp';
        $newMedium = $request->new_connection_medium;

        if ($oldMedium === $newMedium) {
            return redirect()->back()->with('warning', 'Connection type is already ' . strtoupper($newMedium) . '.');
        }

        $chargeAmount = (float) $request->input('change_charge', 0);
        $paymentStatus = $request->input('payment_status', 'free');
        $notes = $request->input('notes', '');

        // Update client connection medium
        $client->update([
            'connection_medium' => $newMedium,
            'fiber_required'    => ($newMedium === 'fiber'),
        ]);

        // Record in client_timeline
        $mediumLabels = ['utp' => 'UTP (Cable)', 'fiber' => 'Fiber'];
        $oldLabel = $mediumLabels[$oldMedium] ?? strtoupper($oldMedium);
        $newLabel = $mediumLabels[$newMedium] ?? strtoupper($newMedium);

        DB::table('client_timeline')->insert([
            'client_id'  => $client->id,
            'event_type' => 'connection_change',
            'title'      => $oldLabel . ' → ' . $newLabel,
            'description' => ($notes ?: 'Connection type changed from ' . $oldLabel . ' to ' . $newLabel . '.'),
            'metadata'   => json_encode([
                'from'           => $oldMedium,
                'to'             => $newMedium,
                'from_label'     => $oldLabel,
                'to_label'       => $newLabel,
                'charge'         => $chargeAmount,
                'payment_status' => $paymentStatus,
                'changed_by'     => auth()->user()->name ?? 'System',
            ]),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create service charge if applicable
        if ($chargeAmount > 0 && in_array($paymentStatus, ['paid', 'due'])) {
            if ($paymentStatus === 'paid') {
                \App\Models\ClientServiceCharge::create([
                    'client_id'   => $client->id,
                    'charge_type' => 'Connection Upgrade (' . $oldLabel . ' → ' . $newLabel . ')',
                    'amount'      => $chargeAmount,
                    'description' => $notes ?: 'Connection type upgrade charge',
                    'charge_date' => now()->toDateString(),
                    'paid_by'     => auth()->id(),
                ]);
            } else {
                DB::table('client_service_charges')->insert([
                    'client_id'   => $client->id,
                    'charge_type' => 'Connection Upgrade — Due (' . $oldLabel . ' → ' . $newLabel . ')',
                    'amount'      => $chargeAmount,
                    'description' => $notes ?: 'Connection type upgrade charge (pending collection)',
                    'charge_date' => now()->toDateString(),
                    'paid_by'     => null,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        \App\Models\ActivityLog::log('update', 'clients',
            'Connection type changed: ' . $oldLabel . ' → ' . $newLabel .
            ($chargeAmount > 0 ? ' | Charge: ৳' . number_format($chargeAmount, 2) . ' (' . $paymentStatus . ')' : '') .
            ' | Client: ' . $client->name . ' (' . $client->client_id . ')');

        return redirect()->back()->with('success',
            'Connection type changed from ' . $oldLabel . ' to ' . $newLabel . '.' .
            ($chargeAmount > 0 ? ' Charge of ৳' . number_format($chargeAmount, 2) . ' recorded.' : ''));
    }

    public function onuDiagnostics(Client $client)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Super Admin') && !\App\Services\MenuPermissionService::canAction($user, 'client_list', 'view')) {
            abort(403, 'You do not have permission to view ONU diagnostics.');
        }

        $data = [
            'olt_name' => null,
            'onu_mac' => $client->onu_mac,
            'port' => $client->olt_port,
            'vlan' => null,
            'temperature' => null,
            'distance' => null,
            'tx_power' => null,
            'tx_power_mw' => null,
            'rx_power' => null,
            'rx_power_mw' => null,
            'voltage' => null,
            'tx_bias' => null,
            'status' => null,
            'uptime' => null,
            'source' => 'database',
        ];

        $oltDevice = null;
        if ($client->olt_device_id) {
            $oltDevice = \App\Models\OltDevice::find($client->olt_device_id);
            if ($oltDevice) {
                $data['olt_name'] = $oltDevice->name;
            }
        }

        // ONU lookup: onu_id (direct FK) > onu_serial > onu_mac > client_id+olt_device_id > client_id
        $onuRecord = null;
        if ($client->onu_id) {
            $onuRecord = DB::table('onus')->where('id', $client->onu_id)->first();
        }
        if (!$onuRecord && $client->onu_serial) {
            $onuRecord = DB::table('onus')->where('serial_number', $client->onu_serial)->first();
        }
        if (!$onuRecord && $client->onu_mac && strlen($client->onu_mac) >= 17) {
            $onuRecord = DB::table('onus')->where('mac_address', $client->onu_mac)->first();
        }
        if (!$onuRecord && $client->olt_device_id) {
            $onuRecord = DB::table('onus')->where('client_id', $client->id)->where('olt_device_id', $client->olt_device_id)->first();
        }
        if (!$onuRecord) {
            $onuRecord = DB::table('onus')->where('client_id', $client->id)->latest('updated_at')->first();
        }

        if ($onuRecord) {
            $data['port'] = $onuRecord->pon_port ?? $data['port'];
            $data['vlan'] = $onuRecord->vlan;
            $data['temperature'] = $onuRecord->temperature;
            $data['rx_power'] = $onuRecord->rx_power;
            $data['tx_power'] = $onuRecord->tx_power;
            $data['status'] = $onuRecord->status;
            $data['onu_mac'] = $onuRecord->mac_address ?? $data['onu_mac'];

            if (!$oltDevice && $onuRecord->olt_device_id) {
                $oltDevice = \App\Models\OltDevice::find($onuRecord->olt_device_id);
                if ($oltDevice) $data['olt_name'] = $oltDevice->name;
            }
        }

        if ($data['rx_power'] !== null) {
            $data['rx_power_mw'] = round(pow(10, $data['rx_power'] / 10), 4);
        }
        if ($data['tx_power'] !== null) {
            $data['tx_power_mw'] = round(pow(10, $data['tx_power'] / 10), 4);
        }

        if ($oltDevice && $oltDevice->is_active && $onuRecord) {
            try {
                $isBdcomTelnet = strtolower($oltDevice->vendor ?? '') === 'bdcom'
                    && strtolower($oltDevice->connection_type ?? 'telnet') === 'telnet';

                if ($isBdcomTelnet) {
                    $poller = new \App\Services\OLT\BdcomTelnetPoller($oltDevice);
                    $info   = $poller->getOnuLiveStatus((int) $onuRecord->id);
                    if ($info && isset($info['status'])) {
                        $data['source']      = 'live';
                        $data['status']      = $info['status'];
                        $data['down_reason'] = $info['down_reason'] ?? null;
                        if (($info['rx_power'] ?? null) !== null) {
                            $data['rx_power']    = $info['rx_power'];
                            $data['rx_power_mw'] = round(pow(10, $info['rx_power'] / 10), 4);
                        }
                        if (($info['tx_power'] ?? null) !== null) {
                            $data['tx_power']    = $info['tx_power'];
                            $data['tx_power_mw'] = round(pow(10, $info['tx_power'] / 10), 4);
                        }
                        if (($info['temperature'] ?? null) !== null) $data['temperature'] = $info['temperature'];
                        if (($info['voltage']     ?? null) !== null) $data['voltage']     = $info['voltage'];
                        if (($info['tx_bias']     ?? null) !== null) $data['tx_bias']     = $info['tx_bias'];
                        if (($info['distance']    ?? null) !== null) $data['distance']    = $info['distance'];
                        $data['port'] = $info['port'] ?? $data['port'];
                        DB::table('onus')->where('id', $onuRecord->id)->update([
                            'status'      => $info['status'],
                            'down_reason' => $info['down_reason'] ?? null,
                            'rx_power'    => $info['rx_power'] ?? null,
                            'tx_power'    => $info['tx_power'] ?? null,
                            'temperature' => $info['temperature'] ?? null,
                            'updated_at'  => now(),
                        ]);
                        DB::table('clients')->where('id', $client->id)->update([
                            'onu_status'      => $info['status'],
                            'onu_rx_power'    => $info['rx_power'] ?? null,
                            'onu_down_reason' => $info['down_reason'] ?? null,
                            'onu_last_check'  => now(),
                        ]);
                    }
                } else {
                    $serial = $client->onu_serial ?: $onuRecord->serial_number;
                    if ($serial) {
                        $api = \App\Services\OltService::fromDevice($oltDevice);
                        if ($api->connect()) {
                            $info = $api->getOnuInfo($serial);
                            $api->disconnect();
                            if ($info && !empty($info)) {
                                $data['source'] = 'live';
                                if (isset($info['status']))      $data['status']      = $info['status'];
                                if (isset($info['signal']))      { $data['rx_power'] = $info['signal']; $data['rx_power_mw'] = round(pow(10, $info['signal'] / 10), 4); }
                                if (isset($info['tx_power']))    { $data['tx_power'] = $info['tx_power']; $data['tx_power_mw'] = round(pow(10, $info['tx_power'] / 10), 4); }
                                if (isset($info['temperature'])) $data['temperature'] = $info['temperature'];
                                if (isset($info['port']))        $data['port']        = $info['port'];
                                if (isset($info['uptime']))      $data['uptime']      = $info['uptime'];
                                if (isset($info['voltage']))     $data['voltage']     = $info['voltage'];
                                if (isset($info['tx_bias']))     $data['tx_bias']     = $info['tx_bias'];
                                if (isset($info['distance']))    $data['distance']    = $info['distance'];
                                if (isset($info['vlan']))        $data['vlan']        = $info['vlan'];
                                if (isset($info['mac']))         $data['onu_mac']     = $info['mac'];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('ONU diagnostics failed for client ' . $client->id . ': ' . $e->getMessage());
                $data['error'] = 'OLT connection failed';
            }
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getClientInfo(Client $client)
    {
        $client->load(['zone', 'area', 'package']);
        return response()->json([
            'id' => $client->id,
            'client_id' => $client->client_id,
            'name' => $client->name,
            'phone' => $client->phone,
            'alt_phone' => $client->alt_phone,
            'address' => $client->address,
            'email' => $client->email,
            'zone' => $client->zone->name ?? 'N/A',
            'area' => $client->area->name ?? 'N/A',
            'package' => $client->package->name ?? 'N/A',
            'package_price' => $client->package->price ?? 0,
            'monthly_fee' => $client->monthly_fee,
            'due_amount' => $client->due_amount,
            'status' => $client->status,
            'username' => $client->username,
            'connection_type' => $client->connection_type,
            'ip_address' => $client->ip_address,
        ]);
    }

    public function edit(Client $client)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Super Admin') && !\App\Services\MenuPermissionService::canAction($user, 'client_list', 'edit')) {
            abort(403, 'You do not have permission to edit clients.');
        }

        $client->load('zone');
        $zones = Zone::with(['areas' => function($q){ $q->whereNull('reseller_id'); }])->whereNull('reseller_id')->where('is_active', true)->get();
        $areas = \App\Models\Area::whereNull('reseller_id')->orderBy('name')->get();
        $packages = Package::where('is_active', true)->with('boundProfile')->get();
        $boxes = Box::with('zone')->where('status', 'active')->orderBy('name')->get();
        $mikrotikDevices = \App\Models\MikrotikDevice::where('is_active', true)
            ->orderBy('name')
            ->get();
        $oltDevices = \App\Models\OltDevice::where('is_active', true)
            ->orderBy('name')
            ->get();
        $divisions = \App\Models\District::where('is_active', true)
            ->whereNotNull('division')->where('division', '!=', '')
            ->distinct()->orderBy('division')->pluck('division');
        $districts = \App\Models\District::where('is_active', true)->orderBy('name')->get();
        $upazilas = \App\Models\Upazila::where('is_active', true)->orderBy('name')->get();
        $availableProfiles = \App\Models\MikrotikProfile::withoutGlobalScopes()->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'source_device']);
        $agents = \App\Models\Agent::where('status', 'active')->orderBy('name')->get();
        $disableReasons = \App\Models\DropdownOption::getOptions('client_disable_reasons');
        $enableReasons = \App\Models\DropdownOption::getOptions('client_enable_reasons');
        return view('admin.clients.edit', compact('client', 'zones', 'areas', 'packages', 'boxes', 'mikrotikDevices', 'oltDevices', 'divisions', 'districts', 'upazilas', 'availableProfiles', 'agents', 'disableReasons', 'enableReasons'));
    }

    public function update(Request $request, Client $client)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Super Admin') && !\App\Services\MenuPermissionService::canAction($user, 'client_list', 'edit')) {
            abort(403, 'You do not have permission to edit clients.');
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'username' => ['required', 'string', \Illuminate\Validation\Rule::unique('clients', 'username')->ignore($client->id)],
            'client_id' => ['nullable', 'string', 'max:50', \Illuminate\Validation\Rule::unique('clients', 'client_id')->where('company_id', auth()->user()->company_id)->ignore($client->id)],
            'zone_id' => ['nullable', \Illuminate\Validation\Rule::exists('zones', 'id')],
            'area_id' => ['nullable', \Illuminate\Validation\Rule::exists('areas', 'id')],
            'box_id' => ['nullable', \Illuminate\Validation\Rule::exists('boxes', 'id')],
            'profile_picture' => 'nullable|image|max:5120',
            'nid_front_image' => 'nullable|image|max:5120',
            'nid_back_image' => 'nullable|image|max:5120',
        ]);

        $request->merge(['name' => trim($request->first_name . ' ' . $request->last_name)]);

        $oldStatus = $client->status;
        $oldPackageId = $client->package_id;
        $oldUsername = $client->username;
        $oldPassword = $client->pppoe_password;
        $oldIsFree = (bool) $client->is_free;
        
        $updateData = $request->only([
            'name', 'email', 'phone', 'alt_phone', 'address', 'nid', 'occupation',
            'district_id', 'upazila_id', 'zone_id', 'area_id', 'box_id', 'package_id', 'username', 'pppoe_password',
            'mac_address', 'ip_address', 'billing_date', 'billing_starts_at', 'monthly_fee', 'permanent_discount',
            'connection_type', 'connection_medium', 'payment_deadline', 'auto_disconnect_days', 'bill_calculation_type',
            'queue_max_limit', 'queue_burst_limit', 'youtube_bandwidth', 'facebook_bandwidth',
            'google_map_location', 'google_map_lat', 'google_map_lng', 'status', 'remarks',
            'mikrotik_device_id', 'olt_device_id', 'onu_serial', 'olt_port', 'onu_model',
            'client_id', 'expire_date', 'connection_date',
            'installation_charge', 'cable_length_meters', 'nearby_landmark',
            'father_husband_name', 'mother_name', 'date_of_birth', 'gender', 'client_type', 'reseller_id', 'agent_id', 'commission_percent', 'signup_fee',
            'free_reason', 'custom_status',
            'fiber_code', 'fiber_color', 'splitter_number', 'splitter_color', 'onu_mac',
            'fiber_vendor', 'fiber_core_type', 'onu_brand',
            'mikrotik_profile_name',
        ]);
        $nullableTextFields = ['district_id', 'upazila_id', 'zone_id', 'area_id', 'box_id', 'fiber_code', 'fiber_color', 'splitter_number', 'splitter_color', 'onu_mac', 'onu_brand', 'fiber_vendor', 'fiber_core_type', 'free_reason', 'custom_status', 'remarks', 'father_husband_name', 'mother_name', 'gender', 'agent_id', 'commission_percent', 'onu_serial', 'olt_port', 'onu_model', 'olt_device_id'];
        foreach ($nullableTextFields as $field) {
            if (array_key_exists($field, $updateData) && ($updateData[$field] === '' || $updateData[$field] === null)) {
                $updateData[$field] = null;
            }
        }
        $updateData = array_filter($updateData, fn($value) => $value !== null && $value !== '');
        foreach ($nullableTextFields as $field) {
            if ($request->has($field) && ($request->$field === '' || $request->$field === null)) {
                $updateData[$field] = null;
            }
        }
        // is_free must be set explicitly — not filtered by array_filter (which strips false)
        $updateData['is_free'] = $request->boolean('is_free');
        $updateData['free_reason'] = $request->is_free ? $request->free_reason : null;
        $updateData['mac_binding_enabled'] = $request->has('mac_binding_enabled');
        $updateData['onu_required'] = $request->has('onu_required');
        $updateData['router_required'] = $request->has('router_required');
        $updateData['fiber_required'] = $request->has('fiber_required');

        if (!empty($updateData['connection_date'])) {
            $updateData['connection_date'] = $this->parseFlexibleDate($updateData['connection_date']);
        }
        if (!empty($updateData['expire_date'])) {
            $updateData['expire_date'] = $this->parseFlexibleDate($updateData['expire_date']);
        } else {
            $updateData['expire_date'] = null;
        }
        // billing_starts_at: store as YYYY-MM-01, or null if blank
        if ($request->has('billing_starts_at')) {
            $bsa = $request->billing_starts_at;
            $updateData['billing_starts_at'] = ($bsa && strlen($bsa) >= 7)
                ? \Carbon\Carbon::parse($bsa . '-01')->startOfMonth()->format('Y-m-d')
                : null;
        }

        if ($request->hasFile('profile_picture')) {
            $updateData['profile_picture'] = $request->file('profile_picture')->store('clients/profile', 'public');
        }
        if ($request->hasFile('nid_front_image')) {
            $updateData['nid_front_image'] = $request->file('nid_front_image')->store('nid_images', 'public');
        }
        if ($request->hasFile('nid_back_image')) {
            $updateData['nid_back_image'] = $request->file('nid_back_image')->store('nid_images', 'public');
        }
        
        $oldOnuSerial = $client->getOriginal('onu_serial');
        $oldOnuMac = $client->getOriginal('onu_mac');
        $oldOltDeviceId = $client->getOriginal('olt_device_id');
        $oldInstallationCharge = (float) $client->getOriginal('installation_charge');

        $client->update($updateData);

        // If free client toggle was turned OFF, recalculate due_amount from bills
        if ($oldIsFree && !((bool) $client->is_free)) {
            app(\App\Services\BillingService::class)->recalculateClientBalance($client);
        }

        // Sync ClientServiceCharge base record if installation_charge changed
        if (isset($updateData['installation_charge']) && (float)$updateData['installation_charge'] !== $oldInstallationCharge) {
            $newInstallCharge = (float) $updateData['installation_charge'];
            $baseRecord = \App\Models\ClientServiceCharge::where('client_id', $client->id)
                ->where('charge_type', 'Installation Charge')
                ->whereNull('paid_by')
                ->first();
            if ($baseRecord) {
                $baseRecord->update(['amount' => $newInstallCharge]);
            } elseif ($newInstallCharge > 0) {
                \App\Models\ClientServiceCharge::create([
                    'client_id'   => $client->id,
                    'charge_type' => 'Installation Charge',
                    'amount'      => $newInstallCharge,
                    'description' => 'Installation charge (updated via client edit)',
                    'charge_date' => $client->connection_date ?? now()->toDateString(),
                ]);
            }
            // Recalculate signup_fee_paid
            $totalPaid = (float) \App\Models\ClientServiceCharge::where('client_id', $client->id)
                ->where('charge_type', 'Installation Charge')
                ->whereNotNull('paid_by')
                ->where('amount', '>', 0)
                ->sum('amount');
            $client->update([
                'signup_fee'      => $newInstallCharge,
                'signup_fee_paid' => $newInstallCharge > 0 && $totalPaid >= $newInstallCharge,
            ]);
        }

        $newOnuSerial = $client->onu_serial;
        $newOnuMac = $client->onu_mac;
        $newOltDeviceId = $client->olt_device_id;

        if ((!$newOnuSerial && !$newOnuMac && !$newOltDeviceId) && ($oldOnuSerial || $oldOnuMac || $oldOltDeviceId)) {
            DB::table('onus')->where('client_id', $client->id)->update(['client_id' => null]);
        }

        if ($newOnuSerial && $newOnuSerial !== $oldOnuSerial) {
            DB::table('onus')->where('serial_number', $newOnuSerial)->update(['client_id' => $client->id]);
        }

        if (isset($updateData['mac_address']) && $updateData['mac_address'] !== $client->getOriginal('mac_address')) {
            $vendor = \App\Services\MacVendorService::lookup($updateData['mac_address']);
            if ($vendor) {
                $client->update(['router_brand' => $vendor]);
            }
        }

        $mikrotikMsg = '';
        $newPackageId = $client->package_id;
        if ($newPackageId && $newPackageId != $oldPackageId) {
            $newPackage = Package::find($newPackageId);
            if ($newPackage) {
                $oldPackageName = $oldPackageId ? (Package::find($oldPackageId)->name ?? 'N/A') : 'N/A';
                $client->update(['monthly_fee' => $newPackage->price]);

                app(\App\Services\BillingService::class)->updateCurrentBillForPackageChange($client, $newPackage, 'admin');

                \App\Models\ClientStatusLog::logStatusChange(
                    client: $client,
                    oldStatus: $oldPackageName,
                    newStatus: $newPackage->name,
                    reason: 'Package changed via client edit',
                    source: \App\Models\ClientStatusLog::SOURCE_MANUAL,
                    changedBy: auth()->id()
                );

                try {
                    $mikrotikService = new \App\Services\SimpleMikroTikService();
                    $pppoeUsername = $client->username;
                    $device = $client->mikrotikDevice;

                    if (!$device) {
                        $device = MikrotikDevice::where('is_active', true)->first();
                    }

                    if ($device) {
                        $connected = $mikrotikService->connect(
                            $device->getRawOriginal('host') ?? $device->host,
                            $device->username,
                            $device->password,
                            (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728)),
                            3
                        );

                        if ($connected) {
                            $response = $mikrotikService->sendCommand('/ppp/secret/print', ['?name' => $pppoeUsername]);
                            $secrets = $mikrotikService->parseResponse($response);

                            if (!empty($secrets)) {
                                $secretId = $secrets[0]['.id'] ?? null;
                                if ($secretId) {
                                    $profileToSet = $client->mikrotik_profile_name ?: ($newPackage->mikrotik_profile ?: ($newPackage->code ?: $newPackage->name));
                                    $setResponse = $mikrotikService->sendCommand('/ppp/secret/set', [
                                        '.id' => $secretId,
                                        'profile' => $profileToSet,
                                    ]);

                                    if (in_array('!done', $setResponse)) {
                                        $mikrotikMsg = " MikroTik profile updated to '{$profileToSet}'.";
                                        ActivityLog::log('mikrotik', 'clients', "Changed MikroTik profile for {$client->name} ({$pppoeUsername}) to {$profileToSet}");
                                    } else {
                                        $mikrotikMsg = " Warning: MikroTik profile change may have failed.";
                                    }
                                }
                            } else {
                                $mikrotikMsg = " Note: User '{$pppoeUsername}' not found on MikroTik router.";
                            }

                            $mikrotikService->disconnect();
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("MikroTik package change sync failed for client {$client->id}: " . $e->getMessage());
                }
            }
        }

        $newUsername = $client->username;
        $newPassword = $client->pppoe_password;
        if ($oldUsername !== $newUsername || ($newPassword && $oldPassword !== $newPassword)) {
            try {
                $mikrotikService = new \App\Services\SimpleMikroTikService();
                $device = $client->mikrotikDevice;
                if (!$device) {
                    $device = MikrotikDevice::where('is_active', true)->first();
                }
                if ($device) {
                    $connected = $mikrotikService->connect(
                        $device->getRawOriginal('host') ?? $device->host,
                        $device->username,
                        $device->password,
                        (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728)),
                        3
                    );
                    if ($connected) {
                        $response = $mikrotikService->sendCommand('/ppp/secret/print', ['?name' => $oldUsername]);
                        $secrets = $mikrotikService->parseResponse($response);
                        if (!empty($secrets)) {
                            $secretId = $secrets[0]['.id'] ?? null;
                            if ($secretId) {
                                $setParams = ['.id' => $secretId];
                                if ($oldUsername !== $newUsername) {
                                    $setParams['name'] = $newUsername;
                                }
                                if ($newPassword && $oldPassword !== $newPassword) {
                                    $setParams['password'] = $newPassword;
                                }
                                $setResponse = $mikrotikService->sendCommand('/ppp/secret/set', $setParams);
                                if (in_array('!done', $setResponse)) {
                                    $changes = [];
                                    if ($oldUsername !== $newUsername) $changes[] = "username '{$oldUsername}' → '{$newUsername}'";
                                    if ($newPassword && $oldPassword !== $newPassword) $changes[] = "password updated";
                                    $mikrotikMsg .= " MikroTik " . implode(', ', $changes) . ".";
                                    ActivityLog::log('mikrotik', 'clients', "Updated MikroTik credentials for {$client->name}: " . implode(', ', $changes));
                                    if ($oldUsername !== $newUsername) {
                                        $mikrotikService->sendCommand('/ppp/active/print', ['?name' => $oldUsername]);
                                        $activeSessions = $mikrotikService->parseResponse($mikrotikService->sendCommand('/ppp/active/print', ['?name' => $newUsername]));
                                        if (!empty($activeSessions)) {
                                            $activeId = $activeSessions[0]['.id'] ?? null;
                                            if ($activeId) {
                                                $mikrotikService->sendCommand('/ppp/active/remove', ['.id' => $activeId]);
                                            }
                                        }
                                    }
                                } else {
                                    $mikrotikMsg .= " Warning: MikroTik credential update may have failed.";
                                }
                            }
                        } else {
                            $mikrotikMsg .= " Note: User '{$oldUsername}' not found on MikroTik router.";
                        }
                        $mikrotikService->disconnect();
                    } else {
                        $mikrotikMsg .= " Warning: Could not connect to MikroTik router for credential update.";
                    }
                }
            } catch (\Exception $e) {
                $mikrotikMsg .= " Warning: MikroTik credential sync failed: " . $e->getMessage();
                Log::error("MikroTik credential sync failed for client {$client->id}: " . $e->getMessage());
            }
        }

        if ($oldStatus !== $client->status) {
            $statusSyncService = new \App\Services\SimpleMikroTikService();
            $syncResult = $statusSyncService->syncClientStatusOnRouter(
                $client,
                $client->status,
                $oldStatus,
                $request->status_reason,
                auth()->id()
            );
            if ($syncResult['message']) {
                $mikrotikMsg .= ' ' . $syncResult['message'];
            }

            event(new ClientStatusChanged(
                $client,
                $oldStatus,
                $client->status,
                'manual',
                $request->status_reason,
                auth()->id()
            ));
            
            \App\Services\ClientActivityService::logStatusChange(
                $client,
                $oldStatus,
                $client->status,
                $request->status_reason ?? 'Status changed via client edit',
                'manual'
            );
            
            try {
                (new \App\Services\TelegramService())->sendClientStatusNotification(
                    $client->client_id ?? $client->id,
                    $client->name,
                    $oldStatus,
                    $client->status,
                    auth()->user()->name ?? 'System',
                    $client->username ?? '',
                    $client->phone ?? '',
                    $client->package?->name ?? '',
                    $request->status_reason ?? ''
                );
            } catch (\Throwable $e) {
            }
            
            if ($client->status === 'active') {
                $client->last_active_date = now();
                $client->last_status_change = now();
                $client->auto_inactive_note = null;
                if ($client->expire_date && $client->expire_date->lte(\Carbon\Carbon::today('Asia/Dhaka'))) {
                    $client->expire_date = \Carbon\Carbon::now('Asia/Dhaka')->addMonthNoOverflow()->toDateString();
                }
                $client->save();
            } elseif ($client->status === 'inactive') {
                $client->last_inactive_date = now();
                $client->last_logout = now();
                $client->connection_status = 'offline';
                $client->auto_inactive_note = $request->status_reason ?: 'Deactivated by admin';
                $client->save();
            }
        }

        ActivityLog::log('update', 'clients', "Updated client: {$client->name}");

        return redirect()->route('admin.clients.show', $client)->with('success', 'Client updated successfully.' . $mikrotikMsg);
    }

    /**
     * Show delete confirmation page with all client bills
     */
    public function confirmDelete(Client $client)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Super Admin') && !\App\Services\MenuPermissionService::canAction($user, 'client_list', 'delete')) {
            abort(403, 'You do not have permission to delete clients.');
        }

        $client->load(['zone', 'area', 'box', 'package', 'bills', 'payments']);
        $bills = $client->bills()->orderBy('billing_year', 'desc')->orderBy('billing_month', 'desc')->get();
        $payments = $client->payments()->orderBy('payment_date', 'desc')->get();
        
        $deleteReasons = \App\Models\DropdownOption::getOptions('client_delete_reasons');

        $equipmentController = new \App\Http\Controllers\Admin\ReturnedEquipmentController();
        $equipmentResponse = $equipmentController->getClientEquipment($client);
        $clientEquipment = json_decode($equipmentResponse->getContent(), true)['equipment'] ?? [];

        return view('admin.clients.confirm-delete', compact('client', 'bills', 'payments', 'deleteReasons', 'clientEquipment'));
    }

    /**
     * Delete a specific bill for a client
     */
    public function deleteBill(Client $client, \App\Models\Bill $bill)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Super Admin') && !\App\Services\MenuPermissionService::canAction($user, 'client_list', 'edit')) {
            abort(403, 'You do not have permission to delete bills.');
        }

        if ($bill->client_id !== $client->id) {
            return back()->with('error', 'Bill does not belong to this client.');
        }
        
        DB::beginTransaction();
        try {
            $billNo = $bill->bill_no;
            $bill->delete();
            
            $billingService = app(BillingService::class);
            $billingService->reapplyAllPaymentsToBills($client);
            $billingService->recalculateClientBalance($client);
            
            ActivityLog::log('delete', 'bills', "Deleted bill {$billNo} for client: {$client->name}");
            
            DB::commit();
            return back()->with('success', "Bill {$billNo} deleted successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting bill: ' . $e->getMessage());
        }
    }

    public function detectRouterBrand(Client $client)
    {
        $mac = $client->mac_address ?? $client->session_mac;
        if (!$mac) {
            return back()->with('error', 'No MAC address found for this client.');
        }

        $vendor = \App\Services\MacVendorService::lookup($mac);
        if ($vendor) {
            $client->update(['router_brand' => $vendor]);
            return back()->with('success', "Router brand detected: {$vendor}");
        }

        return back()->with('error', 'Could not detect router brand from MAC address: ' . $mac);
    }

    public function detectAllRouterBrands()
    {
        $clients = Client::whereNotNull('mac_address')->whereNull('router_brand')->limit(100)->get();
        $detected = 0;

        foreach ($clients as $client) {
            $vendor = \App\Services\MacVendorService::lookup($client->mac_address);
            if ($vendor) {
                $client->update(['router_brand' => $vendor]);
                $detected++;
            }
            usleep(500000);
        }

        return back()->with('success', "Router brand detected for {$detected} out of {$clients->count()} clients.");
    }

    public function destroy(Request $request, Client $client)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Super Admin') && !\App\Services\MenuPermissionService::canAction($user, 'client_list', 'delete')) {
            abort(403, 'You do not have permission to delete clients.');
        }

        $clientName = $client->name;
        $clientId = $client->client_id;
        $oldStatus = $client->status;
        $reason = $request->input('delete_reason', 'Deleted by admin');

        if ($client->username && $client->connection_type === 'pppoe') {
            try {
                $deviceId = $client->mikrotik_device_id;
                $device = $deviceId ? \App\Models\MikrotikDevice::find($deviceId) : \App\Models\MikrotikDevice::where('is_active', true)->first();

                if ($device) {
                    $mk = new \App\Services\SimpleMikroTikService($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
                    $result = $mk->deletePPPoESecret($client->username);
                    \Illuminate\Support\Facades\Log::info("Client delete: MikroTik PPPoE secret '{$client->username}' removed from {$device->name}", ['result' => $result]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Client delete: Failed to remove MikroTik PPPoE secret '{$client->username}': " . $e->getMessage());
            }
        }

        $client->update([
            'status' => 'deleted',
            'deleted_reason' => $reason,
            'deleted_by' => auth()->id(),
            'deleted_at_custom' => now(),
        ]);

        event(new ClientStatusChanged($client, $oldStatus, 'deleted', 'manual', $reason));
        ActivityLog::log('delete', 'clients', "Soft-deleted client: {$clientName} ({$clientId}) - Reason: {$reason}");
        return redirect()->route('admin.clients.index')->with('success', "Client {$clientName} has been deleted and removed from MikroTik. You can restore from Left Client page.");
    }

    public function pending()
    {
        $perPage = in_array((int)request('per_page'), [20, 50, 100, 200]) ? (int)request('per_page') : (int)session('client_list_per_page', 20);
        $clients = Client::with(['zone', 'package'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        return view('admin.clients.pending', compact('clients'));
    }

    public function inactive()
    {
        $perPage = in_array((int)request('per_page'), [20, 50, 100, 200]) ? (int)request('per_page') : (int)session('client_list_per_page', 20);
        $clients = Client::withoutGlobalScope('exclude_deleted')
            ->with(['zone', 'package'])
            ->whereNull('reseller_id')
            ->where('status', 'deleted')
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);
        
        $inactiveCount = Client::where('status', 'inactive')->whereNull('reseller_id')->count();
        $deletedCount = Client::withoutGlobalScope('exclude_deleted')->where('status', 'deleted')->whereNull('reseller_id')->count();
        $leftCount = $deletedCount;
        
        return view('admin.clients.inactive', compact('clients', 'leftCount', 'inactiveCount', 'deletedCount'));
    }

    public function dueClients()
    {
        // Store last list page for redirect after payment
        session(['last_client_list_url' => url()->current()]);
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->user_type === 'super_admin' || (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')));
        
        $perPage = in_array((int)request('per_page'), [20, 50, 100, 200]) ? (int)request('per_page') : (int)session('client_list_per_page', 20);
        $clients = Client::with(['zone', 'area', 'package'])
            ->whereNull('reseller_id')
            ->where('due_amount', '>', 0)
            ->orderBy('due_amount', 'desc')
            ->paginate($perPage);
        
        $totalDue = Client::where('due_amount', '>', 0)->sum('due_amount');
        $dueCount = Client::where('due_amount', '>', 0)->count();
        
        return view('admin.clients.due', compact('clients', 'totalDue', 'dueCount'));
    }

    public function scheduler()
    {
        $schedules = \App\Models\ClientSchedule::with(['client', 'package', 'creator'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('scheduled_at', 'desc')
            ->paginate(in_array((int)request('per_page'), [20, 50, 100, 200]) ? (int)request('per_page') : 30);

        $packages = Package::where('is_active', true)->orderBy('name')->get();
        $_profileCompanyId = auth()->user()?->company_id;
        $availableProfiles = \App\Models\MikrotikProfile::withoutGlobalScopes()
            ->where('is_active', true)
            ->when($_profileCompanyId && !auth()->user()?->isSuperAdmin(), fn($q) => $q->where('company_id', $_profileCompanyId))
            ->orderBy('name')
            ->pluck('name');

        return view('admin.clients.scheduler', compact('schedules', 'packages', 'availableProfiles'));
    }

    public function storeSchedule(Request $request)
    {
        $rules = [
            'client_id' => 'required|exists:clients,id',
            'action' => 'required|in:enable,disable,change_package,change_profile',
            'scheduled_at' => 'required|date',
            'note' => 'nullable|string|max:500',
        ];

        if ($request->action === 'change_package') {
            $rules['package_id'] = 'required|exists:packages,id';
            $rules['mikrotik_profile'] = 'required|string|max:100';
        } elseif ($request->action === 'change_profile') {
            $rules['mikrotik_profile'] = 'required|string|max:100';
        }

        $request->validate($rules);

        $schedule = \App\Models\ClientSchedule::create([
            'client_id' => $request->client_id,
            'action' => $request->action,
            'scheduled_at' => $request->scheduled_at,
            'package_id' => $request->action === 'change_package' ? $request->package_id : null,
            'mikrotik_profile' => in_array($request->action, ['change_package', 'change_profile']) ? $request->mikrotik_profile : null,
            'note' => $request->note,
            'created_by' => auth()->id(),
            'company_id' => auth()->user()->company_id,
            'status' => 'pending',
        ]);

        $schedule->load(['client', 'package']);

        $client = $schedule->client;
        $packageName = $schedule->package->name ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully.',
            'schedule' => [
                'id' => $schedule->id,
                'client_name' => $client->name ?? 'N/A',
                'client_id_display' => $client->client_id ?? '',
                'client_username' => $client->username ?? '-',
                'client_url' => $client ? route('admin.clients.show', $client->id) : '#',
                'action' => $schedule->action,
                'package_id' => $schedule->package_id,
                'package_name' => $packageName,
                'mikrotik_profile' => $schedule->mikrotik_profile,
                'note' => $schedule->note,
                'scheduled_at' => \Carbon\Carbon::parse($schedule->scheduled_at)->format('d-m-Y h:i A'),
                'scheduled_at_iso' => \Carbon\Carbon::parse($schedule->scheduled_at)->toIso8601String(),
                'created_by' => auth()->user()->name,
            ],
        ]);
    }

    public function deleteSchedule(\App\Models\ClientSchedule $schedule)
    {
        if ($schedule->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending schedules can be cancelled.'], 422);
        }

        $schedule->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Schedule cancelled.']);
    }

    public function updateSchedule(Request $request, \App\Models\ClientSchedule $schedule)
    {
        if ($schedule->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending schedules can be edited.'], 422);
        }

        $rules = [
            'action' => 'required|in:enable,disable,change_package,change_profile',
            'scheduled_at' => 'required|date',
            'note' => 'nullable|string|max:500',
        ];

        if ($request->action === 'change_package') {
            $rules['package_id'] = 'required|exists:packages,id';
            $rules['mikrotik_profile'] = 'required|string|max:100';
        } elseif ($request->action === 'change_profile') {
            $rules['mikrotik_profile'] = 'required|string|max:100';
        }

        $request->validate($rules);

        $schedule->update([
            'action' => $request->action,
            'scheduled_at' => $request->scheduled_at,
            'package_id' => $request->action === 'change_package' ? $request->package_id : null,
            'mikrotik_profile' => in_array($request->action, ['change_package', 'change_profile']) ? $request->mikrotik_profile : null,
            'note' => $request->note,
        ]);

        $schedule->load(['client', 'package']);
        $client = $schedule->client;

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully.',
            'schedule' => [
                'id' => $schedule->id,
                'client_name' => $client->name ?? 'N/A',
                'client_id_display' => $client->client_id ?? '',
                'client_username' => $client->username ?? '-',
                'client_url' => $client ? route('admin.clients.show', $client->id) : '#',
                'action' => $schedule->action,
                'package_id' => $schedule->package_id,
                'package_name' => $schedule->package->name ?? null,
                'mikrotik_profile' => $schedule->mikrotik_profile,
                'note' => $schedule->note,
                'scheduled_at' => \Carbon\Carbon::parse($schedule->scheduled_at)->format('d-m-Y h:i A'),
                'scheduled_at_iso' => \Carbon\Carbon::parse($schedule->scheduled_at)->toIso8601String(),
                'created_by' => $schedule->creator->name ?? '-',
            ],
        ]);
    }

    public function portalManage()
    {
        $settings = [
            'portal_enabled' => \App\Models\SystemSetting::get('client_portal_enabled', '1'),
            'view_bills' => \App\Models\SystemSetting::get('client_portal_view_bills', '1'),
            'pay_online' => \App\Models\SystemSetting::get('client_portal_pay_online', '1'),
            'create_tickets' => \App\Models\SystemSetting::get('client_portal_create_tickets', '1'),
            'change_package' => \App\Models\SystemSetting::get('client_portal_change_package', '0'),
        ];
        $notices = \DB::table('announcements')
            ->where(function($q){ $q->where('target','all')->orWhere('target','clients'); })
            ->orderByDesc('created_at')->get();
        return view('admin.clients.portal-manage', compact('settings','notices'));
    }


    public function storeNotice(\Illuminate\Http\Request $request)
    {
        $request->validate(['content' => 'required|string|max:500']);
        $user = auth()->user();
        $companyId = $user->company_id ?? null;
        \DB::table('announcements')->insert([
            'title' => 'Client Notice',
            'content' => $request->content,
            'target' => 'clients',
            'is_active' => $request->has('is_active') ? 1 : 1,
            'company_id' => $companyId,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Notice added successfully.');
    }

    public function deleteNotice($id)
    {
        \DB::table('announcements')->where('id',$id)->delete();
        return back()->with('success', 'Notice deleted.');
    }

    public function toggleNotice($id)
    {
        $notice = \DB::table('announcements')->where('id',$id)->first();
        if($notice){
            \DB::table('announcements')->where('id',$id)->update(['is_active'=>!$notice->is_active,'updated_at'=>now()]);
        }
        return back()->with('success', 'Notice status updated.');
    }

    public function updatePortalSettings(Request $request)
    {
        \App\Models\SystemSetting::set('client_portal_enabled', $request->has('portal_enabled') ? '1' : '0', 'portal');
        \App\Models\SystemSetting::set('client_portal_view_bills', $request->has('view_bills') ? '1' : '0', 'portal');
        \App\Models\SystemSetting::set('client_portal_pay_online', $request->has('pay_online') ? '1' : '0', 'portal');
        \App\Models\SystemSetting::set('client_portal_create_tickets', $request->has('create_tickets') ? '1' : '0', 'portal');
        \App\Models\SystemSetting::set('client_portal_change_package', $request->has('change_package') ? '1' : '0', 'portal');

        return back()->with('success', 'Client portal settings updated successfully.');
    }

    public function applyTemporaryDeadline(Request $request, Client $client)
    {
        $request->validate([
            'deadline_date' => 'required|date',
            'reason' => 'required|string|max:255',
        ]);

        // Must be strictly after current effective deadline
        // NOTE: expire_date is connection expiry (not billing deadline) — do NOT use it here
        if ($client->temp_deadline_date) {
            // If there's already a temp deadline, new date must be after it
            $effectiveDeadline = \Carbon\Carbon::parse($client->temp_deadline_date)->toDateString();
        } else {
            // Use current billing cycle deadline: billing_date day of current month
            $billingDay = (int)($client->billing_date ?? 1);
            $billingDay = max(1, min(28, $billingDay));
            $billingDeadline = \Carbon\Carbon::create(now()->year, now()->month, $billingDay);
            // If billing deadline has passed, allow any future date (no restriction)
            $effectiveDeadline = $billingDeadline->isPast() ? null : $billingDeadline->toDateString();
        }

        $newDate = \Carbon\Carbon::parse($request->deadline_date)->toDateString();

        if ($effectiveDeadline && $newDate <= $effectiveDeadline) {
            return response()->json([
                'success' => false,
                'message' => "New deadline ({$newDate}) must be after the current billing deadline ({$effectiveDeadline}).",
            ], 422);
        }

        try {
            $billingService = app(BillingService::class);
            $result = $billingService->applyTemporaryDeadline(
                $client,
                $request->deadline_date,
                $request->reason,
                auth()->id()
            );

            if ($request->ajax()) {
                $client->refresh();
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'new_status' => $client->status,
                ]);
            }

            return back()->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error("Failed to apply temporary deadline for client {$client->id}: " . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to apply deadline: ' . $e->getMessage()], 500);
            }

            return back()->with('error', 'Failed to apply temporary deadline.');
        }
    }

    public function clearTemporaryDeadline(Request $request, Client $client)
    {
        try {
            $billingService = app(BillingService::class);
            $billingService->clearTemporaryDeadline($client, 'manual');

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Temporary deadline cleared.']);
            }

            return back()->with('success', 'Temporary deadline cleared.');
        } catch (\Exception $e) {
            Log::error("Failed to clear temporary deadline for client {$client->id}: " . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to clear deadline.'], 500);
            }

            return back()->with('error', 'Failed to clear temporary deadline.');
        }
    }

    public function updateRemarks(Request $request, Client $client)
    {
        $request->validate(['remarks' => 'nullable|string|max:2000']);
        $client->update(['remarks' => $request->input('remarks', '')]);
        return response()->json(['success' => true, 'message' => 'Remarks updated successfully.']);
    }

    public function changePackage(Request $request, Client $client)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'billing_option' => 'required|in:day_calculation,set_new_price,no_change',
            'mikrotik_profile' => 'required|string|max:100',
            'new_price' => 'nullable|numeric|min:0',
            'ip_address' => 'nullable|string|max:45',
            'note' => 'nullable|string|max:500',
        ]);

        $oldPackage = $client->package;
        $newPackage = Package::findOrFail($request->package_id);

        if ($client->package_id == $request->package_id) {
            return response()->json(['success' => false, 'message' => 'Client is already on this package.'], 422);
        }

        $oldMonthlyFee = $client->monthly_fee ?? ($oldPackage->price ?? 0);
        $newMonthlyFee = $newPackage->price;
        $billingNote = '';

        $result = DB::transaction(function () use ($client, $request, $oldPackage, $newPackage, $oldMonthlyFee, $newMonthlyFee, &$billingNote) {
            switch ($request->billing_option) {
                case 'day_calculation':
                    $today = now();
                    $billingDay = $client->billing_date ?? 1;
                    $currentCycleStart = $today->copy()->day($billingDay);
                    if ($currentCycleStart->gt($today)) {
                        $currentCycleStart->subMonth();
                    }
                    $nextCycleStart = $currentCycleStart->copy()->addMonth();
                    $totalDaysInCycle = $currentCycleStart->diffInDays($nextCycleStart);
                    $remainingDays = max($today->diffInDays($nextCycleStart), 0);

                    if ($remainingDays > 0 && $totalDaysInCycle > 0 && $oldMonthlyFee != $newMonthlyFee) {
                        $dailyOld = $oldMonthlyFee / $totalDaysInCycle;
                        $dailyNew = $newMonthlyFee / $totalDaysInCycle;
                        $difference = round(($dailyNew - $dailyOld) * $remainingDays, 2);

                        if ($difference > 0) {
                            Bill::create([
                                'client_id' => $client->id,
                                'billing_month' => $today->month,
                                'billing_year' => $today->year,
                                'bill_date' => $today,
                                'due_date' => $today->copy()->addDays(7),
                                'package_amount' => $difference,
                                'previous_due' => 0,
                                'discount' => 0,
                                'advance_used' => 0,
                                'total_amount' => $difference,
                                'paid_amount' => 0,
                                'due_amount' => $difference,
                                'status' => 'unpaid',
                                'remarks' => 'Package Upgrade (Day Calc): ' . ($oldPackage->name ?? 'N/A') . ' → ' . $newPackage->name . ' (' . $remainingDays . ' days remaining)',
                                'generated_by' => auth()->id(),
                            ]);
                            $client->due_amount = ($client->due_amount ?? 0) + $difference;
                            $billingNote = 'Upgrade bill ৳' . number_format($difference) . ' generated for ' . $remainingDays . ' remaining days.';
                        } elseif ($difference < 0) {
                            $credit = abs($difference);
                            $client->balance = ($client->balance ?? 0) + $credit;
                            $billingNote = 'Credit of ৳' . number_format($credit) . ' added for ' . $remainingDays . ' remaining days (downgrade).';
                        }
                    }
                    $client->monthly_fee = $newMonthlyFee;
                    break;

                case 'set_new_price':
                    $client->monthly_fee = $request->new_price ?? $newMonthlyFee;
                    $billingNote = 'Monthly fee set to ৳' . number_format($client->monthly_fee) . '.';
                    break;

                case 'no_change':
                    $client->monthly_fee = $newMonthlyFee;
                    $billingNote = 'No billing adjustment.';
                    break;
            }

            $mikrotikProfile = $request->mikrotik_profile;
            $client->package_id = $request->package_id;
            $client->mikrotik_profile_name = $mikrotikProfile;
            if ($request->filled('ip_address')) {
                $client->ip_address = $request->ip_address;
            }
            $client->save();

            return true;
        });

        $mikrotikSuccess = false;
        $mikrotikProfile = $request->mikrotik_profile;
        if ($client->username) {
            try {
                $device = null;
                if ($client->mikrotik_device_id) {
                    $device = MikrotikDevice::find($client->mikrotik_device_id);
                } elseif ($client->zone && $client->zone->mikrotik_device_id) {
                    $device = MikrotikDevice::find($client->zone->mikrotik_device_id);
                }
                if (!$device) {
                    $device = MikrotikDevice::where('is_active', true)->first();
                }
                if ($device) {
                    $mikrotik = \App\Services\MikroTikService::fromDevice($device);
                    $connection = $mikrotik->connect();
                    if ($connection !== false) {
                        $mikrotikSuccess = $mikrotik->changePPPoEProfile($client->username, $mikrotikProfile);
                        $mikrotik->disconnect();
                    }
                }
            } catch (\Exception $e) {
                Log::error('MikroTik package change failed: ' . $e->getMessage());
            }
        }

        \App\Services\ClientActivityService::log(
            $client, 'package_changed',
            $oldPackage->name ?? 'N/A',
            $newPackage->name,
            'Package changed by admin from ' . ($oldPackage->name ?? 'N/A') . ' to ' . $newPackage->name
                . ($request->note ? ' | Note: ' . $request->note : '')
                . ($billingNote ? ' | ' . $billingNote : ''),
            'manual'
        );

        \App\Models\ClientStatusLog::logStatusChange(
            client: $client,
            oldStatus: $oldPackage->name ?? 'N/A',
            newStatus: $newPackage->name,
            reason: 'Package changed via client profile' . ($request->note ? ' | ' . $request->note : ''),
            source: \App\Models\ClientStatusLog::SOURCE_MANUAL,
            changedBy: auth()->id()
        );

        $message = 'Package changed to ' . $newPackage->name . ' successfully.';
        if ($billingNote) {
            $message .= ' ' . $billingNote;
        }
        if (!$mikrotikSuccess && $client->username) {
            $message .= ' (MikroTik sync may need verification)';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function toggleMikrotikProfile(Request $request, Client $client)
    {
        $isAjax = $request->expectsJson() || $request->ajax();
        $client->refresh();
        $inactiveProfileName = 'Inactive';
        $currentProfile = $client->mikrotik_profile_name
            ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);

        if (!$currentProfile) {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => 'No MikroTik profile found for this client.'], 400);
            }
            return back()->with('error', 'No MikroTik profile found for this client.');
        }

        $isCurrentlyInactive = strtolower($currentProfile) === strtolower($inactiveProfileName);

        \Illuminate\Support\Facades\Log::info("MikroTik toggle for {$client->name}: currentProfile={$currentProfile}, isInactive=" . ($isCurrentlyInactive ? 'yes' : 'no') . ", prev={$client->previous_mikrotik_profile}");

        if ($isCurrentlyInactive) {
            $newProfile = $client->previous_mikrotik_profile
                ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);
            if (!$newProfile) {
                return response()->json(['success' => false, 'message' => 'Cannot restore: No previous profile found.'], 400);
            }
            $newStatus = 'on';
        } else {
            $newProfile = $inactiveProfileName;
            $newStatus = 'off';
        }

        $mikrotikSuccess = false;
        $mikrotikWarning = '';
        $device = $client->mikrotikDevice ?? MikrotikDevice::where('is_active', true)->first();
        $hasDevice = $device && $client->username;

        if ($hasDevice) {
            try {
                $mikrotik = new \App\Services\SimpleMikroTikService();
                if ($mikrotik->connectFromDevice($device)) {
                    $mikrotikSuccess = $mikrotik->changePPPoEProfile($client->username, $newProfile);
                    $mikrotik->disconnect();
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('MikroTik profile toggle failed for ' . $client->username . ': ' . $e->getMessage());
            }

            if (!$mikrotikSuccess) {
                $mikrotikWarning = ' (MikroTik unreachable - local only, sync when device is available)';
            }
        }

        $reason = $request->input('reason', '');

        $updateData = ['mikrotik_profile_name' => $newProfile];
        if ($newStatus === 'off') {
            $updateData['previous_mikrotik_profile'] = $currentProfile;
            $updateData['status'] = 'inactive';
            $updateData['auto_inactive_note'] = $reason ?: 'Deactivated by admin';
        } else {
            $updateData['previous_mikrotik_profile'] = null;
            $updateData['status'] = 'active';
        }
        $client->update($updateData);

        $statusLabel = $newStatus === 'on' ? 'ON (Active)' : 'OFF (Inactive)';

        $logNewStatus = $newStatus === 'on' ? 'active' : 'inactive';
        $logOldStatus = $newStatus === 'on' ? 'inactive' : 'active';
        \App\Models\ClientStatusLog::logStatusChange(
            client: $client,
            oldStatus: $logOldStatus,
            newStatus: $logNewStatus,
            reason: $reason ?: "MikroTik toggle: profile changed to {$newProfile}",
            source: 'manual',
            changedBy: auth()->id()
        );

        ClientActivityService::log(
            client: $client,
            actionType: $newStatus === 'on' ? 'activated' : 'deactivated',
            oldValue: $logOldStatus,
            newValue: $logNewStatus,
            reason: $reason ?: "MikroTik toggle: profile changed to {$newProfile}",
            triggeredBy: 'manual'
        );

        $message = "Client {$client->name} MikroTik set to {$statusLabel}" . $mikrotikWarning;

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'profile' => $newProfile,
                'mikrotik_applied' => $mikrotikSuccess,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    public function payInstallationCharge(Request $request, Client $client)
    {
        $payMethod = \App\Models\PaymentMethod::find($request->payment_method_id);
        $isCash = $payMethod && stripos($payMethod->name, 'cash') !== false;

        $rules = [
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'money_receipt_no' => 'nullable|string|max:100',
            'transaction_id' => $isCash ? 'nullable|string|max:100' : 'required|string|max:100',
            'sender_number' => $isCash ? 'nullable|string|max:50' : 'required|string|max:50',
            'notes_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ];

        $request->validate($rules);

        $installCharge = (float)$client->installation_charge ?: (float)$client->signup_fee ?: 0;
        if ($installCharge <= 0) {
            return back()->with('warning', 'No installation charge to pay.');
        }

        $totalPaid = (float) ClientServiceCharge::where('client_id', $client->id)
            ->where('charge_type', 'Installation Charge')
            ->whereNotNull('paid_by')
            ->where('amount', '>', 0)
            ->sum('amount');

        $remaining = $installCharge - $totalPaid;
        if ($remaining <= 0) {
            return back()->with('warning', 'Installation charge is already fully paid.');
        }

        $amount = (float) $request->amount;
        $note = trim(($request->notes_reason ? $request->notes_reason . '. ' : '') . ($request->notes ?: ''));

        DB::beginTransaction();
        try {
            ClientServiceCharge::create([
                'client_id' => $client->id,
                'charge_type' => 'Installation Charge',
                'amount' => $amount,
                'description' => $note ?: 'Installation charge payment',
                'payment_method' => $payMethod->name ?? 'Cash',
                'payment_method_id' => $request->payment_method_id,
                'money_receipt_no' => $request->money_receipt_no,
                'transaction_id' => $request->transaction_id,
                'sender_number' => $request->sender_number,
                'paid_by' => auth()->id(),
                'charge_date' => now()->toDateString(),
            ]);

            $newTotalPaid = $totalPaid + $amount;
            if ($newTotalPaid >= $installCharge) {
                $client->update(['signup_fee_paid' => true]);
            }

            if ($request->send_sms && $client->phone) {
                try {
                    app(\App\Services\SmsService::class)->sendPaymentConfirmation($client, $amount, $request->money_receipt_no ?: 'IC-' . $client->client_id);
                } catch (\Exception $smsEx) {
                }
            }

            DB::commit();

            try {
                $methodName = $payMethod->name ?? 'Cash';
                $methodLower = strtolower($methodName);
                $paymentRef = '';
                if (in_array($methodLower, ['cash', 'hand_cash'])) {
                    $paymentRef = $request->money_receipt_no ?? '';
                } elseif (in_array($methodLower, ['bank', 'bank_transfer'])) {
                    $paymentRef = $request->transaction_id ?? $request->money_receipt_no ?? '';
                } else {
                    $paymentRef = $request->transaction_id ?? '';
                }
                $remainingAmount = $installCharge - $newTotalPaid;
                $statusText = $newTotalPaid >= $installCharge ? '🟢 Fully Paid' : '🟡 Remaining: ৳' . number_format($remainingAmount, 2);
                $telegram = new \App\Services\TelegramService();
                $telegram->sendPaymentNotification(
                    $client->client_id ?? $client->id,
                    $client->name,
                    (float) $amount,
                    $methodName,
                    $client->username ?? '',
                    auth()->user()->name ?? 'System',
                    'Installation Charge',
                    (float) max(0, $remainingAmount),
                    now()->format('d-m-Y'),
                    $paymentRef,
                    $request->sender_number ?? ''
                );
            } catch (\Throwable $e) {
            }

            $msg = 'Installation charge payment of ৳' . number_format($amount) . ' recorded.';
            if ($newTotalPaid >= $installCharge) {
                $msg .= ' Fully paid!';
            } else {
                $msg .= ' Remaining: ৳' . number_format($installCharge - $newTotalPaid) . '.';
            }
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to process: ' . $e->getMessage());
        }
    }

    public function unpayInstallationCharge(Client $client)
    {
        if (!$client->signup_fee_paid) {
            return back()->with('warning', 'Installation charge is already due.');
        }

        $amount = (float)$client->installation_charge ?: (float)$client->signup_fee ?: 0;

        DB::beginTransaction();
        try {
            $client->update(['signup_fee_paid' => false]);

            $lastCharge = ClientServiceCharge::where('client_id', $client->id)
                ->where('charge_type', 'Installation Charge')
                ->latest()
                ->first();

            if ($lastCharge) {
                $lastCharge->delete();
            }

            ClientServiceCharge::create([
                'client_id' => $client->id,
                'charge_type' => 'Installation Charge Reversal',
                'amount' => -$amount,
                'description' => 'Installation charge payment reversed by ' . auth()->user()->name,
                'payment_method' => 'Reversal',
                'paid_by' => auth()->id(),
                'charge_date' => now()->toDateString(),
            ]);

            DB::commit();
            return back()->with('success', 'Installation charge marked as due.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to process: ' . $e->getMessage());
        }
    }

    private function parseFlexibleDate(string $dateString): string
    {
        try {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $dateString)->format('Y-m-d');
        } catch (\Exception $e) {}
        try {
            return \Carbon\Carbon::createFromFormat('d-m-Y', $dateString)->format('Y-m-d');
        } catch (\Exception $e) {}
        return \Carbon\Carbon::parse($dateString)->format('Y-m-d');
    }

    private function applyZoneFilter(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $user = auth()->user();
        if (!$user || $user->hasRole('Super Admin') || $user->hasRole('admin') || $user->hasRole('Manager') || $user->hasRole('Assistant Admin') || $user->hasRole('Assistant Manager')) {
            return;
        }

        $zoneRestrictedRoles = ['Collection Agent', 'billman', 'Billman'];
        $isZoneRestricted = false;
        foreach ($zoneRestrictedRoles as $roleName) {
            if ($user->hasRole($roleName)) {
                $isZoneRestricted = true;
                break;
            }
        }

        if (!$isZoneRestricted) return;

        $employee = \App\Models\Employee::where('user_id', $user->id)->first();
        if (!$employee) return;

        $zoneIds = $employee->zone_ids;
        if (is_string($zoneIds)) {
            $zoneIds = json_decode($zoneIds, true);
        }
        if (!empty($zoneIds) && is_array($zoneIds)) {
            $query->whereIn('zone_id', $zoneIds);
        }
    }

    public function clientMap(Request $request)
    {
        $zones = Zone::orderBy('name')->get();
        $areas = Area::orderBy('name')->get();

        $query = Client::with(['zone', 'area', 'package'])
            ->whereNotIn('status', ['deleted']);

        if ($request->zone_id) {
            $query->where('zone_id', $request->zone_id);
        }
        if ($request->area_id) {
            $query->where('area_id', $request->area_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $allClients = $query->get();

        $withLocation    = $allClients->filter(fn($c) => $c->google_map_lat && $c->google_map_lng);
        $withoutLocation = $allClients->filter(fn($c) => !$c->google_map_lat || !$c->google_map_lng);

        $mapMarkers = $withLocation->map(function ($client) {
            return [
                'id'         => $client->id,
                'name'       => $client->name,
                'lat'        => (float) $client->google_map_lat,
                'lng'        => (float) $client->google_map_lng,
                'status'     => $client->status ?? 'active',
                'zone'       => $client->zone->name ?? 'N/A',
                'area'       => $client->area->name ?? 'N/A',
                'phone'      => $client->phone ?? '',
                'username'   => $client->username ?? '',
                'package'    => $client->package->name ?? 'N/A',
                'address'    => $client->address ?? '',
                'edit_url'   => route('admin.clients.edit', $client->id),
                'show_url'   => route('admin.clients.show', $client->id),
            ];
        })->values();

        $statusCounts = [
            'active'   => $allClients->where('status', 'active')->count(),
            'inactive' => $allClients->where('status', 'inactive')->count(),
            'expired'  => $allClients->where('status', 'expired')->count(),
        ];

        return view('admin.clients.client-map', compact(
            'zones', 'areas', 'mapMarkers', 'withoutLocation',
            'allClients', 'statusCounts'
        ));
    }

    public function refreshOnlineInfo(Client $client)
    {
        if ($client->username && $client->mikrotik_device_id) {
            try {
                $mikrotikService = new \App\Services\SimpleMikroTikService();
                $device = $client->mikrotikDevice;
                if ($device && $mikrotikService->connectFromDevice($device)) {
                    $session = $mikrotikService->getActiveSession($client->username);
                    if ($session) {
                        $upd = ['connection_status' => 'online', 'last_online' => now()];
                        if (!empty($session['address']))   $upd['session_ip']      = $session['address'];
                        if (!empty($session['caller-id'])) $upd['session_mac']     = $session['caller-id'];
                        if (!empty($session['uptime']))    $upd['session_uptime'] = $session['uptime'];
                        $client->update($upd); $client->refresh();
                    } else {
                        if ($client->connection_status === 'online') {
                            $client->update(['connection_status'=>'offline','session_ip'=>null,'session_mac'=>null,'session_uptime'=>null,'last_logout'=>now()]);
                            $client->refresh();
                        }
                    }
                }
            } catch (\Exception $e) {}
        }
        $ip  = $client->session_ip  ?: $client->ip_address;
        $mac = $client->session_mac ?: $client->mac_address;
        $secs = 0;
        if ($client->session_uptime && $client->connection_status === 'online') {
            $u = $client->session_uptime;
            if (preg_match('/^(\\d{1,2}):(\\d{2}):(\\d{2})$/', $u, $m)) {
                $secs = $m[1]*3600 + $m[2]*60 + $m[3];
            } else {
                if(preg_match('/(\\d+)w/',$u,$m)) $secs+=$m[1]*604800;
                if(preg_match('/(\\d+)d/',$u,$m)) $secs+=$m[1]*86400;
                if(preg_match('/(\\d+)h/',$u,$m)) $secs+=$m[1]*3600;
                if(preg_match('/(\\d+)m/',$u,$m)) $secs+=$m[1]*60;
                if(preg_match('/(\\d+)s/',$u,$m)) $secs+=$m[1];
            }
            if ($client->last_online) $secs += abs((int) \Carbon\Carbon::parse($client->last_online)->diffInSeconds(now()));
        }
        return response()->json(['ip'=>$ip,'mac'=>$mac,'uptime_secs'=>$secs,'connection_status'=>$client->connection_status]);
    }

    public function setInstallationCharge(\Illuminate\Http\Request $request, \App\Models\Client $client)
    {
        $request->validate([
            'installation_charge' => 'required|numeric|min:0',
            'cable_length_meters' => 'nullable|numeric|min:0',
        ]);

        $newCharge = (float) $request->installation_charge;
        $oldCharge = (float) $client->installation_charge;

        $client->installation_charge = $newCharge;
        if ($request->filled('cable_length_meters')) {
            $client->cable_length_meters = (float) $request->cable_length_meters;
        }
        $client->save();

        if ($newCharge !== $oldCharge) {
            $baseCharge = \App\Models\ClientServiceCharge::where('client_id', $client->id)
                ->where('charge_type', 'Installation Charge')
                ->whereNull('paid_by')
                ->first();
            if ($baseCharge) {
                $baseCharge->update(['amount' => $newCharge]);
            } elseif ($newCharge > 0 && !$client->signup_fee_paid) {
                \App\Models\ClientServiceCharge::create([
                    'client_id' => $client->id,
                    'charge_type' => 'Installation Charge',
                    'amount' => $newCharge,
                    'description' => 'Installation charge',
                ]);
            }
        }

        return back()->with('success', 'Installation charge updated.');
    }
    /**
     * Automatically scan the ONU table and re-link the correct ONU to this client.
     * Matches by onu_serial, onu_mac, or client_id.
     */
    public function fixOnuLink(\App\Models\Client $client)
    {
        try {
            $onu = null;

            // 1. Try by serial number
            if ($client->onu_serial) {
                $onu = \Illuminate\Support\Facades\DB::table('onus')
                    ->where('serial_number', $client->onu_serial)
                    ->orderByDesc('updated_at')
                    ->first();
            }

            // 2. Try by MAC address
            if (!$onu && $client->onu_mac) {
                $onu = \Illuminate\Support\Facades\DB::table('onus')
                    ->where('mac_address', $client->onu_mac)
                    ->orderByDesc('updated_at')
                    ->first();
            }

            // 3. Try by client_id already linked in onus table
            if (!$onu) {
                $onu = \Illuminate\Support\Facades\DB::table('onus')
                    ->where('client_id', $client->id)
                    ->orderByDesc('updated_at')
                    ->first();
            }

            if (!$onu) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching ONU found in the database. Please run an ONU sync from the OLT device first, then try again.',
                ]);
            }

            \Illuminate\Support\Facades\DB::beginTransaction();

            // Link ONU to client
            \Illuminate\Support\Facades\DB::table('onus')
                ->where('id', $onu->id)
                ->update(['client_id' => $client->id, 'updated_at' => now()]);

            // Update client with correct ONU data
            $client->update([
                'onu_id'     => $onu->id,
                'onu_serial' => $onu->serial_number ?? $client->onu_serial,
                'onu_mac'    => $onu->mac_address   ?? $client->onu_mac,
                'olt_port'   => $onu->pon_port       ?? $client->olt_port,
                'onu_status' => $onu->status         ?? $client->onu_status,
                'onu_rx_power' => $onu->rx_power     ?? $client->onu_rx_power,
            ]);

            // Also sync clients table for rx_power / status
            if ($onu->pon_port) {
                \Illuminate\Support\Facades\DB::table('clients')
                    ->where('id', $client->id)
                    ->update([
                        'onu_status'   => $onu->status    ?? null,
                        'onu_rx_power' => $onu->rx_power  ?? null,
                        'onu_id'       => $onu->id,
                        'olt_port'     => $onu->pon_port,
                        'updated_at'   => now(),
                    ]);
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ONU linked successfully! ONU ID: ' . $onu->id . ' | Port: ' . ($onu->pon_port ?? '?') . ':' . ($onu->onu_number ?? '?') . ' | MAC: ' . ($onu->mac_address ?? '?'),
                'onu_id'  => $onu->id,
                'onu_mac' => $onu->mac_address,
                'onu_serial' => $onu->serial_number,
                'olt_port' => ($onu->pon_port ?? '') . ':' . ($onu->onu_number ?? ''),
                'status'  => $onu->status,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }


}
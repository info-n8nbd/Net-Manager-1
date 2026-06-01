<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\Bill;
use App\Models\MikrotikDevice;
use App\Models\StaffDue;
use App\Models\Zone;
use App\Models\Expense;
use App\Models\Reseller;
use App\Models\ActivityLog;
use App\Services\DashboardService;
use App\Services\SmsService;
use App\Services\SimpleMikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $companyId = auth()->user()->company_id;
        $ispNF = fn($q) => $q->whereNull('reseller_id')->where(fn($q2) => $q2->where('is_free', false)->orWhereNull('is_free'));
        $isp = fn($q) => $q->whereNull('reseller_id');

        $clientStats = DB::table('clients')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereNull('deleted_at')
            ->where('status', '!=', 'deleted')
            ->whereNull('reseller_id')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'active' AND connection_status = 'online' THEN 1 ELSE 0 END) as running,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'left' THEN 1 ELSE 0 END) as left_count,
                SUM(CASE WHEN monthly_fee = 0 THEN 1 ELSE 0 END) as waiver,
                SUM(CASE WHEN connection_status = 'online' THEN 1 ELSE 0 END) as online,
                SUM(CASE WHEN status = 'active' AND connection_status != 'online' THEN 1 ELSE 0 END) as offline,
                SUM(CASE WHEN due_amount > 0 AND (is_free = false OR is_free IS NULL) THEN 1 ELSE 0 END) as unpaid,
                SUM(CASE WHEN status = 'active' AND due_amount > 0 AND (is_free = false OR is_free IS NULL) THEN 1 ELSE 0 END) as unpaid_extension,
                SUM(CASE WHEN (is_free = false OR is_free IS NULL) THEN due_amount ELSE 0 END) as total_due,
                SUM(CASE WHEN mikrotik_device_id IS NOT NULL THEN 1 ELSE 0 END) as pop_clients,
                SUM(CASE WHEN mikrotik_device_id IS NOT NULL AND status = 'active' THEN 1 ELSE 0 END) as enabled_pop,
                SUM(CASE WHEN mikrotik_device_id IS NOT NULL AND status != 'active' THEN 1 ELSE 0 END) as disabled_pop
            ")
            ->first();

        $now2 = now();
        $monthlyStats = [
            'new_clients' => Client::whereNull('reseller_id')->whereMonth('created_at', $now2->month)->whereYear('created_at', $now2->year)->count(),
            'deactivated' => Client::whereNull('reseller_id')->where('status', 'inactive')->whereMonth('updated_at', $now2->month)->count(),
            'renewed' => Payment::whereMonth('payment_date', $now2->month)->whereYear('payment_date', $now2->year)->whereHas('client', $isp)->distinct('client_id')->count('client_id'),
            'billing_clients' => Bill::where('billing_month', $now2->month)->where('billing_year', $now2->year)->whereHas('client', $ispNF)->distinct('client_id')->count('client_id'),
            'partial_paid' => Bill::where('billing_month', $now2->month)->where('billing_year', $now2->year)->where('status', 'partial')->whereHas('client', $ispNF)->count(),
            'month_bills' => Bill::where('billing_month', $now2->month)->where('billing_year', $now2->year)->whereHas('client', $ispNF)->sum('total_amount'),
            'monthly_collection' => Payment::whereMonth('payment_date', $now2->month)->whereYear('payment_date', $now2->year)->whereHas('client', $isp)->sum('amount'),
        ];

        $billDateExpired = Client::whereNull('reseller_id')->where('status', 'active')->where('expire_date', '<', now())->count();

        $totalPops = MikrotikDevice::count();
        
        $oldDue = Bill::where('status', '!=', 'paid')
            ->whereRaw("(billing_year * 100 + billing_month) < ?", [now()->year * 100 + now()->month])
            ->whereHas('client', $ispNF)
            ->sum('due_amount');

        $ticketStats = DB::table('tickets')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->selectRaw("
                SUM(CASE WHEN status IN ('open', 'in_progress') THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN status IN ('open', 'in_progress') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as processing
            ")
            ->first();

        $recentTickets = Ticket::with('client')->latest()->take(5)->get();

        $todayKey = now()->format('Y-m-d');
        $todayCollection = Payment::whereDate('payment_date', today())->whereHas('client', $isp)->sum('amount');
        $todayPayments = Payment::with(['client', 'paymentMethod'])
            ->whereDate('payment_date', today())
            ->whereHas('client', $isp)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $revenue = Payment::selectRaw('EXTRACT(MONTH FROM payment_date) as month, SUM(amount) as total')
            ->whereYear('payment_date', now()->year)
            ->whereHas('client', $isp)
            ->groupBy(DB::raw('EXTRACT(MONTH FROM payment_date)'))
            ->pluck('total', 'month')
            ->toArray();
        $monthlyRevenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyRevenue[] = $revenue[$i] ?? 0;
        }

        // Daily collection for current month — dashboard bar chart
        $dashboardDailyData = \App\Models\Payment::whereYear('payment_date', now()->year)
            ->whereMonth('payment_date', now()->month)
            ->selectRaw('DATE(payment_date) as date, SUM(amount) as total')
            ->groupBy(\DB::raw('DATE(payment_date)'))
            ->orderBy('date')
            ->get();
        
        $zoneStats = \App\Models\Zone::withCount(['clients' => function($q) {
            $q->whereNull('reseller_id')->where('status', 'active');
        }])->get()->filter(fn($z) => $z->clients_count > 0)->sortByDesc('clients_count')->values();
        
        $areaStats = \App\Models\Area::with('zone')->withCount(['clients' => function($q) {
            $q->whereNull('reseller_id')->where('status', 'active');
        }])->get()->filter(fn($a) => $a->clients_count > 0)->sortByDesc('clients_count')->values();
        
        try {
            $smsBalance = app(SmsService::class)->getBalance();
        } catch (\Throwable $e) {
            $smsBalance = null;
        }

        $totalClients = $clientStats->total ?? 0;
        $activeClients = $clientStats->active ?? 0;
        $runningClients = $clientStats->running ?? 0;
        $inactiveClients = $clientStats->inactive ?? 0;
        $suspendedClients = 0;
        $pendingClients = $clientStats->pending ?? 0;
        $leftClients = $clientStats->left_count ?? 0;
        $waiverClients = $clientStats->waiver ?? 0;
        $onlineClients = $clientStats->online ?? 0;
        $offlineClients = $clientStats->offline ?? 0;
        $unpaidClients = $clientStats->unpaid ?? 0;
        $unpaidExtension = $clientStats->unpaid_extension ?? 0;
        $totalDue = $clientStats->total_due ?? 0;
        $totalPopClients = $clientStats->pop_clients ?? 0;
        $enabledPopClients = $clientStats->enabled_pop ?? 0;
        $disabledPopClients = $clientStats->disabled_pop ?? 0;
        $blockedClients = $suspendedClients;
        $activeTotal = $activeClients;
        $inactiveTotal = $inactiveClients;

        $newClients = $monthlyStats['new_clients'] ?? 0;
        $deactivatedClients = $monthlyStats['deactivated'] ?? 0;
        $renewedClients = $monthlyStats['renewed'] ?? 0;
        $billingClients = $monthlyStats['billing_clients'] ?? 0;
        $partiallyPaidClients = $monthlyStats['partial_paid'] ?? 0;
        $monthBills = $monthlyStats['month_bills'] ?? 0;
        $monthlyCollection = $monthlyStats['monthly_collection'] ?? 0;

        $openTickets = $ticketStats->open ?? 0;
        $pendingTickets = $ticketStats->pending ?? 0;
        $processingTickets = $ticketStats->processing ?? 0;

        $pendingApplications = $pendingClients;

        // Staff Dues Data
        $staffDuesStats = [
            'total_pending' => StaffDue::sum('current_due'),
            'total_collected' => StaffDue::sum('total_collected'),
            'total_submitted' => StaffDue::sum('total_submitted'),
        ];
        
        $staffWithDues = StaffDue::with('user')
            ->where('current_due', '>', 0)
            ->orderByDesc('current_due')
            ->take(5)
            ->get();
        
        $pendingPaymentsCount = Payment::where('status', 'pending')->count();

        $now = now();
        
        $todayAutoInactive = Client::whereNull('reseller_id')->where('status', 'inactive')
            ->whereDate('last_inactive_date', today())
            ->count();

        $todayExpense = 0;
        try {
            $todayExpense += DB::table('expenses')->when($companyId, fn($q) => $q->where('company_id', $companyId))->whereDate('expense_date', today())->sum('amount');
            $todayExpense += DB::table('vendor_ledgers')->when($companyId, fn($q) => $q->where('company_id', $companyId))->where('type', 'payment')->whereDate('transaction_date', today())->sum('debit');
            $todayExpense += \App\Models\LoanPayment::whereHas('loan', fn($q) => $q->where('type', 'given'))->whereDate('payment_date', today())->sum('amount');
            $todayExpense += DB::table('agent_commissions')->when($companyId, fn($q) => $q->where('company_id', $companyId))->whereDate('date', today())->sum('commission_amount');
        } catch (\Exception $e) {}
        
        $signupFees = \App\Models\ClientServiceCharge::where('charge_type', 'Installation Charge')
            ->whereNotNull('paid_by')
            ->where('amount', '>', 0)
            ->whereMonth('charge_date', $now->month)
            ->whereYear('charge_date', $now->year)
            ->sum('amount');

        $installationChargeTotal = Client::whereNull('reseller_id')->whereMonth('connection_date', $now->month)
            ->whereYear('connection_date', $now->year)
            ->where('installation_charge', '>', 0)
            ->sum('installation_charge');

        $installationChargePaid = \App\Models\ClientServiceCharge::where('charge_type', 'Installation Charge')
            ->whereNotNull('paid_by')
            ->where('amount', '>', 0)
            ->whereMonth('charge_date', $now->month)
            ->whereYear('charge_date', $now->year)
            ->sum('amount');

        $installationChargeDue = max(0, $installationChargeTotal - $installationChargePaid);
            
        // MAC reseller client collection (clients under MAC resellers paying bills)
        $resellerCollection = Payment::whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->whereHas('client', fn($q) => $q->whereNotNull('reseller_id'))
            ->sum('amount');
        $otherCollection = 0;

        // ── Reseller bill tracking (type-wise) ─────────────────────────────
        $macResellerBillMonthly = \App\Models\ResellerBill::whereHas('reseller', fn($q) => $q->where('type', 'mac'))
            ->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)
            ->sum('total_paid');
        $bwResellerBillMonthly = \App\Models\ResellerBill::whereHas('reseller', fn($q) => $q->where('type', 'bandwidth'))
            ->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)
            ->sum('total_paid');
        $macResellerBillDue = \App\Models\ResellerBill::whereHas('reseller', fn($q) => $q->where('type', 'mac'))
            ->where('due_amount', '>', 0)->sum('due_amount');
        $bwResellerBillDue = \App\Models\ResellerBill::whereHas('reseller', fn($q) => $q->where('type', 'bandwidth'))
            ->where('due_amount', '>', 0)->sum('due_amount');
        $macResellerCount = Reseller::where('type', 'mac')->count();
        $bwResellerCount  = Reseller::where('type', 'bandwidth')->count();

        $runningMonthBills = Bill::where('billing_month', $now->month)
            ->where('billing_year', $now->year)
            ->whereHas('client', $ispNF)
            ->sum('total_amount');
            
        $runningMonthBillsCollection = Bill::where('billing_month', $now->month)
            ->where('billing_year', $now->year)
            ->whereHas('client', $ispNF)
            ->sum('paid_amount');

        $monthlyExpense = 0;
        try {
            $monthlyExpense += DB::table('expenses')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereMonth('expense_date', $now->month)
                ->whereYear('expense_date', $now->year)
                ->sum('amount');
            $monthlyExpense += DB::table('vendor_ledgers')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('type', 'payment')
                ->whereMonth('transaction_date', $now->month)
                ->whereYear('transaction_date', $now->year)
                ->sum('debit');
            $monthlyExpense += \App\Models\LoanPayment::whereHas('loan', fn($q) => $q->where('type', 'given'))
                ->whereMonth('payment_date', $now->month)
                ->whereYear('payment_date', $now->year)
                ->sum('amount');
            $monthlyExpense += DB::table('agent_commissions')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereMonth('date', $now->month)
                ->whereYear('date', $now->year)
                ->sum('commission_amount');
        } catch (\Exception $e) {}

        $prevMonth = $now->copy()->subMonth();
        $prevMonthPaidClients = Payment::whereMonth('payment_date', $prevMonth->month)
            ->whereYear('payment_date', $prevMonth->year)
            ->whereHas('client', $isp)
            ->distinct('client_id')
            ->count('client_id');

        $thisMonthPaidClients = Payment::whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->whereHas('client', $isp)
            ->distinct('client_id')
            ->count('client_id');

        $prevMonthDue = Bill::where('billing_month', $prevMonth->month)
            ->where('billing_year', $prevMonth->year)
            ->whereHas('client', $ispNF)
            ->sum('due_amount');

        $thisMonthDue = Bill::where('billing_month', $now->month)
            ->where('billing_year', $now->year)
            ->whereHas('client', $ispNF)
            ->sum('due_amount');

        $overdueClients = Bill::where('due_amount', '>', 0)
            ->whereHas('client', $ispNF)
            ->distinct('client_id')
            ->count('client_id');

        $recentAutoInactive = Client::whereNull('reseller_id')->where('status', 'inactive')
            ->whereNotNull('last_inactive_date')
            ->orderByDesc('last_inactive_date')
            ->take(10)
            ->get(['id', 'client_id', 'name', 'phone', 'expire_date', 'last_inactive_date', 'zone_id']);

        $employeeLogs = DB::table('activity_logs')
            ->join('users', 'activity_logs.user_id', '=', 'users.id')
            ->select('activity_logs.user_id', 'users.name as user_name', DB::raw('COUNT(*) as total_actions'))
            ->where('users.company_id', $companyId)
            ->whereDate('activity_logs.created_at', today())
            ->groupBy('activity_logs.user_id', 'users.name')
            ->orderByDesc('total_actions')
            ->limit(10)
            ->get();

        $zoneAutoInactive = Zone::withCount(['clients' => function($q) {
            $q->whereNull('reseller_id')->where('status', 'inactive')
              ->whereNotNull('last_inactive_date');
        }])->get();


        // ── Zone-wise Problem Data ──────────────────────────────
        $zoneProblemData = DB::table('zones')
            ->leftJoin('clients', function($j) {
                $j->on('clients.zone_id', '=', 'zones.id')
                  ->whereNull('clients.deleted_at')
                  ->whereNull('clients.reseller_id');
            })
            ->where('zones.company_id', $companyId)
            ->selectRaw("zones.id, zones.name,
                SUM(CASE WHEN clients.status='inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN clients.due_amount > 0 AND (clients.is_free=false OR clients.is_free IS NULL) THEN 1 ELSE 0 END) as due_clients,
                SUM(CASE WHEN clients.status='active' THEN 1 ELSE 0 END) as active_clients")
            ->groupBy('zones.id', 'zones.name')
            ->orderByDesc(DB::raw("SUM(CASE WHEN clients.status='inactive' THEN 1 ELSE 0 END) + SUM(CASE WHEN clients.due_amount > 0 THEN 1 ELSE 0 END)"))
            ->limit(8)->get();

        $zoneTicketsRaw = DB::table('tickets')
            ->join('clients', 'clients.id', '=', 'tickets.client_id')
            ->join('zones', 'zones.id', '=', 'clients.zone_id')
            ->where('zones.company_id', $companyId)
            ->whereIn('tickets.status', ['open', 'in_progress'])
            ->whereNull('tickets.deleted_at')
            ->selectRaw("zones.id, COUNT(tickets.id) as ticket_count")
            ->groupBy('zones.id')->get()->keyBy('id');

        $zoneProblemData = $zoneProblemData->map(function($z) use ($zoneTicketsRaw) {
            $z->tickets      = $zoneTicketsRaw[$z->id]->ticket_count ?? 0;
            $z->problem_score = ($z->inactive ?? 0) * 3 + ($z->due_clients ?? 0) * 2 + ($z->tickets ?? 0) * 4;
            return $z;
        })->sortByDesc('problem_score')->values();

        // ── Employee Efficiency Data ────────────────────────────
        $employeeEfficiency = DB::table('activity_logs')
            ->join('users', 'activity_logs.user_id', '=', 'users.id')
            ->select(
                'users.name as user_name',
                DB::raw('COUNT(*) as total_actions'),
                DB::raw('COUNT(DISTINCT DATE(activity_logs.created_at)) as active_days')
            )
            ->where('users.company_id', $companyId)
            ->whereMonth('activity_logs.created_at', now()->month)
            ->whereYear('activity_logs.created_at', now()->year)
            ->groupBy('users.name', 'activity_logs.user_id')
            ->orderByDesc('total_actions')
            ->limit(8)->get();


        // ── Technician / Employee Ticket Stats (all time) ────
        $empTicketStats = DB::table('users')
            ->leftJoin('tickets as tech_t', function($j) use ($companyId) {
                $j->on('tech_t.technician_id', '=', 'users.id')
                  ->whereNull('tech_t.deleted_at');
            })
            ->where('users.company_id', $companyId)
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->whereIn('users.user_type', ['technician','employee','admin'])
            ->select('users.id', 'users.name as user_name', 'users.user_type',
                DB::raw('COUNT(tech_t.id) as assigned'),
                DB::raw("SUM(CASE WHEN tech_t.status IN ('resolved','closed') THEN 1 ELSE 0 END) as resolved"),
                DB::raw("SUM(CASE WHEN tech_t.status IN ('open','in_progress') THEN 1 ELSE 0 END) as pending"))
            ->groupBy('users.id', 'users.name', 'users.user_type')
            ->orderByDesc('assigned')
            ->limit(10)->get()
            ->filter(fn($e) => $e->assigned > 0 || ($employeeEfficiency->firstWhere('user_name', $e->user_name)?->total_actions ?? 0) > 0)
            ->values();

        // ── Zone + Category ticket data ─────────────────────────
        $zoneAllTickets = DB::table('zones')
            ->join('clients', 'clients.zone_id', '=', 'zones.id')
            ->join('tickets', 'tickets.client_id', '=', 'clients.id')
            ->where('zones.company_id', $companyId)
            ->whereNull('tickets.deleted_at')
            ->selectRaw('zones.name, COUNT(tickets.id) as cnt')
            ->groupBy('zones.name', 'zones.id')
            ->orderByDesc('cnt')
            ->limit(8)->get();

        $ticketCategories = DB::table('ticket_categories')
            ->leftJoin('tickets', function($j) use ($companyId) {
                $j->on('tickets.category_id', '=', 'ticket_categories.id')
                  ->whereNull('tickets.deleted_at');
            })
            ->where('ticket_categories.company_id', $companyId)
            ->selectRaw('ticket_categories.name, COUNT(tickets.id) as cnt')
            ->groupBy('ticket_categories.name', 'ticket_categories.id')
            ->orderByDesc('cnt')
            ->limit(10)->get()
            ->filter(fn($c) => $c->cnt > 0)->values();

        $expiringSoon = Client::whereNull('reseller_id')->where('status', 'active')
            ->whereBetween('expire_date', [now()->toDateString(), now()->addDays(3)->toDateString()])
            ->orderBy('expire_date')
            ->take(15)
            ->get(['id', 'client_id', 'name', 'phone', 'expire_date', 'zone_id']);
        $expiringSoonCount = Client::whereNull('reseller_id')->where('status', 'active')
            ->whereBetween('expire_date', [now()->toDateString(), now()->addDays(3)->toDateString()])
            ->count();

        return view('admin.dashboard.index', compact(
            'totalClients', 'activeClients', 'runningClients', 'inactiveClients', 'suspendedClients',
            'pendingClients', 'leftClients', 'waiverClients', 'deactivatedClients', 'newClients', 'renewedClients',
            'billingClients', 'partiallyPaidClients', 'unpaidClients', 'billDateExpired',
            'blockedClients', 'unpaidExtension', 'totalPops', 'totalPopClients', 'enabledPopClients', 'disabledPopClients',
            'totalDue', 'oldDue', 'todayCollection', 'monthlyCollection', 'todayPayments',
            'recentTickets', 'openTickets', 'pendingTickets', 'processingTickets', 'onlineClients', 'offlineClients',
            'monthBills', 'signupFees', 'installationChargeTotal', 'installationChargePaid', 'installationChargeDue', 'pendingApplications', 'monthlyRevenue', 'zoneStats', 'areaStats', 'smsBalance',
            'activeTotal', 'inactiveTotal', 'staffDuesStats', 'staffWithDues', 'pendingPaymentsCount',
            'todayAutoInactive', 'todayExpense', 'resellerCollection', 'otherCollection',
            'runningMonthBills', 'runningMonthBillsCollection', 'recentAutoInactive', 'employeeLogs', 'zoneAutoInactive', 'zoneProblemData', 'employeeEfficiency', 'empTicketStats', 'zoneAllTickets', 'ticketCategories',
            'expiringSoon', 'expiringSoonCount',
            'monthlyExpense', 'prevMonthPaidClients', 'thisMonthPaidClients', 'prevMonthDue', 'thisMonthDue', 'overdueClients',
            'macResellerBillMonthly', 'bwResellerBillMonthly', 'macResellerBillDue', 'bwResellerBillDue',
            'macResellerCount', 'bwResellerCount',
            'dashboardDailyData'
        ));
    }


    public function serverHealth()
    {
        try {
            // CPU usage (1-min load average as % of cores)
            $cpuCores = (int)shell_exec("nproc");
            $loadAvg  = sys_getloadavg();
            $cpuLoad  = $cpuCores > 0 ? round(($loadAvg[0] / $cpuCores) * 100, 1) : 0;
            $cpuLoad  = min($cpuLoad, 100);

            // RAM usage
            $memInfo = [];
            foreach (file("/proc/meminfo") as $line) {
                [$key, $val] = array_pad(explode(":", $line, 2), 2, "");
                $memInfo[trim($key)] = (int)trim($val);
            }
            $totalRam = round($memInfo["MemTotal"] / 1024);
            $freeRam  = round(($memInfo["MemAvailable"] ?? $memInfo["MemFree"]) / 1024);
            $usedRam  = $totalRam - $freeRam;
            $ramPct   = $totalRam > 0 ? round(($usedRam / $totalRam) * 100, 1) : 0;

            // Disk usage for /
            $diskTotal = disk_total_space("/");
            $diskFree  = disk_free_space("/");
            $diskUsed  = $diskTotal - $diskFree;
            $diskPct   = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;
            $diskUsedG  = round($diskUsed / 1073741824, 1);
            $diskTotalG = round($diskTotal / 1073741824, 1);

            // Uptime
            $uptimeSec = (int)file_get_contents("/proc/uptime");
            $uptimeDays = floor($uptimeSec / 86400);
            $uptimeHrs  = floor(($uptimeSec % 86400) / 3600);
            $uptimeMin  = floor(($uptimeSec % 3600) / 60);
            $uptime = "{$uptimeDays}d {$uptimeHrs}h {$uptimeMin}m";

            // Laravel log size
            $logFile  = storage_path("logs/laravel.log");
            $logSizeKB = file_exists($logFile) ? round(filesize($logFile) / 1024, 1) : 0;

            // DB size
            $dbSize = \DB::selectOne("SELECT pg_size_pretty(pg_database_size(current_database())) AS size")?->size ?? "N/A";

            return response()->json([
                "cpu"     => ["load" => $cpuLoad, "cores" => $cpuCores],
                "ram"     => ["used_mb" => $usedRam, "total_mb" => $totalRam, "pct" => $ramPct],
                "disk"    => ["used_gb" => $diskUsedG, "total_gb" => $diskTotalG, "pct" => $diskPct],
                "uptime"  => $uptime,
                "log_kb"  => $logSizeKB,
                "db_size" => $dbSize,
                "timestamp" => now()->format("H:i:s"),
            ]);
        } catch (\Throwable $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }
    public function liveStats(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $ispNF = fn($q) => $q->whereNull('reseller_id')->where(fn($q2) => $q2->where('is_free', false)->orWhereNull('is_free'));
        $isp = fn($q) => $q->whereNull('reseller_id');

        $clientStats = DB::table('clients')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereNull('deleted_at')
            ->where('status', '!=', 'deleted')
            ->whereNull('reseller_id')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' AND connection_status = 'online' THEN 1 ELSE 0 END) as running,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN monthly_fee = 0 THEN 1 ELSE 0 END) as waiver,
                SUM(CASE WHEN status = 'left' THEN 1 ELSE 0 END) as left_count,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as blocked,
                SUM(CASE WHEN connection_status = 'online' THEN 1 ELSE 0 END) as online,
                SUM(CASE WHEN status = 'active' AND connection_status != 'online' THEN 1 ELSE 0 END) as offline,
                SUM(CASE WHEN due_amount > 0 AND (is_free = false OR is_free IS NULL) THEN 1 ELSE 0 END) as unpaid,
                SUM(CASE WHEN status = 'active' AND due_amount > 0 AND (is_free = false OR is_free IS NULL) THEN 1 ELSE 0 END) as unpaid_extension,
                SUM(CASE WHEN (is_free = false OR is_free IS NULL) THEN due_amount ELSE 0 END) as total_due,
                SUM(CASE WHEN mikrotik_device_id IS NOT NULL THEN 1 ELSE 0 END) as pop_clients,
                SUM(CASE WHEN mikrotik_device_id IS NOT NULL AND status = 'active' THEN 1 ELSE 0 END) as enabled_pop,
                SUM(CASE WHEN mikrotik_device_id IS NOT NULL AND status != 'active' THEN 1 ELSE 0 END) as disabled_pop
            ")
            ->first();

        $now = now();
        $monthlyStats = [
            'new_clients' => Client::whereNull('reseller_id')->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            'renewed' => Payment::whereMonth('payment_date', $now->month)->whereYear('payment_date', $now->year)->whereHas('client', $isp)->distinct('client_id')->count('client_id'),
            'deactivated' => Client::whereNull('reseller_id')->where('status', 'inactive')->whereMonth('updated_at', $now->month)->count(),
            'billing_clients' => Bill::where('billing_month', $now->month)->where('billing_year', $now->year)->whereHas('client', $ispNF)->distinct('client_id')->count('client_id'),
            'partial_paid' => Bill::where('billing_month', $now->month)->where('billing_year', $now->year)->where('status', 'partial')->whereHas('client', $ispNF)->count(),
            'monthly_collection' => Payment::whereMonth('payment_date', $now->month)->whereYear('payment_date', $now->year)->whereHas('client', $isp)->sum('amount'),
        ];

        $billDateExpired = Client::whereNull('reseller_id')->where('status', 'active')->where('expire_date', '<', now())->count();

        $ticketStats = DB::table('tickets')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->selectRaw("
                SUM(CASE WHEN status IN ('open', 'in_progress') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN priority = 'urgent' AND status IN ('open', 'in_progress') THEN 1 ELSE 0 END) as critical
            ")
            ->first();

        $routerStats = DB::table('mikrotik_devices')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active")
            ->first();

        $todayCollection = Payment::whereDate('payment_date', today())->whereHas('client', $isp)->sum('amount');
        $todayNewClients = Client::whereNull('reseller_id')->whereDate('created_at', today())->count();
        $todayPaymentsCount = Payment::whereDate('payment_date', today())->whereHas('client', $isp)->count();

        $totalClients = $clientStats->total ?? 0;
        $activeClients = ($clientStats->total ?? 0) - ($clientStats->inactive ?? 0) - ($clientStats->left_count ?? 0);
        $runningClients = $clientStats->running ?? 0;
        $inactiveClients = $clientStats->inactive ?? 0;
        $waiverClients = $clientStats->waiver ?? 0;
        $leftClients = $clientStats->left_count ?? 0;
        $blockedClients = $clientStats->blocked ?? 0;
        $onlineClients = $clientStats->online ?? 0;
        $offlineClients = $clientStats->offline ?? 0;
        $unpaidClients = $clientStats->unpaid ?? 0;
        $unpaidExtension = $clientStats->unpaid_extension ?? 0;
        $totalDue = $clientStats->total_due ?? 0;
        $totalPopClients = $clientStats->pop_clients ?? 0;
        $enabledPopClients = $clientStats->enabled_pop ?? 0;
        $disabledPopClients = $clientStats->disabled_pop ?? 0;

        $newClients = $monthlyStats['new_clients'] ?? 0;
        $renewedClients = $monthlyStats['renewed'] ?? 0;
        $deactivatedClients = $monthlyStats['deactivated'] ?? 0;
        $billingClients = $monthlyStats['billing_clients'] ?? 0;
        $partiallyPaidClients = $monthlyStats['partial_paid'] ?? 0;
        $monthlyCollection = $monthlyStats['monthly_collection'] ?? 0;

        $pendingTickets = $ticketStats->pending ?? 0;
        $processingTickets = $ticketStats->processing ?? 0;
        $criticalTickets = $ticketStats->critical ?? 0;

        $totalPops = $routerStats->total ?? 0;
        $activeRouters = $routerStats->active ?? 0;

        $now = now();

        $runningMonthBills = Bill::where('billing_month', $now->month)->where('billing_year', $now->year)
            ->whereHas('client', $ispNF)->sum('total_amount');
        $runningMonthBillsCollection = Bill::where('billing_month', $now->month)->where('billing_year', $now->year)
            ->whereHas('client', $ispNF)->sum('paid_amount');

        $prevMonth = $now->copy()->subMonth();
        $prevMonthDue = Bill::where('billing_month', $prevMonth->month)->where('billing_year', $prevMonth->year)
            ->whereHas('client', $ispNF)->sum('due_amount');
        $thisMonthDue = Bill::where('billing_month', $now->month)->where('billing_year', $now->year)
            ->whereHas('client', $ispNF)->sum('due_amount');
        $overdueClients = Bill::where('due_amount', '>', 0)
            ->whereHas('client', $ispNF)->distinct('client_id')->count('client_id');

        $prevMonthPaidClients = Payment::whereMonth('payment_date', $prevMonth->month)
            ->whereYear('payment_date', $prevMonth->year)->whereHas('client', $isp)
            ->distinct('client_id')->count('client_id');
        $thisMonthPaidClients = Payment::whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)->whereHas('client', $isp)
            ->distinct('client_id')->count('client_id');

        $todayExpense = 0;
        $monthlyExpense = 0;
        try {
            $todayExpense += DB::table('expenses')->when($companyId, fn($q) => $q->where('company_id', $companyId))->whereDate('expense_date', today())->sum('amount');
            $todayExpense += DB::table('vendor_ledgers')->when($companyId, fn($q) => $q->where('company_id', $companyId))->where('type', 'payment')->whereDate('transaction_date', today())->sum('debit');
            $todayExpense += \App\Models\LoanPayment::whereHas('loan', fn($q) => $q->where('type', 'given'))->whereDate('payment_date', today())->sum('amount');
            $todayExpense += DB::table('agent_commissions')->when($companyId, fn($q) => $q->where('company_id', $companyId))->whereDate('date', today())->sum('commission_amount');
            $monthlyExpense += DB::table('expenses')->when($companyId, fn($q) => $q->where('company_id', $companyId))->whereMonth('expense_date', $now->month)
                ->whereYear('expense_date', $now->year)->sum('amount');
            $monthlyExpense += DB::table('vendor_ledgers')->when($companyId, fn($q) => $q->where('company_id', $companyId))->where('type', 'payment')
                ->whereMonth('transaction_date', $now->month)->whereYear('transaction_date', $now->year)->sum('debit');
            $monthlyExpense += \App\Models\LoanPayment::whereHas('loan', fn($q) => $q->where('type', 'given'))
                ->whereMonth('payment_date', $now->month)->whereYear('payment_date', $now->year)->sum('amount');
            $monthlyExpense += DB::table('agent_commissions')->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereMonth('date', $now->month)->whereYear('date', $now->year)->sum('commission_amount');
        } catch (\Exception $e) {}

        $todayAutoInactive = Client::whereNull('reseller_id')->where('status', 'inactive')
            ->whereDate('last_inactive_date', today())->count();

        $installationChargeTotal = Client::whereNull('reseller_id')->whereMonth('connection_date', $now->month)
            ->whereYear('connection_date', $now->year)->where('installation_charge', '>', 0)
            ->sum('installation_charge');
        $installationChargePaid = \App\Models\ClientServiceCharge::where('charge_type', 'Installation Charge')
            ->whereNotNull('paid_by')->where('amount', '>', 0)
            ->whereMonth('charge_date', $now->month)->whereYear('charge_date', $now->year)->sum('amount');
        $installationChargeDue = max(0, $installationChargeTotal - $installationChargePaid);

        $resellerCollection = Payment::whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->whereHas('client', fn($q) => $q->whereNotNull('reseller_id'))
            ->sum('amount');

        // Reseller bill stats (type-wise)
        $macResellerBillMonthly = \App\Models\ResellerBill::whereHas('reseller', fn($q) => $q->where('type', 'mac'))
            ->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('total_paid');
        $bwResellerBillMonthly = \App\Models\ResellerBill::whereHas('reseller', fn($q) => $q->where('type', 'bandwidth'))
            ->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('total_paid');
        $macResellerBillDue = \App\Models\ResellerBill::whereHas('reseller', fn($q) => $q->where('type', 'mac'))
            ->where('due_amount', '>', 0)->sum('due_amount');
        $bwResellerBillDue = \App\Models\ResellerBill::whereHas('reseller', fn($q) => $q->where('type', 'bandwidth'))
            ->where('due_amount', '>', 0)->sum('due_amount');

        return response()->json([
            'timestamp' => now()->format('h:i:s A'),
            'date' => now()->format('d-m-Y'),
            'total_clients' => $totalClients,
            'active_total' => $activeClients,
            'running_clients' => $runningClients,
            'inactive_clients' => $inactiveClients,
            'waiver_clients' => $waiverClients,
            'new_clients' => $newClients,
            'renewed_clients' => $renewedClients,
            'deactivated_clients' => $deactivatedClients,
            'left_clients' => $leftClients,
            'billing_clients' => $billingClients,
            'partially_paid' => $partiallyPaidClients,
            'unpaid_clients' => $unpaidClients,
            'online_clients' => $onlineClients,
            'blocked_clients' => $blockedClients,
            'bill_date_expired' => $billDateExpired,
            'unpaid_extension' => $unpaidExtension,
            'total_pops' => $totalPops,
            'total_pop_clients' => $totalPopClients,
            'enabled_pop_clients' => $enabledPopClients,
            'disabled_pop_clients' => $disabledPopClients,
            'today_collection' => $todayCollection,
            'today_collection_formatted' => number_format($todayCollection, 0),
            'monthly_collection' => $monthlyCollection,
            'monthly_collection_formatted' => number_format($monthlyCollection, 0),
            'total_due' => $totalDue,
            'total_due_formatted' => number_format($totalDue, 0),
            'pending_tickets' => $pendingTickets,
            'processing_tickets' => $processingTickets,
            'critical_tickets' => $criticalTickets,
            'today_new_clients' => $todayNewClients,
            'today_payments_count' => $todayPaymentsCount,
            'active_routers' => $activeRouters,
            'total_routers' => $totalPops,
            'network_health' => $totalPops > 0 ? round(($activeRouters / $totalPops) * 100, 1) : 100,
            'running_month_bills' => number_format($runningMonthBills, 0),
            'month_bills_collected' => number_format($runningMonthBillsCollection, 0),
            'prev_month_due' => number_format($prevMonthDue, 0),
            'this_month_due' => number_format($thisMonthDue, 0),
            'overdue_clients' => $overdueClients,
            'prev_month_paid' => $prevMonthPaidClients,
            'this_month_paid' => $thisMonthPaidClients,
            'today_expense' => number_format($todayExpense, 0),
            'monthly_expense' => number_format($monthlyExpense, 0),
            'today_auto_inactive' => $todayAutoInactive,
            'installation_charge_total' => '৳' . number_format($installationChargeTotal, 0),
            'installation_charge_subtitle' => 'Paid: ৳' . number_format($installationChargePaid, 0) . ' | Due: ৳' . number_format($installationChargeDue, 0),
            'reseller_collection' => number_format($resellerCollection, 0),
            'other_collection' => 0,
            'mac_reseller_bill_monthly' => number_format($macResellerBillMonthly, 0),
            'bw_reseller_bill_monthly' => number_format($bwResellerBillMonthly, 0),
            'mac_reseller_bill_due' => number_format($macResellerBillDue, 0),
            'bw_reseller_bill_due' => number_format($bwResellerBillDue, 0),
            'staff_dues_pending' => '৳' . number_format(StaffDue::sum('current_due'), 0),
            'staff_dues_collected' => '৳' . number_format(StaffDue::sum('total_collected'), 0),
            'staff_dues_submitted' => '৳' . number_format(StaffDue::sum('total_submitted'), 0),
        ]);
    }

    public function liveNetworkStatus()
    {
        $devices = MikrotikDevice::select('id', 'name', 'host', 'is_active', 'last_sync_at')
            ->orderBy('name')
            ->get()
            ->map(function($device) {
                $isOnline = $device->is_active && $device->last_sync_at && $device->last_sync_at > now()->subMinutes(5);
                $clientsOnline = Client::where('mikrotik_device_id', $device->id)->where('connection_status', 'online')->count();
                $clientsTotal = Client::where('mikrotik_device_id', $device->id)->count();
                
                return [
                    'id' => $device->id,
                    'name' => $device->name,
                    'ip' => $device->host,
                    'status' => $isOnline ? 'online' : 'offline',
                    'clients_online' => $clientsOnline,
                    'clients_total' => $clientsTotal,
                    'last_seen' => $device->last_sync_at ? Carbon::parse($device->last_sync_at)->diffForHumans() : 'Never',
                ];
            });

        $companyDeviceIds = MikrotikDevice::pluck('id')->all();
        $routerHealth = DB::table('router_health_logs')
            ->whereIn('mikrotik_device_id', $companyDeviceIds)
            ->select('mikrotik_device_id', DB::raw('MAX(created_at) as last_check'), 
                     DB::raw('AVG(cpu_load) as avg_cpu'), DB::raw('AVG(memory_usage) as avg_memory'))
            ->where('created_at', '>', now()->subHour())
            ->groupBy('mikrotik_device_id')
            ->get()
            ->keyBy('mikrotik_device_id');

        return response()->json([
            'timestamp' => now()->format('H:i:s'),
            'devices' => $devices,
            'router_health' => $routerHealth,
            'total_online' => $devices->where('status', 'online')->count(),
            'total_offline' => $devices->where('status', 'offline')->count(),
        ]);
    }

    public function liveAlerts()
    {
        $alerts = [];

        $criticalTickets = Ticket::where('priority', 'urgent')
            ->whereIn('status', ['open', 'in_progress'])
            ->with('client:id,name,client_id')
            ->latest()
            ->take(5)
            ->get();
        
        foreach ($criticalTickets as $ticket) {
            $alerts[] = [
                'type' => 'ticket',
                'severity' => 'critical',
                'icon' => 'fas fa-exclamation-triangle',
                'title' => 'Critical Ticket #' . $ticket->id,
                'message' => $ticket->subject,
                'client' => $ticket->client->name ?? 'Unknown',
                'time' => $ticket->created_at->diffForHumans(),
                'link' => route('admin.support.tickets.show', $ticket->id),
            ];
        }

        $companyId = auth()->user()->company_id;
        $revenueLeaks = DB::table('revenue_leakage_alerts')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('is_resolved', false)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        
        foreach ($revenueLeaks as $leak) {
            $client = Client::find($leak->client_id);
            $alerts[] = [
                'type' => 'revenue',
                'severity' => 'warning',
                'icon' => 'fas fa-money-bill-wave',
                'title' => ucwords(str_replace('_', ' ', $leak->alert_type)),
                'message' => $leak->details ?? 'Revenue leakage detected',
                'client' => $client->name ?? 'Unknown',
                'time' => Carbon::parse($leak->created_at)->diffForHumans(),
                'link' => $leak->client_id ? route('admin.clients.show', $leak->client_id) : '#',
            ];
        }

        $networkAlerts = DB::table('network_event_logs')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereIn('event_type', ['router_down', 'fiber_cut', 'area_outage'])
            ->where('is_resolved', false)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        foreach ($networkAlerts as $event) {
            $alerts[] = [
                'type' => 'network',
                'severity' => $event->severity ?? 'warning',
                'icon' => 'fas fa-network-wired',
                'title' => ucwords(str_replace('_', ' ', $event->event_type)),
                'message' => $event->description ?? 'Network event detected',
                'client' => null,
                'time' => Carbon::parse($event->created_at)->diffForHumans(),
                'link' => route('admin.network.monitoring'),
            ];
        }

        usort($alerts, function($a, $b) {
            $severityOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];
            return ($severityOrder[$a['severity']] ?? 2) <=> ($severityOrder[$b['severity']] ?? 2);
        });

        return response()->json([
            'timestamp' => now()->format('H:i:s'),
            'alerts' => array_slice($alerts, 0, 10),
            'total_critical' => count(array_filter($alerts, fn($a) => $a['severity'] === 'critical')),
            'total_warnings' => count(array_filter($alerts, fn($a) => $a['severity'] === 'warning')),
        ]);
    }

    public function livePayments()
    {
        $recentPayments = Payment::with(['client:id,name,client_id', 'collectedBy:id,name'])
            ->whereDate('payment_date', today())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function($payment) {
                $collector = $payment->collectedBy;
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'amount_formatted' => '৳' . number_format($payment->amount, 0),
                    'client_name' => $payment->client->name ?? 'Unknown',
                    'client_id' => $payment->client->client_id ?? '',
                    'method' => ucfirst($payment->payment_method ?? 'cash'),
                    'time' => $payment->created_at->diffForHumans(),
                    'trx_id' => $payment->transaction_id,
                    'submit_date' => $payment->created_at->format('d-m-Y'),
                    'submit_time' => $payment->created_at->format('h:i:s A'),
                    'submit_month' => $payment->created_at->format('F'),
                    'submit_year' => $payment->created_at->format('Y'),
                    'submit_hour' => $payment->created_at->format('H'),
                    'submit_day' => $payment->created_at->format('d'),
                    'submitted_by' => $payment->collector_name ?? ($collector ? $collector->name : 'Admin'),
                    'submitted_by_type' => $payment->submitted_by_type ?? ($collector ? 'Staff' : 'Admin'),
                    'received_by' => $payment->receiver_name ?? 'Office',
                    'received_at' => $payment->received_at ? Carbon::parse($payment->received_at)->format('d-m-y h:i:s A') : null,
                    'submitted_at_formatted' => $payment->submitted_at 
                        ? Carbon::parse($payment->submitted_at)->format('d-m-y h:i:s A') . ' (BDT)' 
                        : $payment->created_at->format('d-m-y h:i:s A') . ' (BDT)',
                ];
            });

        $hourlyCollection = [];
        for ($h = 0; $h <= now()->hour; $h++) {
            $start = today()->setHour($h);
            $end = today()->setHour($h)->endOfHour();
            $hourlyCollection[] = [
                'hour' => sprintf('%02d:00', $h),
                'amount' => Payment::whereDate('payment_date', today())
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('amount'),
            ];
        }

        return response()->json([
            'timestamp' => now()->format('H:i:s'),
            'recent_payments' => $recentPayments,
            'hourly_collection' => $hourlyCollection,
            'today_total' => Payment::whereDate('payment_date', today())->sum('amount'),
            'today_count' => Payment::whereDate('payment_date', today())->count(),
        ]);
    }

    public function liveIspHealth()
    {
        $health = $this->dashboardService->getIspHealthScore();
        $areaHealth = $this->dashboardService->getAreaHealthScores();
        $churnRisk = $this->dashboardService->getChurnRiskClients();

        return response()->json([
            'timestamp' => now()->format('H:i:s'),
            'overall' => $health,
            'areas' => array_slice($areaHealth, 0, 10),
            'churn_risk' => array_slice($churnRisk, 0, 5),
        ]);
    }

    public function notifications()
    {
        try {
            $notifications = [];
            
            $criticalTickets = Ticket::where('priority', 'urgent')
                ->whereIn('status', ['open', 'in_progress'])
                ->count();
                
            $pendingPayments = Bill::where('status', 'unpaid')
                ->where('due_date', '<', now())
                ->count();
                
            $newApplications = \App\Models\NewConnectionApplication::whereIn('status', ['submitted', 'under_review'])->count();
            
            $todayNewClients = Client::whereDate('created_at', today())->count();
            
            if ($criticalTickets > 0) {
                $notifications[] = [
                    'type' => 'urgent',
                    'icon' => 'fas fa-exclamation-triangle',
                    'title' => "$criticalTickets Critical Ticket(s)",
                    'message' => 'Requires immediate attention',
                    'time' => 'now',
                    'link' => '/admin/support/tickets?priority=urgent'
                ];
            }
            
            if ($pendingPayments > 0) {
                $notifications[] = [
                    'type' => 'warning',
                    'icon' => 'fas fa-money-bill-wave',
                    'title' => "$pendingPayments Overdue Bill(s)",
                    'message' => 'Bills past due date',
                    'time' => 'now',
                    'link' => '/admin/billing/due'
                ];
            }
            
            if ($newApplications > 0) {
                $notifications[] = [
                    'type' => 'info',
                    'icon' => 'fas fa-file-alt',
                    'title' => "$newApplications New Application(s)",
                    'message' => 'Pending review',
                    'time' => 'now',
                    'link' => '/admin/applications/pending'
                ];
            }
            
            if ($todayNewClients > 0) {
                $notifications[] = [
                    'type' => 'success',
                    'icon' => 'fas fa-user-plus',
                    'title' => "$todayNewClients New Client(s) Today",
                    'message' => 'Welcome new customers',
                    'time' => 'today',
                    'link' => '/admin/clients'
                ];
            }

            return response()->json([
                'timestamp' => now()->format('H:i:s'),
                'unread_count' => count($notifications),
                'notifications' => $notifications,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'timestamp' => now()->format('H:i:s'),
                'unread_count' => 0,
                'notifications' => [],
                'error' => 'Failed to load notifications'
            ]);
        }
    }

    public function liveClientActivity()
    {
        $companyId = auth()->user()->company_id;
        $recentConnections = DB::table('mikrotik_sessions')
            ->join('clients', 'mikrotik_sessions.client_id', '=', 'clients.id')
            ->where('clients.company_id', $companyId)
            ->where('mikrotik_sessions.status', 'online')
            ->orderByDesc('mikrotik_sessions.last_seen')
            ->limit(10)
            ->select('clients.name', 'clients.client_id as code', 'mikrotik_sessions.last_seen', 
                     'mikrotik_sessions.address as ip_address', 'mikrotik_sessions.caller_id as mac_address')
            ->get()
            ->map(function($session) {
                return [
                    'name' => $session->name,
                    'code' => $session->code,
                    'ip' => $session->ip_address,
                    'mac' => $session->mac_address,
                    'connected' => $session->last_seen ? Carbon::parse($session->last_seen)->diffForHumans() : 'Unknown',
                    'status' => 'online',
                ];
            });

        $recentDisconnections = DB::table('network_event_logs')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('event_type', 'session_disconnect')
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function($event) {
                $data = json_decode($event->description, true) ?? [];
                return [
                    'name' => $data['client_name'] ?? 'Unknown',
                    'code' => $data['client_code'] ?? '',
                    'reason' => $data['reason'] ?? 'Unknown',
                    'time' => Carbon::parse($event->created_at)->diffForHumans(),
                    'status' => 'offline',
                ];
            });

        return response()->json([
            'timestamp' => now()->format('H:i:s'),
            'recent_connections' => $recentConnections,
            'recent_disconnections' => $recentDisconnections,
            'online_count' => Client::where('connection_status', 'online')->where('status', '!=', 'deleted')->count(),
            'offline_count' => Client::where('status', 'active')->where('connection_status', 'offline')->count(),
        ]);
    }

    public function chartData()
    {
        $now = now();
        $startDate = $now->copy()->subDays(29)->startOfDay();
        
        $clientsByDate = Client::where('created_at', '>=', $startDate)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->groupByRaw("DATE(created_at)")
            ->pluck('count', 'date')
            ->toArray();
        
        $last30DaysClients = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $last30DaysClients[] = [
                'date' => $date->format('d M'),
                'count' => $clientsByDate[$dateKey] ?? 0
            ];
        }

        $collectionByDate = Payment::where('payment_date', '>=', $startDate)
            ->selectRaw("DATE(payment_date) as date, SUM(amount) as amount")
            ->groupByRaw("DATE(payment_date)")
            ->pluck('amount', 'date')
            ->toArray();
        
        $last30DaysCollection = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $last30DaysCollection[] = [
                'date' => $date->format('d M'),
                'amount' => $collectionByDate[$dateKey] ?? 0
            ];
        }

        $inactiveByDate = Client::where('status', 'inactive')
            ->where('last_inactive_date', '>=', $startDate)
            ->selectRaw("DATE(last_inactive_date) as date, COUNT(*) as count")
            ->groupByRaw("DATE(last_inactive_date)")
            ->pluck('count', 'date')
            ->toArray();
        
        $last30DaysAutoInactive = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $last30DaysAutoInactive[] = [
                'date' => $date->format('d M'),
                'count' => $inactiveByDate[$dateKey] ?? 0
            ];
        }

        // Last 30 days Left clients
        $leftByDate = Client::withoutGlobalScope('exclude_deleted')->whereIn('status', ['left', 'deleted'])
            ->where('updated_at', '>=', $startDate)
            ->selectRaw("DATE(updated_at) as date, COUNT(*) as count")
            ->groupByRaw("DATE(updated_at)")
            ->pluck('count', 'date')
            ->toArray();

        $last30DaysLeftClients = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $last30DaysLeftClients[] = [
                'date' => $date->format('d M'),
                'count' => $leftByDate[$dateKey] ?? 0
            ];
        }

        $last180DaysData = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();
            $last180DaysData[] = [
                'month' => $monthStart->format('M Y'),
                'collection' => Payment::whereBetween('payment_date', [$monthStart, $monthEnd])->sum('amount'),
                'discount' => Bill::whereBetween('created_at', [$monthStart, $monthEnd])->sum('discount')
            ];
        }

        $dueBillsByMonth = Bill::where('status', '!=', 'paid')
            ->selectRaw('billing_month, billing_year, SUM(due_amount) as total_due')
            ->groupBy('billing_month', 'billing_year')
            ->orderByRaw('billing_year DESC, billing_month DESC')
            ->limit(6)
            ->get()
            ->map(function($item) {
                $monthName = Carbon::createFromDate($item->billing_year, $item->billing_month, 1)->format('M Y');
                return [
                    'month' => $monthName,
                    'due' => $item->total_due
                ];
            });

        $resellerExpenseByMonth = [];
        try {
            $transactions = DB::table('reseller_transactions')
                ->where('company_id', auth()->user()->company_id)
                ->whereIn('type', ['commission', 'expense'])
                ->where('created_at', '>=', $now->copy()->subMonths(6)->startOfMonth())
                ->select('created_at', 'amount')
                ->get();
            
            $grouped = $transactions->groupBy(function($item) {
                return Carbon::parse($item->created_at)->format('M Y');
            })->map(function($items) {
                return $items->sum('amount');
            })->take(6);
            
            foreach ($grouped as $month => $total) {
                $resellerExpenseByMonth[] = ['month' => $month, 'total' => $total];
            }
        } catch (\Exception $e) {
            $resellerExpenseByMonth = [];
        }

        return response()->json([
            'timestamp' => now()->format('H:i:s'),
            'last_30_days_clients' => $last30DaysClients,
            'last_30_days_collection' => $last30DaysCollection,
            'last_30_days_auto_inactive' => $last30DaysAutoInactive,
            'last_30_days_left_clients' => $last30DaysLeftClients,
            'last_180_days_collection_vs_discount' => $last180DaysData,
            'due_bills_by_month' => $dueBillsByMonth,
            'reseller_expense_by_month' => $resellerExpenseByMonth,
        ]);
    }

    public function liveMikrotikCount()
    {
        $totalOnline = 0;
        $deviceStats = [];

        try {
            $devices = MikrotikDevice::where('is_active', true)->get();
            
            foreach ($devices as $device) {
                $onlineCount = Client::where('mikrotik_device_id', $device->id)
                    ->where('connection_status', 'online')
                    ->count();
                
                $totalOnline += $onlineCount;
                $deviceStats[] = [
                    'id' => $device->id,
                    'name' => $device->name,
                    'online' => $onlineCount,
                ];
            }
        } catch (\Throwable $e) {}

        $data = [
            'total_online' => $totalOnline,
            'devices' => $deviceStats,
        ];

        try {
            $companyId = auth()->user()->company_id ?? null;
            \App\Models\OnlineClientHistory::create([
                'company_id' => $companyId,
                'online_count' => $data['total_online'],
                'recorded_at' => now(),
            ]);
            \App\Models\OnlineClientHistory::where('recorded_at', '<', now()->startOfDay()->subDays(3))->delete();
        } catch (\Throwable $e) {}

        return response()->json([
            'timestamp' => now()->format('H:i:s'),
            'total_online' => $data['total_online'],
            'devices' => $data['devices'],
        ]);
    }

    public function onlineClientHistory()
    {
        $companyId = auth()->user()->company_id ?? null;

        $threeDaysAgo = now()->startOfDay()->subDays(3);

        \App\Models\OnlineClientHistory::where('recorded_at', '<', $threeDaysAgo)->delete();

        $records = \App\Models\OnlineClientHistory::where('company_id', $companyId)
            ->where('recorded_at', '>=', $threeDaysAgo)
            ->orderBy('recorded_at')
            ->get(['online_count', 'recorded_at']);

        $labels = [];
        $data = [];
        foreach ($records as $r) {
            $labels[] = $r->recorded_at->format('d-m H:i');
            $data[] = $r->online_count;
        }

        $dailyData = [];
        foreach ($records as $r) {
            $dayKey = $r->recorded_at->format('Y-m-d');
            if (!isset($dailyData[$dayKey])) {
                $dailyData[$dayKey] = [
                    'peak' => $r->online_count,
                    'min' => $r->online_count,
                    'sum' => $r->online_count,
                    'count' => 1,
                    'day_name' => $r->recorded_at->format('l'),
                    'date' => $r->recorded_at->format('d-m'),
                ];
            } else {
                $dailyData[$dayKey]['sum'] += $r->online_count;
                $dailyData[$dayKey]['count']++;
                if ($r->online_count > $dailyData[$dayKey]['peak']) {
                    $dailyData[$dayKey]['peak'] = $r->online_count;
                }
                if ($r->online_count < $dailyData[$dayKey]['min']) {
                    $dailyData[$dayKey]['min'] = $r->online_count;
                }
            }
        }

        $days = [];
        $totalOnline = 0;
        $i = 1;
        foreach ($dailyData as $info) {
            $days[] = [
                'label' => 'Day ' . $i,
                'day_name' => $info['day_name'],
                'date' => $info['date'],
                'peak' => $info['peak'],
                'avg' => $info['count'] > 0 ? round($info['sum'] / $info['count']) : 0,
                'min' => $info['min'],
                'points' => $info['count'],
            ];
            $totalOnline += $info['peak'];
            $i++;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'days' => $days,
            'total_online' => $totalOnline,
        ]);
    }

    public function appUpdateNews()
    {
        $news = [
            [
                'version' => '2.5.0',
                'date' => '2026-02-01',
                'title' => 'Enhanced Dashboard',
                'description' => 'New comprehensive dashboard with 23+ features including graphs, charts, and live statistics.',
                'type' => 'feature'
            ],
            [
                'version' => '2.4.5',
                'date' => '2026-01-15',
                'title' => 'Reseller Module Update',
                'description' => 'Full CRUD operations for reseller panel with improved security.',
                'type' => 'improvement'
            ],
        ];

        return response()->json([
            'timestamp' => now()->format('H:i:s'),
            'news' => $news,
            'latest_version' => '2.5.0',
        ]);
    }

    public function zonesByThana($upazilaId = null)
    {
        $companyId = auth()->user()->company_id;
        $query = \DB::table('zones')
            ->leftJoin('clients', function($j) {
                $j->on('clients.zone_id', '=', 'zones.id')
                  ->whereNull('clients.reseller_id')
                  ->whereIn('clients.status', ['active', 'inactive', 'left']);
            })
            ->where('zones.company_id', $companyId)
            ->whereNull('zones.reseller_id')
            ->select('zones.id', 'zones.name')
            ->selectRaw("COALESCE(SUM(CASE WHEN clients.status='active' THEN 1 ELSE 0 END),0) as active_c")
            ->selectRaw("COALESCE(SUM(CASE WHEN clients.status='inactive' THEN 1 ELSE 0 END),0) as inactive_c")
            ->selectRaw("COALESCE(SUM(CASE WHEN clients.status='left' THEN 1 ELSE 0 END),0) as left_c")
            ->groupBy('zones.id', 'zones.name')
            ->orderByRaw("COALESCE(SUM(CASE WHEN clients.status='active' THEN 1 ELSE 0 END),0) DESC");
        if ($upazilaId === null || $upazilaId === 'null' || $upazilaId === '0') {
            $query->whereNull('zones.upazila_id');
        } else {
            $query->where('zones.upazila_id', (int)$upazilaId);
        }
        $zones = $query->orderBy('zones.name')->get()->values();
        return response()->json(['zones' => $zones]);
    }

}
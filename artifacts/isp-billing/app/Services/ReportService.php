<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Zone;
use App\Models\User;
use App\Models\Reseller;
use App\Models\Product;
use App\Models\AdvancePayment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    public function getClientLedgerReport(int $clientId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $client = Client::findOrFail($clientId);
        $billingService = new BillingService();
        return $billingService->getClientLedger($client);
    }

    public function getEmployeeCollectionReport(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Payment::select(
            'users.name as employee_name',
            DB::raw('COUNT(*) as payment_count'),
            DB::raw('SUM(payments.amount) as total_amount')
        )
        ->join('users', 'payments.collected_by', '=', 'users.id')
        ->groupBy('users.id', 'users.name');
        
        if ($startDate) {
            $query->whereDate('payment_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('payment_date', '<=', $endDate);
        }
        
        return $query->get()->toArray();
    }

    public function getZoneRevenueReport(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Payment::select(
            'zones.name as zone_name',
            DB::raw('COUNT(DISTINCT clients.id) as client_count'),
            DB::raw('SUM(payments.amount) as total_revenue')
        )
        ->join('clients', 'payments.client_id', '=', 'clients.id')
        ->join('zones', 'clients.zone_id', '=', 'zones.id')
        ->groupBy('zones.id', 'zones.name');
        
        if ($startDate) {
            $query->whereDate('payment_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('payment_date', '<=', $endDate);
        }
        
        return $query->get()->toArray();
    }

    public function getResellerReport(?int $resellerId = null): array
    {
        $query = Reseller::with(['clients', 'packages'])
            ->withCount('clients');
        
        if ($resellerId) {
            $query->where('id', $resellerId);
        }
        
        return $query->get()->map(function ($reseller) {
            $totalRevenue = Payment::whereIn('client_id', $reseller->clients->pluck('id'))
                ->sum('amount');
            
            return [
                'reseller' => $reseller->name,
                'company' => $reseller->company_name,
                'client_count' => $reseller->clients_count,
                'wallet_balance' => $reseller->wallet_balance,
                'total_revenue' => $totalRevenue
            ];
        })->toArray();
    }

    public function getStockReport(): array
    {
        $products = Product::with('category')
            ->select('products.*')
            ->withCount([
                'serials as serial_in_stock'  => fn($q) => $q->where('status', 'in_stock'),
                'serials as serial_assigned'   => fn($q) => $q->where('status', 'assigned'),
                'serials as serial_damaged'    => fn($q) => $q->where('status', 'damaged'),
            ])
            ->get();

        return $products->map(function ($p) {
            $arr = $p->toArray();
            if ($p->has_serial) {
                $arr['in_stock_count'] = $p->serial_in_stock  ?? 0;
                $arr['assigned_count'] = $p->serial_assigned  ?? 0;
                $arr['damaged_count']  = $p->serial_damaged   ?? 0;
            } else {
                $arr['in_stock_count'] = $p->stock_quantity ?? 0;
                $arr['assigned_count'] = 0;
                $arr['damaged_count']  = 0;
            }
            return $arr;
        })->toArray();
    }

    public function getProfitLossStatement(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate   = $endDate   ?? now()->endOfMonth();

        // ── REVENUE ──────────────────────────────────────────────────────────
        // Direct ISP client bill collection
        $directClientCollection = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->whereHas('client', fn($q) => $q->whereNull('reseller_id'))
            ->sum('amount');

        // MAC reseller client collections excluded — that money belongs to the reseller, not ISP.
        $macClientCollection = 0;

        $companyId = auth()->user()->company_id;

        // MAC Reseller monthly bills paid to ISP
        $macResellerBill = \App\Models\ResellerBill::where('company_id', $companyId)
            ->whereHas('reseller', fn($q) => $q->where('type', 'mac'))
            ->whereIn('status', ['paid', 'partial'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_paid');

        // Bandwidth Reseller bills paid to ISP
        $bwResellerBill = \App\Models\ResellerBill::where('company_id', $companyId)
            ->whereHas('reseller', fn($q) => $q->where('type', 'bandwidth'))
            ->whereIn('status', ['paid', 'partial'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_paid');

        // Installation charges paid (scoped via client's TenantAware)
        $installationIncome = \App\Models\ClientServiceCharge::where('charge_type', 'Installation Charge')
            ->whereNotNull('paid_by')->where('amount', '>', 0)
            ->whereHas('client')
            ->whereBetween('charge_date', [$startDate, $endDate])
            ->sum('amount');

        // Product / equipment sales
        $productSales = \App\Models\Sale::whereBetween('sale_date', [$startDate, $endDate])->sum('paid');

        // Other income entries
        $otherIncome = Income::whereBetween('income_date', [$startDate, $endDate])->sum('amount');

        // Other income by head (for detail breakdown)
        $otherIncomeByHead = Income::select('income_heads.name', DB::raw('SUM(incomes.amount) as total'))
            ->join('income_heads', 'incomes.income_head_id', '=', 'income_heads.id')
            ->whereBetween('income_date', [$startDate, $endDate])
            ->groupBy('income_heads.id', 'income_heads.name')
            ->pluck('total', 'name')->toArray();

        $totalRevenue = $directClientCollection + $macClientCollection
            + $macResellerBill + $bwResellerBill
            + $installationIncome + $productSales + $otherIncome;

        // ── EXPENSES ─────────────────────────────────────────────────────────
        $bwBillHeadId   = \App\Models\ExpenseHead::where('name', 'Bandwidth Bill')->value('id');
        $bwBillExpense  = $bwBillHeadId
            ? Expense::where('expense_head_id', $bwBillHeadId)->whereBetween('expense_date', [$startDate, $endDate])->sum('amount')
            : 0;

        $officeExpense = Expense::when($bwBillHeadId, fn($q) => $q->where('expense_head_id', '!=', $bwBillHeadId))
            ->whereBetween('expense_date', [$startDate, $endDate])->sum('amount');

        $vendorPayment = \App\Models\VendorLedger::where('type', 'payment')
            ->whereBetween('transaction_date', [$startDate, $endDate])->sum('debit');

        $agentCommission = \App\Models\AgentCommission::where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate])->sum('commission_amount');

        $loanPayment = \App\Models\LoanPayment::whereHas('loan', fn($q) => $q->where('type', 'given')
                ->whereHas('creator', fn($q2) => $q2->where('company_id', $companyId)))
            ->whereBetween('payment_date', [$startDate, $endDate])->sum('amount');

        // Office expense by head (excluding Bandwidth Bill — it has its own line)
        $expenseByHead = Expense::select('expense_heads.name', DB::raw('SUM(expenses.amount) as total'))
            ->join('expense_heads', 'expenses.expense_head_id', '=', 'expense_heads.id')
            ->when($bwBillHeadId, fn($q) => $q->where('expenses.expense_head_id', '!=', $bwBillHeadId))
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->groupBy('expense_heads.id', 'expense_heads.name')
            ->pluck('total', 'name')->toArray();

        $totalExpenses = $officeExpense + $bwBillExpense + $vendorPayment + $agentCommission + $loanPayment;

        $netProfit = $totalRevenue - $totalExpenses;

        return [
            'period' => [
                'start' => $startDate->format('d-M-Y'),
                'end'   => $endDate->format('d-M-Y'),
            ],
            // Detailed revenue breakdown
            'revenue' => [
                'direct_client'    => $directClientCollection,
                'mac_client'       => $macClientCollection,
                'mac_reseller'     => $macResellerBill,
                'bw_reseller'      => $bwResellerBill,
                'installation'     => $installationIncome,
                'product_sales'    => $productSales,
                'other'            => $otherIncome,
                'other_by_head'    => $otherIncomeByHead,
            ],
            // Detailed expense breakdown
            'expenses' => [
                'office'           => $officeExpense,
                'bw_bill'          => $bwBillExpense,
                'vendor'           => $vendorPayment,
                'agent_commission' => $agentCommission,
                'loan_payment'     => $loanPayment,
                'by_head'          => $expenseByHead,
            ],
            'total_revenue'  => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_profit'     => $netProfit,
        ];
    }

    public function getBtrcFormatReport(): array
    {
        return Client::where('status', 'active')
            ->with(['zone', 'package'])
            ->get()
            ->map(function ($client, $index) {
                return [
                    'sl' => $index + 1,
                    'client_id' => $client->client_id,
                    'name' => $client->name,
                    'address' => $client->address,
                    'nid' => $client->nid,
                    'phone' => $client->phone,
                    'package' => $client->package?->name,
                    'bandwidth' => $client->package?->download_speed . ' Mbps',
                    'connection_date' => $client->connection_date?->format('Y-m-d'),
                    'zone' => $client->zone?->name,
                    'mac_address' => $client->mac_address,
                    'ip_address' => $client->ip_address
                ];
            })
            ->toArray();
    }

    public function getTechnicianLeakageReport(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();
        $companyId = auth()->user()->company_id;

        $technicians = User::whereIn('user_type', ['technician', 'billman'])
            ->where('company_id', $companyId)
            ->with('employee')
            ->get();

        return $technicians->map(function ($tech) use ($startDate, $endDate, $companyId) {
            $collected = Payment::where('collected_by', $tech->id)
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount');

            $submitted = DB::table('staff_submissions')
                ->where('staff_user_id', $tech->id)
                ->where('company_id', $companyId)
                ->whereBetween('submission_date', [$startDate, $endDate])
                ->sum('amount');

            $staffDue = DB::table('staff_dues')
                ->where('user_id', $tech->id)
                ->where('company_id', $companyId)
                ->value('current_due') ?? 0;

            $zoneIds = $tech->employee?->zone_ids ?? [];
            $expectedCollection = 0;
            if (!empty($zoneIds) && is_array($zoneIds)) {
                $expectedCollection = Client::whereIn('zone_id', $zoneIds)
                    ->where('status', 'active')
                    ->sum('monthly_fee');
            }

            return [
                'technician_id' => $tech->id,
                'technician_name' => $tech->name,
                'role' => $tech->user_type,
                'zones' => $tech->employee?->zone_ids ?? [],
                'total_collected' => $collected,
                'total_submitted' => $submitted,
                'outstanding' => $staffDue,
                'leakage' => $collected - $submitted - $staffDue,
                'expected_collection' => $expectedCollection,
                'collection_rate' => $expectedCollection > 0 ? round(($collected / $expectedCollection) * 100, 2) : 0,
            ];
        })->sortByDesc('leakage')->values()->toArray();
    }

    public function getAgingAnalysisReport(): array
    {
        $today = now();

        $brackets = [
            '0-30 days' => ['min' => 0, 'max' => 30],
            '31-60 days' => ['min' => 31, 'max' => 60],
            '61-90 days' => ['min' => 61, 'max' => 90],
            '91-180 days' => ['min' => 91, 'max' => 180],
            '180+ days' => ['min' => 181, 'max' => 9999],
        ];

        $analysis = [];

        foreach ($brackets as $bracket => $range) {
            $startDate = $today->copy()->subDays($range['max']);
            $endDate = $today->copy()->subDays($range['min']);

            $bills = Bill::where('status', 'unpaid')
                ->whereBetween('due_date', [$startDate, $endDate])
                ->with('client')
                ->get();

            $totalDue = $bills->sum(function ($bill) {
                return max(0, $bill->amount - ($bill->paid_amount ?? 0));
            });

            $clientIds = $bills->pluck('client_id')->unique();

            $analysis[$bracket] = [
                'bill_count' => $bills->count(),
                'client_count' => $clientIds->count(),
                'total_due' => $totalDue,
                'bills' => $bills->take(10)->map(fn($b) => [
                    'id' => $b->id,
                    'bill_no' => $b->bill_no,
                    'client_id' => $b->client?->client_id,
                    'client_name' => $b->client?->name,
                    'amount' => $b->amount,
                    'paid' => $b->paid_amount ?? 0,
                    'remaining' => max(0, $b->amount - ($b->paid_amount ?? 0)),
                    'due_date' => $b->due_date?->format('Y-m-d'),
                ])->toArray(),
            ];
        }

        return $analysis;
    }

    public function getAdvanceVsDueReport(): array
    {
        $zones = Zone::all();

        $report = [];
        foreach ($zones as $zone) {
            $zoneClients = Client::where('zone_id', $zone->id)->get();

            $totalDue = $zoneClients->sum('due_amount');
            $totalAdvance = $zoneClients->sum('balance');
            $activeCount = $zoneClients->where('status', 'active')->count();
            $inactiveCount = $zoneClients->where('status', 'inactive')->count();
            $dueCount = $zoneClients->where('due_amount', '>', 0)->count();

            $report[] = [
                'zone_id' => $zone->id,
                'zone_name' => $zone->name,
                'total_clients' => $zoneClients->count(),
                'active_clients' => $activeCount,
                'inactive_clients' => $inactiveCount,
                'clients_with_due' => $dueCount,
                'total_due' => $totalDue,
                'total_advance' => $totalAdvance,
                'net_position' => $totalAdvance - $totalDue,
                'collection_efficiency' => $activeCount > 0 
                    ? round((($activeCount - $dueCount) / $activeCount) * 100, 2) 
                    : 0,
            ];
        }

        $summary = [
            'total_due' => collect($report)->sum('total_due'),
            'total_advance' => collect($report)->sum('total_advance'),
            'total_clients' => collect($report)->sum('total_clients'),
            'clients_with_due' => collect($report)->sum('clients_with_due'),
            'net_position' => collect($report)->sum('net_position'),
        ];

        return [
            'zones' => $report,
            'summary' => $summary,
        ];
    }

    public function getDashboardSummary(): array
    {
        $today = now();
        $monthStart = $today->copy()->startOfMonth();

        return [
            'clients' => [
                'total' => Client::where('status', '!=', 'deleted')->count(),
                'active' => Client::where('status', 'active')->count(),
                'inactive' => Client::where('status', 'inactive')->count(),
                'pending' => Client::where('status', 'pending')->count(),
            ],
            'revenue' => [
                'today' => Payment::whereDate('payment_date', $today)->sum('amount'),
                'this_month' => Payment::whereBetween('payment_date', [$monthStart, $today])->sum('amount'),
                'last_month' => Payment::whereMonth('payment_date', $today->copy()->subMonth()->month)
                    ->whereYear('payment_date', $today->copy()->subMonth()->year)
                    ->sum('amount'),
            ],
            'dues' => [
                'total_due' => Client::sum('due_amount'),
                'total_advance' => Client::sum('balance'),
                'clients_with_due' => Client::where('due_amount', '>', 0)->count(),
            ],
            'billing' => [
                'bills_generated' => Bill::whereMonth('created_at', $today->month)
                    ->whereYear('created_at', $today->year)->count(),
                'bills_paid' => Bill::whereMonth('created_at', $today->month)
                    ->whereYear('created_at', $today->year)->where('status', 'paid')->count(),
                'bills_unpaid' => Bill::whereMonth('created_at', $today->month)
                    ->whereYear('created_at', $today->year)->where('status', 'unpaid')->count(),
            ],
        ];
    }

    public function getAdvanceLiabilityReport(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        $totalAdvanceBalance = AdvancePayment::where('remaining_amount', '>', 0)->sum('remaining_amount');

        $advanceReceived = AdvancePayment::whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $advanceUsed = AdvancePayment::whereBetween('updated_at', [$startDate, $endDate])
            ->where('used_amount', '>', 0)
            ->sum('used_amount');

        $clientsWithAdvance = Client::whereHas('advancePayments', function ($q) {
            $q->where('remaining_amount', '>', 0);
        })->with(['advancePayments' => function ($q) {
            $q->where('remaining_amount', '>', 0);
        }])->get()->map(function ($client) {
            $advanceBalance = $client->advancePayments->sum('remaining_amount');
            return [
                'client_id' => $client->client_id,
                'name' => $client->name,
                'zone' => $client->zone?->name ?? 'N/A',
                'advance_balance' => $advanceBalance,
                'due_amount' => $client->due_amount,
                'net_position' => $advanceBalance - $client->due_amount,
            ];
        })->sortByDesc('advance_balance')->values()->toArray();

        $recentTransactions = AdvancePayment::with('client')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(function ($adv) {
                return [
                    'id' => $adv->id,
                    'date' => $adv->created_at->format('d-m-Y'),
                    'client_id' => $adv->client?->client_id,
                    'client_name' => $adv->client?->name,
                    'type' => 'Advance Received',
                    'amount' => $adv->amount,
                    'used' => $adv->used_amount,
                    'remaining' => $adv->remaining_amount,
                ];
            })->toArray();

        return [
            'summary' => [
                'total_advance_balance' => $totalAdvanceBalance,
                'advance_received' => $advanceReceived,
                'advance_used' => $advanceUsed,
                'clients_with_advance' => count($clientsWithAdvance),
                'period' => [
                    'start' => $startDate->format('d-m-Y'),
                    'end' => $endDate->format('d-m-Y'),
                ],
            ],
            'clients' => $clientsWithAdvance,
            'transactions' => $recentTransactions,
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Bill;
use App\Models\Client;
use App\Models\Zone;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function daily(Request $request)
    {
        $date = $request->date ?? today()->format('Y-m-d');
        $payments = Payment::with(['client', 'paymentMethod', 'collectedBy'])
            ->whereDate('payment_date', $date)
            ->get();
        $totalCollection = $payments->sum('amount');
        return view('admin.reports.daily', compact('payments', 'date', 'totalCollection'));
    }

    public function monthly(Request $request)
    {
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');
        
        $revenue = Payment::whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->sum('amount');
            
        $dailyData = Payment::whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->selectRaw('DATE(payment_date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 12-month revenue for Polar Area chart
        $yearlyRevenue = Payment::selectRaw('EXTRACT(MONTH FROM payment_date) as month, SUM(amount) as total')
            ->whereYear('payment_date', $year)
            ->groupBy(\DB::raw('EXTRACT(MONTH FROM payment_date)'))
            ->pluck('total', 'month')
            ->toArray();
        $monthlyRevenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyRevenue[] = round($yearlyRevenue[$i] ?? 0);
        }

        return view('admin.reports.monthly', compact('revenue', 'dailyData', 'month', 'year', 'monthlyRevenue'));
    }

    public function due(Request $request)
    {
        $clients = Client::with(['zone', 'package'])
            ->whereNull('reseller_id')
            ->where('due_amount', '>', 0)
            ->orderBy('due_amount', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $totalDue = Client::whereNull('reseller_id')->sum('due_amount');
        return view('admin.reports.due', compact('clients', 'totalDue'));
    }

    public function clientLedger(Request $request)
    {
        $client = null;
        $ledger = [];
        
        if ($request->client_id) {
            $client = Client::find($request->client_id);
            $ledger = $this->reportService->getClientLedgerReport($client->id);
        }
        
        $clients = Client::orderBy('name')->get();
        return view('admin.reports.client-ledger', compact('clients', 'client', 'ledger'));
    }

    public function zone(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->endOfMonth();
        
        $report = $this->reportService->getZoneRevenueReport($startDate, $endDate);
        $zones = Zone::orderBy('name')->get();
        
        return view('admin.reports.zone', compact('report', 'zones', 'startDate', 'endDate'));
    }

    public function employeeCollection(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->endOfMonth();
        
        $report = $this->reportService->getEmployeeCollectionReport($startDate, $endDate);
        
        return view('admin.reports.employee-collection', compact('report', 'startDate', 'endDate'));
    }

    public function profitLoss(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->endOfMonth();
        
        $report = $this->reportService->getProfitLossStatement($startDate, $endDate);
        
        return view('admin.reports.profit-loss', compact('report', 'startDate', 'endDate'));
    }

    public function incomeStatement(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();

        $companyId = auth()->user()->company_id;
        $openingBalance = \App\Models\Account::where('is_active', true)->sum('opening_balance');

        // ── Direct ISP client bill collection ────────────────────────────────
        $directBillCount  = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->whereHas('client', fn($q) => $q->whereNull('reseller_id'))->count();
        $directBillAmount = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->whereHas('client', fn($q) => $q->whereNull('reseller_id'))->sum('amount');

        // MAC Reseller client collections intentionally excluded —
        // that money belongs to the reseller, not the ISP.

        // ── Installation charges (paid) ───────────────────────────────────────
        $installationChargeCollection = \App\Models\ClientServiceCharge::where('charge_type', 'Installation Charge')
            ->whereNotNull('paid_by')->where('amount', '>', 0)
            ->whereHas('client')
            ->whereBetween('charge_date', [$startDate, $endDate])->count();
        $installationChargeAmount = \App\Models\ClientServiceCharge::where('charge_type', 'Installation Charge')
            ->whereNotNull('paid_by')->where('amount', '>', 0)
            ->whereHas('client')
            ->whereBetween('charge_date', [$startDate, $endDate])->sum('amount');

        // ── MAC Reseller bill payments to ISP ────────────────────────────────
        $macResellerBillCount  = \App\Models\ResellerBill::where('company_id', $companyId)
            ->whereHas('reseller', fn($q) => $q->where('type', 'mac'))
            ->whereIn('status', ['paid', 'partial'])
            ->whereBetween('created_at', [$startDate, $endDate])->count();
        $macResellerBillAmount = \App\Models\ResellerBill::where('company_id', $companyId)
            ->whereHas('reseller', fn($q) => $q->where('type', 'mac'))
            ->whereIn('status', ['paid', 'partial'])
            ->whereBetween('created_at', [$startDate, $endDate])->sum('total_paid');

        // ── Bandwidth Reseller bill payments to ISP ──────────────────────────
        $bwResellerBillCount  = \App\Models\ResellerBill::where('company_id', $companyId)
            ->whereHas('reseller', fn($q) => $q->where('type', 'bandwidth'))
            ->whereIn('status', ['paid', 'partial'])
            ->whereBetween('created_at', [$startDate, $endDate])->count();
        $bwResellerBillAmount = \App\Models\ResellerBill::where('company_id', $companyId)
            ->whereHas('reseller', fn($q) => $q->where('type', 'bandwidth'))
            ->whereIn('status', ['paid', 'partial'])
            ->whereBetween('created_at', [$startDate, $endDate])->sum('total_paid');

        // ── Other income ─────────────────────────────────────────────────────
        $othersIncome       = \App\Models\Income::whereBetween('income_date', [$startDate, $endDate])->count();
        $othersIncomeAmount = \App\Models\Income::whereBetween('income_date', [$startDate, $endDate])->sum('amount');

        // ── Product Sales ────────────────────────────────────────────────────
        $productSalesCount  = \App\Models\Sale::whereBetween('sale_date', [$startDate, $endDate])->count();
        $productSalesAmount = \App\Models\Sale::whereBetween('sale_date', [$startDate, $endDate])->sum('paid');

        // ── Loan Received ────────────────────────────────────────────────────
        $loanReceived       = \App\Models\Loan::where('type', 'received')
            ->whereHas('creator', fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('loan_date', [$startDate, $endDate])->count();
        $loanReceivedAmount = \App\Models\Loan::where('type', 'received')
            ->whereHas('creator', fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('loan_date', [$startDate, $endDate])->sum('amount');

        $periodIncome = $directBillAmount + $installationChargeAmount
            + $macResellerBillAmount + $bwResellerBillAmount
            + $othersIncomeAmount + $productSalesAmount + $loanReceivedAmount;
        $totalIncome = $openingBalance + $periodIncome;

        // ── Expenses ─────────────────────────────────────────────────────────
        $loanPaymentCount  = \App\Models\LoanPayment::whereHas('loan', fn($q) => $q->where('type', 'given')
                ->whereHas('creator', fn($q2) => $q2->where('company_id', $companyId)))
            ->whereBetween('payment_date', [$startDate, $endDate])->count();
        $loanPaymentAmount = \App\Models\LoanPayment::whereHas('loan', fn($q) => $q->where('type', 'given')
                ->whereHas('creator', fn($q2) => $q2->where('company_id', $companyId)))
            ->whereBetween('payment_date', [$startDate, $endDate])->sum('amount');

        $bwBillExpenseHeadId = \App\Models\ExpenseHead::where('name', 'Bandwidth Bill')->value('id');
        $bwBillExpenseCount  = $bwBillExpenseHeadId
            ? \App\Models\Expense::where('expense_head_id', $bwBillExpenseHeadId)->whereBetween('expense_date', [$startDate, $endDate])->count()
            : 0;
        $bwBillExpenseAmount = $bwBillExpenseHeadId
            ? \App\Models\Expense::where('expense_head_id', $bwBillExpenseHeadId)->whereBetween('expense_date', [$startDate, $endDate])->sum('amount')
            : 0;

        $officeExpenseCount  = \App\Models\Expense::when($bwBillExpenseHeadId, fn($q) => $q->where('expense_head_id', '!=', $bwBillExpenseHeadId))->whereBetween('expense_date', [$startDate, $endDate])->count();
        $officeExpenseAmount = \App\Models\Expense::when($bwBillExpenseHeadId, fn($q) => $q->where('expense_head_id', '!=', $bwBillExpenseHeadId))->whereBetween('expense_date', [$startDate, $endDate])->sum('amount');

        $vendorPayCount  = \App\Models\VendorLedger::where('type', 'payment')->whereBetween('created_at', [$startDate, $endDate])->count();
        $vendorPayAmount = \App\Models\VendorLedger::where('type', 'payment')->whereBetween('created_at', [$startDate, $endDate])->sum('debit');

        $agentCommissionCount  = \App\Models\AgentCommission::where('company_id', $companyId)->whereBetween('date', [$startDate, $endDate])->count();
        $agentCommissionAmount = \App\Models\AgentCommission::where('company_id', $companyId)->whereBetween('date', [$startDate, $endDate])->sum('commission_amount');

        $totalExpense    = $loanPaymentAmount + $officeExpenseAmount + $bwBillExpenseAmount + $vendorPayAmount + $agentCommissionAmount;
        $closingBalance  = $totalIncome - $totalExpense;

        $incomeItems = [
            ['name' => 'Direct Client Bill Collection',              'object' => $directBillCount,              'amount' => $directBillAmount,          'view_route' => route('admin.payments.index'),                'note' => 'ISP own clients'],
            ['name' => 'Installation Charge (Paid)',                 'object' => $installationChargeCollection, 'amount' => $installationChargeAmount,  'view_route' => route('admin.sales.installation-charges'),    'note' => ''],
            ['name' => 'MAC Reseller Monthly Bill (Collected)',      'object' => $macResellerBillCount,         'amount' => $macResellerBillAmount,      'view_route' => route('admin.reseller.index'),                'note' => 'Subscription-based resellers'],
            ['name' => 'Bandwidth Reseller Bill (Collected)',        'object' => $bwResellerBillCount,          'amount' => $bwResellerBillAmount,       'view_route' => route('admin.reseller.index'),                'note' => 'Wholesale bandwidth buyers'],
            ['name' => 'Others Income',                              'object' => $othersIncome,                 'amount' => $othersIncomeAmount,         'view_route' => route('admin.accounts.income'),               'note' => ''],
            ['name' => 'Product Sales',                              'object' => $productSalesCount,            'amount' => $productSalesAmount,         'view_route' => route('admin.sales.index'),                   'note' => ''],
            ['name' => 'Loan Received',                              'object' => $loanReceived,                 'amount' => $loanReceivedAmount,         'view_route' => route('admin.accounts.loan'),                 'note' => ''],
        ];

        $expenseItems = [
            ['name' => 'Office Expense',                  'category' => 'Operating', 'object' => $officeExpenseCount,     'amount' => $officeExpenseAmount,     'view_route' => route('admin.accounts.expense')],
            ['name' => 'Bandwidth Bill',                  'category' => 'Operating', 'object' => $bwBillExpenseCount,     'amount' => $bwBillExpenseAmount,     'view_route' => route('admin.accounts.expense')],
            ['name' => 'Vendor Bill Payment',             'category' => 'Operating', 'object' => $vendorPayCount,         'amount' => $vendorPayAmount,         'view_route' => route('admin.accounts.vendor-ledger')],
            ['name' => 'Referral Partner Commission',     'category' => 'Operating', 'object' => $agentCommissionCount,   'amount' => $agentCommissionAmount,   'view_route' => route('admin.agent.ledgers')],
            ['name' => 'Loan Payment (Given)',            'category' => 'Financial', 'object' => $loanPaymentCount,       'amount' => $loanPaymentAmount,       'view_route' => route('admin.accounts.loan')],
        ];

        $summary = [
            'opening_balance'           => $openingBalance,
            'direct_bill_collection'    => $directBillAmount,
            'installation_charge'       => $installationChargeAmount,
            'mac_reseller_bill'         => $macResellerBillAmount,
            'bw_reseller_bill'          => $bwResellerBillAmount,
            'others_income'             => $othersIncomeAmount,
            'product_sales'             => $productSalesAmount,
            'loan_received'             => $loanReceivedAmount,
            'office_expense'            => $officeExpenseAmount,
            'bw_bill_expense'           => $bwBillExpenseAmount,
            'vendor_bill_pay'           => $vendorPayAmount,
            'agent_commission'          => $agentCommissionAmount,
            'loan_payment'              => $loanPaymentAmount,
        ];

        return view('admin.reports.income-statement', compact(
            'startDate', 'endDate', 'openingBalance',
            'incomeItems', 'expenseItems', 'summary',
            'periodIncome', 'totalIncome', 'totalExpense', 'closingBalance'
        ));
    }

    public function btrcFormat()
    {
        $report = $this->reportService->getBtrcFormatReport();
        return view('admin.reports.btrc-format', compact('report'));
    }

    public function stock()
    {
        $inventoryReport = \App\Models\Instrument::selectRaw("
            category,
            sub_category,
            SUM(COALESCE(quantity, 1))                                                     AS total,
            SUM(CASE WHEN status = 'available' THEN COALESCE(quantity, 1) ELSE 0 END)     AS available_count,
            SUM(CASE WHEN status = 'in_use'    THEN COALESCE(quantity, 1) ELSE 0 END)     AS in_use_count,
            SUM(CASE WHEN status = 'damaged'   THEN COALESCE(quantity, 1) ELSE 0 END)     AS damaged_count
        ")
        ->groupBy('category', 'sub_category')
        ->orderBy('category')
        ->orderBy('sub_category')
        ->get();
        return view('admin.reports.stock', compact('inventoryReport'));
    }

    public function resellerReport(Request $request)
    {
        $resellerId   = $request->reseller_id;
        $resellerType = $request->type; // 'mac' or 'bandwidth' or null

        $resellers = \App\Models\Reseller::orderBy('name')->get();

        // Build per-reseller stats
        $query = \App\Models\Reseller::with(['clients', 'bills'])->orderBy('type')->orderBy('name');
        if ($resellerId) $query->where('id', $resellerId);
        if ($resellerType) $query->where('type', $resellerType);

        $report = $query->get()->map(function ($reseller) {
            $clientCollection = \App\Models\Payment::whereHas('client', fn($q) => $q->where('reseller_id', $reseller->id))->sum('amount');
            $billTotal   = $reseller->bills->sum('total_recharge');
            $billPaid    = $reseller->bills->sum('total_paid');
            $billDue     = $reseller->bills->sum('due_amount');
            $billCount   = $reseller->bills->count();
            $unpaidBills = $reseller->bills->where('status', 'unpaid')->count() + $reseller->bills->where('status', 'partial')->count();

            return [
                'id'                 => $reseller->id,
                'type'               => $reseller->type,
                'reseller'           => $reseller->name,
                'company'            => $reseller->company_name,
                'phone'              => $reseller->phone,
                'wallet_balance'     => $reseller->wallet_balance,
                'client_count'       => $reseller->clients->count(),
                // MAC: client payments go through MAC reseller; Bandwidth: only bill payments
                'client_collection'  => $clientCollection,
                'bill_total'         => $billTotal,
                'bill_paid'          => $billPaid,
                'bill_due'           => $billDue,
                'bill_count'         => $billCount,
                'unpaid_bills'       => $unpaidBills,
            ];
        })->toArray();

        // Summary by type
        $macSummary = collect($report)->where('type', 'mac');
        $bwSummary  = collect($report)->where('type', 'bandwidth');

        return view('admin.reports.reseller', compact('report', 'resellers', 'resellerType', 'macSummary', 'bwSummary'));
    }

    public function exportPdf(Request $request)
    {
        $reportType = $request->type ?? 'daily';
        $date = $request->date ?? today()->format('Y-m-d');
        
        $data = $this->getReportData($reportType, $request);
        $data['reportType'] = $reportType;
        $data['date'] = $date;
        $data['companyName'] = \App\Models\SystemSetting::get('company_name', config('app.name', 'ISP Billing System'));
        $data['currency'] = \App\Models\SystemSetting::get('currency_symbol', '৳');
        $data['logo'] = \App\Models\SystemSetting::get('site_logo', '');
        
        $pdf = Pdf::loadView('admin.reports.pdf.' . $reportType, $data);
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download($reportType . '-report-' . $date . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $reportType = $request->type ?? 'daily';
        $date = $request->date ?? today()->format('Y-m-d');
        
        $data = $this->getReportData($reportType, $request);
        
        $filename = $reportType . '-report-' . $date . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($data, $reportType) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            if ($reportType === 'daily' && isset($data['payments'])) {
                fputcsv($file, ['Date', 'Client', 'Amount (BDT)', 'Method', 'Collected By']);
                foreach ($data['payments'] as $payment) {
                    fputcsv($file, [
                        $payment->payment_date->format('Y-m-d'),
                        $payment->client->name ?? 'N/A',
                        $payment->amount,
                        $payment->paymentMethod->name ?? 'Cash',
                        $payment->collectedBy->name ?? 'Admin',
                    ]);
                }
            } elseif ($reportType === 'due' && isset($data['clients'])) {
                fputcsv($file, ['Client ID', 'Name', 'Phone', 'Zone', 'Package', 'Due Amount (BDT)']);
                foreach ($data['clients'] as $client) {
                    fputcsv($file, [
                        $client->client_id,
                        $client->name,
                        $client->phone,
                        $client->zone->name ?? 'N/A',
                        $client->package->name ?? 'N/A',
                        $client->due_amount,
                    ]);
                }
            } elseif ($reportType === 'monthly' && isset($data['dailyData'])) {
                fputcsv($file, ['Date', 'Collection (BDT)']);
                foreach ($data['dailyData'] as $row) {
                    fputcsv($file, [$row->date, $row->total]);
                }
            } elseif ($reportType === 'advance-liability' && isset($data['clients'])) {
                fputcsv($file, ['Client ID', 'Name', 'Zone', 'Advance Balance (BDT)', 'Due Amount (BDT)', 'Net Position (BDT)']);
                foreach ($data['clients'] as $client) {
                    fputcsv($file, [
                        $client['client_id'],
                        $client['name'],
                        $client['zone'],
                        $client['advance_balance'],
                        $client['due_amount'],
                        $client['net_position'],
                    ]);
                }
            }
            
            fclose($file);
        };
        
        return Response::stream($callback, 200, $headers);
    }
    
    protected function getReportData(string $type, Request $request): array
    {
        return match($type) {
            'daily' => [
                'payments' => Payment::with(['client', 'paymentMethod', 'collectedBy'])
                    ->whereDate('payment_date', $request->date ?? today())
                    ->get(),
                'totalCollection' => Payment::whereDate('payment_date', $request->date ?? today())->sum('amount'),
            ],
            'due' => [
                'clients' => Client::with(['zone', 'package'])->whereNull('reseller_id')->where('due_amount', '>', 0)->get(),
                'totalDue' => Client::whereNull('reseller_id')->sum('due_amount'),
            ],
            'monthly' => [
                'revenue' => Payment::whereYear('payment_date', $request->year ?? date('Y'))
                    ->whereMonth('payment_date', $request->month ?? date('m'))
                    ->sum('amount'),
                'dailyData' => Payment::whereYear('payment_date', $request->year ?? date('Y'))
                    ->whereMonth('payment_date', $request->month ?? date('m'))
                    ->selectRaw('DATE(payment_date) as date, SUM(amount) as total')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
            ],
            'advance-liability' => $this->reportService->getAdvanceLiabilityReport(
                $request->filled('start_date') ? Carbon::parse($request->start_date) : null,
                $request->filled('end_date') ? Carbon::parse($request->end_date) : null
            ),
            'zone' => [
                'report' => $this->reportService->getZoneRevenueReport(
                    $request->filled('start_date') ? Carbon::parse($request->start_date) : now()->startOfMonth(),
                    $request->filled('end_date') ? Carbon::parse($request->end_date) : now()->endOfMonth()
                ),
                'startDate' => $request->filled('start_date') ? Carbon::parse($request->start_date) : now()->startOfMonth(),
                'endDate' => $request->filled('end_date') ? Carbon::parse($request->end_date) : now()->endOfMonth(),
            ],
            default => [],
        };
    }

    public function technicianLeakage(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->endOfMonth();

        $report = $this->reportService->getTechnicianLeakageReport($startDate, $endDate);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'report' => $report,
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ]);
        }

        return view('admin.reports.technician-leakage', compact('report', 'startDate', 'endDate'));
    }

    public function agingAnalysis(Request $request)
    {
        $report = $this->reportService->getAgingAnalysisReport();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'report' => $report,
            ]);
        }

        return view('admin.reports.aging-analysis', compact('report'));
    }

    public function advanceVsDue(Request $request)
    {
        $report = $this->reportService->getAdvanceVsDueReport();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'zones' => $report['zones'],
                'summary' => $report['summary'],
            ]);
        }

        return view('admin.reports.advance-vs-due', compact('report'));
    }

    public function dashboardSummary()
    {
        $summary = $this->reportService->getDashboardSummary();

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    public function advanceLiability(Request $request)
    {
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay() 
            : Carbon::now()->startOfMonth();
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date)->endOfDay() 
            : Carbon::now()->endOfMonth();

        $report = $this->reportService->getAdvanceLiabilityReport($startDate, $endDate);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'report' => $report,
            ]);
        }

        return view('admin.reports.advance-liability', compact('report', 'startDate', 'endDate'));
    }
}

<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Client;
use App\Models\Payment;

class DashboardController extends Controller
{
    public function index()
    {
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            abort(403, 'Only Super Admin can access this area.');
        }

        $now = now();

        $totalCompanies     = Company::count();
        $activeCompanies    = Company::where('is_active', true)->count();
        $inactiveCompanies  = $totalCompanies - $activeCompanies;

        $totalClients    = Client::withoutGlobalScopes()->where('status', '!=', 'deleted')->count();
        $activeClients   = Client::withoutGlobalScopes()->where('status', 'active')->count();
        $inactiveClients = Client::withoutGlobalScopes()->where('status', 'inactive')->count();
        $onlineClients   = Client::withoutGlobalScopes()->where('status', 'active')->where('connection_status', 'online')->count();

        $newCompaniesThisMonth = Company::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)->count();

        $monthlyRevenue = Payment::withoutGlobalScopes()
            ->whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->sum('amount');

        $totalAllTimeRevenue = Payment::withoutGlobalScopes()->sum('amount');

        $totalPaymentsThisMonth = Payment::withoutGlobalScopes()
            ->whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->count();

        $revenueByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $m   = $now->copy()->subMonths($i);
            $rev = Payment::withoutGlobalScopes()
                ->whereMonth('payment_date', $m->month)
                ->whereYear('payment_date', $m->year)
                ->sum('amount');
            $revenueByMonth[] = ['label' => $m->format('M Y'), 'amount' => (float) $rev];
        }

        $expiredList = Company::where('is_active', true)
            ->whereNotNull('subscription_end')
            ->where('subscription_end', '<', $now->copy()->startOfDay())
            ->orderBy('subscription_end', 'asc')->get();

        $expiring7List = Company::where('is_active', true)
            ->whereNotNull('subscription_end')
            ->where('subscription_end', '>=', $now->copy()->startOfDay())
            ->where('subscription_end', '<=', $now->copy()->addDays(7)->endOfDay())
            ->orderBy('subscription_end', 'asc')->get();

        $expiring30List = Company::where('is_active', true)
            ->whereNotNull('subscription_end')
            ->where('subscription_end', '>', $now->copy()->addDays(7)->endOfDay())
            ->where('subscription_end', '<=', $now->copy()->addDays(30)->endOfDay())
            ->orderBy('subscription_end', 'asc')->get();

        $expiredCompanies  = $expiredList->count();
        $expiringCompanies = $expiring7List->count();

        $companiesDetailed = Company::withCount([
            'users',
            'clients as total_clients_count'  => fn ($q) => $q->where('status', '!=', 'deleted'),
            'clients as active_clients_count' => fn ($q) => $q->where('status', 'active'),
            'clients as online_clients_count' => fn ($q) => $q->where('status', 'active')->where('connection_status', 'online'),
        ])
        ->orderByDesc('active_clients_count')
        ->get()
        ->each(function ($co) use ($now) {
            $co->monthly_revenue = Payment::withoutGlobalScopes()
                ->where('company_id', $co->id)
                ->whereMonth('payment_date', $now->month)
                ->whereYear('payment_date', $now->year)
                ->sum('amount');
            $co->total_revenue = Payment::withoutGlobalScopes()
                ->where('company_id', $co->id)
                ->sum('amount');
        });

        $recentPayments = Payment::withoutGlobalScopes()
            ->with(['client:id,name,company_id', 'client.company:id,name,currency_symbol'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('superadmin.dashboard', compact(
            'totalCompanies', 'activeCompanies', 'inactiveCompanies',
            'totalClients', 'activeClients', 'inactiveClients', 'onlineClients',
            'newCompaniesThisMonth',
            'monthlyRevenue', 'totalAllTimeRevenue', 'totalPaymentsThisMonth',
            'revenueByMonth',
            'expiredCompanies', 'expiringCompanies',
            'expiredList', 'expiring7List', 'expiring30List',
            'companiesDetailed', 'recentPayments'
        ));
    }
}

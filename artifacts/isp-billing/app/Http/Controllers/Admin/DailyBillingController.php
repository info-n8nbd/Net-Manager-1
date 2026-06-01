<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\ResellerBillingConfig;
use App\Models\ResellerDailyBill;
use App\Services\DailyBillingService;
use Illuminate\Http\Request;

class DailyBillingController extends Controller
{
    protected DailyBillingService $dailyBillingService;

    public function __construct(DailyBillingService $dailyBillingService)
    {
        $this->dailyBillingService = $dailyBillingService;
    }

    public function index()
    {
        $configs = ResellerBillingConfig::with('reseller')->get();
        $resellers = Reseller::whereDoesntHave('billingConfig')->get();

        return view('admin.daily-billing.index', compact('configs', 'resellers'));
    }

    public function configure(Request $request)
    {
        $request->validate([
            'reseller_id' => 'required|exists:resellers,id',
            'billing_mode' => 'required|in:monthly,daily,per_minute',
            'daily_rate_per_client' => 'nullable|numeric|min:0',
            'per_minute_rate' => 'nullable|numeric|min:0',
        ]);

        ResellerBillingConfig::updateOrCreate(
            ['reseller_id' => $request->reseller_id],
            $request->only(['billing_mode', 'daily_rate_per_client', 'per_minute_rate', 'auto_deduct_wallet', 'suspend_on_zero_balance'])
        );

        return back()->with('success', 'Billing configuration updated');
    }

    public function history(Reseller $reseller, Request $request)
    {
        $result = $this->dailyBillingService->getBillingHistory($reseller->id, $request->month);
        return view('admin.daily-billing.history', compact('reseller', 'result'));
    }

    public function processBilling()
    {
        $results = $this->dailyBillingService->processAllDailyBilling();
        return back()->with('success', "Daily billing processed. {$results['processed']} resellers billed. Total: ৳{$results['total_billed']}");
    }
}

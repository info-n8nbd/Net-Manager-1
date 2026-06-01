<?php

namespace App\Jobs;

use App\Services\ResellerBillingService;
use App\Models\Reseller;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateResellerBillsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $billingService = new ResellerBillingService();
        $now = Carbon::now();
        $month = $now->month;
        $year = $now->year;

        // Only process resellers with auto_bill_generate enabled
        $resellers = Reseller::where('status', 'active')
            ->where('auto_bill_generate', true)
            ->get();

        if ($resellers->isEmpty()) {
            Log::info('GenerateResellerBillsJob: No resellers have auto_bill_generate enabled. Skipping.');
            return;
        }

        $totalRecharged = 0;

        foreach ($resellers as $reseller) {
            try {
                $recharged = $billingService->syncResellerClientBilling($reseller, $month, $year);
                $totalRecharged += $recharged;
                Log::info('Auto bill generated for reseller: ' . $reseller->name . ' (' . $month . '/' . $year . ')');
            } catch (\Exception $e) {
                Log::error('Failed to generate bill for reseller ' . $reseller->name . ': ' . $e->getMessage());
            }
        }

        Log::info('Reseller auto bill generation complete. ' . $totalRecharged . ' recharges for ' . $resellers->count() . ' resellers.');
    }
}

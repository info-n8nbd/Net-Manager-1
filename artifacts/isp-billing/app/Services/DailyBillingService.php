<?php

namespace App\Services;

use App\Models\Reseller;
use App\Models\ResellerBillingConfig;
use App\Models\ResellerDailyBill;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class DailyBillingService
{
    public function processAllDailyBilling(): array
    {
        $results = ['processed' => 0, 'errors' => [], 'total_billed' => 0];

        $configs = ResellerBillingConfig::where('billing_mode', '!=', 'monthly')
            ->with('reseller')
            ->get();

        foreach ($configs as $config) {
            try {
                $bill = $this->processDailyBill($config);
                $results['processed']++;
                $results['total_billed'] += $bill->amount;
            } catch (\Exception $e) {
                $results['errors'][] = ['reseller_id' => $config->reseller_id, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function processDailyBill(ResellerBillingConfig $config): ResellerDailyBill
    {
        return DB::transaction(function () use ($config) {
            $reseller = Reseller::lockForUpdate()->findOrFail($config->reseller_id);
            $today = now()->toDateString();

            $existing = ResellerDailyBill::where('reseller_id', $config->reseller_id)
                ->where('billing_date', $today)
                ->first();

            if ($existing) return $existing;

            $activeClients = Client::whereHas('resellerClient', fn($q) => $q->where('reseller_id', $config->reseller_id))
                ->where('status', 'active')
                ->count();

            $amount = $config->billing_mode === 'daily'
                ? $activeClients * $config->daily_rate_per_client
                : $activeClients * $config->per_minute_rate * 1440;

            $walletBefore = $reseller->wallet_balance;
            $walletAfter = $walletBefore;

            if ($config->auto_deduct_wallet) {
                $walletAfter = $walletBefore - $amount;
                $reseller->update(['wallet_balance' => $walletAfter]);
            }

            $bill = ResellerDailyBill::create([
                'reseller_id' => $config->reseller_id,
                'billing_date' => $today,
                'active_clients_count' => $activeClients,
                'total_minutes' => $config->billing_mode === 'per_minute' ? 1440 * $activeClients : 0,
                'amount' => $amount,
                'wallet_balance_before' => $walletBefore,
                'wallet_balance_after' => $walletAfter,
                'status' => 'billed',
            ]);

            $config->update(['last_billed_date' => $today]);

            if ($config->suspend_on_zero_balance && $walletAfter < 0) {
                $clientIds = \App\Models\ResellerClient::where('reseller_id', $config->reseller_id)->pluck('client_id');
                Client::whereIn('id', $clientIds)->where('status', 'active')->update(['status' => 'inactive']);
            }

            return $bill;
        });
    }

    public function getBillingHistory(int $resellerId, ?string $month = null): array
    {
        $query = ResellerDailyBill::where('reseller_id', $resellerId);

        if ($month) {
            $query->whereYear('billing_date', substr($month, 0, 4))
                ->whereMonth('billing_date', substr($month, 5, 2));
        }

        $bills = $query->orderBy('billing_date', 'desc')->get();

        return ['bills' => $bills, 'total_amount' => $bills->sum('amount'), 'total_days' => $bills->count()];
    }
}

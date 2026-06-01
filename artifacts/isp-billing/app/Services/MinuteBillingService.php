<?php

namespace App\Services;

use App\Models\MinutePackage;
use App\Models\MinuteSale;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class MinuteBillingService
{
    public function purchaseMinutes(int $clientId, int $packageId, ?int $resellerId = null): MinuteSale
    {
        return DB::transaction(function () use ($clientId, $packageId, $resellerId) {
            $package = MinutePackage::findOrFail($packageId);

            return MinuteSale::create([
                'client_id' => $clientId,
                'minute_package_id' => $packageId,
                'total_minutes' => $package->duration_minutes,
                'remaining_minutes' => $package->duration_minutes,
                'amount_paid' => $package->price,
                'purchased_at' => now(),
                'activated_at' => now(),
                'status' => 'active',
                'sold_by' => auth()->id(),
                'reseller_id' => $resellerId,
            ]);
        });
    }

    public function consumeMinutes(int $saleId, int $minutes): MinuteSale
    {
        return DB::transaction(function () use ($saleId, $minutes) {
            $sale = MinuteSale::lockForUpdate()->findOrFail($saleId);

            if ($sale->status !== 'active') {
                throw new \Exception('This minute package is not active');
            }

            $sale->used_minutes += $minutes;
            $sale->remaining_minutes = max(0, $sale->total_minutes - $sale->used_minutes);

            if ($sale->remaining_minutes <= 0) {
                $sale->status = 'exhausted';
            }

            $sale->save();
            return $sale;
        });
    }

    public function getActiveMinutes(int $clientId): ?MinuteSale
    {
        return MinuteSale::where('client_id', $clientId)
            ->where('status', 'active')
            ->where('remaining_minutes', '>', 0)
            ->orderBy('purchased_at', 'asc')
            ->first();
    }

    public function expireOldPackages(): int
    {
        return MinuteSale::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    public function getSalesReport(array $filters = []): array
    {
        $query = MinuteSale::with(['client', 'package', 'seller']);

        if (!empty($filters['date_from'])) $query->where('purchased_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->where('purchased_at', '<=', $filters['date_to']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['reseller_id'])) $query->where('reseller_id', $filters['reseller_id']);

        $sales = $query->orderBy('purchased_at', 'desc')->get();

        return [
            'sales' => $sales,
            'total_revenue' => $sales->sum('amount_paid'),
            'total_minutes_sold' => $sales->sum('total_minutes'),
            'total_minutes_used' => $sales->sum('used_minutes'),
        ];
    }
}

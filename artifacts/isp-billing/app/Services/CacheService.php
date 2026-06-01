<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Zone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheService
{
    protected static int $dashboardTtl = 300;
    protected static int $clientTtl = 60;
    protected static int $reportTtl = 600;

    public static function getDashboardStats(): array
    {
        return Cache::remember('dashboard:stats', self::$dashboardTtl, function () {
            return [
                'total_clients' => Client::where('status', '!=', 'deleted')->count(),
                'active_clients' => Client::where('status', 'active')->count(),
                'inactive_clients' => Client::where('status', 'inactive')->count(),
                'inactive_clients_blocked' => Client::where('status', 'inactive')->count(),
                'total_due' => Client::where('status', '!=', 'deleted')->sum('due_amount'),
                'total_advance' => Client::where('status', '!=', 'deleted')->sum('advance_amount'),
                'monthly_revenue' => Payment::whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year)
                    ->where('status', 'approved')
                    ->sum('amount'),
                'today_collection' => Payment::whereDate('payment_date', today())
                    ->where('status', 'approved')
                    ->sum('amount'),
                'pending_bills' => Bill::where('status', 'unpaid')->count(),
                'zones_count' => Zone::count(),
                'cached_at' => now()->toDateTimeString(),
            ];
        });
    }

    public static function getZoneStats(int $zoneId): array
    {
        return Cache::remember("zone:stats:{$zoneId}", self::$reportTtl, function () use ($zoneId) {
            return [
                'total_clients' => Client::where('zone_id', $zoneId)->count(),
                'active_clients' => Client::where('zone_id', $zoneId)->where('status', 'active')->count(),
                'total_due' => Client::where('zone_id', $zoneId)->sum('due_amount'),
                'monthly_collection' => Payment::whereHas('client', fn($q) => $q->where('zone_id', $zoneId))
                    ->whereMonth('payment_date', now()->month)
                    ->where('status', 'approved')
                    ->sum('amount'),
            ];
        });
    }

    public static function getClientData(int $clientId): ?array
    {
        return Cache::remember("client:data:{$clientId}", self::$clientTtl, function () use ($clientId) {
            $client = Client::with(['zone', 'area', 'package'])->find($clientId);
            if (!$client) return null;

            return [
                'id' => $client->id,
                'client_id' => $client->client_id,
                'name' => $client->name,
                'phone' => $client->phone,
                'status' => $client->status,
                'due_amount' => $client->due_amount,
                'advance_amount' => $client->advance_amount,
                'monthly_fee' => $client->monthly_fee,
                'zone_name' => $client->zone?->name,
                'area_name' => $client->area?->name,
                'package_name' => $client->package?->name,
            ];
        });
    }

    public static function clearClientCache(int $clientId): void
    {
        Cache::forget("client:data:{$clientId}");
    }

    public static function clearDashboardCache(): void
    {
        Cache::forget('dashboard:stats');
    }

    public static function clearZoneCache(int $zoneId): void
    {
        Cache::forget("zone:stats:{$zoneId}");
    }

    public static function clearAllCache(): void
    {
        Cache::forget('dashboard:stats');
        $zones = Zone::pluck('id');
        foreach ($zones as $zoneId) {
            Cache::forget("zone:stats:{$zoneId}");
        }
    }

    public static function getPackageDistribution(): array
    {
        return Cache::remember('dashboard:package_distribution', self::$dashboardTtl, function () {
            return Client::select('package_id', DB::raw('count(*) as count'))
                ->whereNotNull('package_id')
                ->where('status', 'active')
                ->groupBy('package_id')
                ->with('package:id,name')
                ->get()
                ->map(function ($item) {
                    return [
                        'package' => $item->package?->name ?? 'Unknown',
                        'count' => $item->count,
                    ];
                })
                ->toArray();
        });
    }

    public static function getRecentPayments(int $limit = 10): array
    {
        return Cache::remember('dashboard:recent_payments', 60, function () use ($limit) {
            return Payment::with(['client:id,name,client_id', 'collectedBy:id,name'])
                ->where('status', 'approved')
                ->orderByDesc('payment_date')
                ->take($limit)
                ->get()
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'client_name' => $payment->client?->name,
                        'client_id' => $payment->client?->client_id,
                        'amount' => $payment->amount,
                        'date' => $payment->payment_date->format('d-m-Y'),
                        'method' => $payment->payment_method,
                        'collected_by' => $payment->collectedBy?->name ?? 'Self',
                    ];
                })
                ->toArray();
        });
    }
}

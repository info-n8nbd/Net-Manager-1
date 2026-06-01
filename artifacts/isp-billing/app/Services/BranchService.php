<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Pop;
use App\Models\Client;
use App\Models\Zone;
use App\Models\Payment;

class BranchService
{
    public function getBranchStats(int $branchId): array
    {
        $branch = Branch::findOrFail($branchId);
        $clients = Client::where('branch_id', $branchId);
        $pops = Pop::where('branch_id', $branchId);

        return [
            'branch' => $branch,
            'total_clients' => $clients->count(),
            'active_clients' => (clone $clients)->where('status', 'active')->count(),
            'total_pops' => $pops->count(),
            'active_pops' => (clone $pops)->where('status', 'active')->count(),
            'total_zones' => Zone::where('branch_id', $branchId)->count(),
            'monthly_revenue' => Payment::whereHas('client', fn($q) => $q->where('branch_id', $branchId))
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];
    }

    public function getAllBranchesOverview(): array
    {
        return Branch::withCount([
            'clients',
            'clients as active_clients_count' => fn($q) => $q->where('status', 'active'),
            'pops',
            'zones',
        ])->get()->map(fn($b) => [
            'id' => $b->id,
            'name' => $b->name,
            'code' => $b->code,
            'is_main' => $b->is_main,
            'total_clients' => $b->clients_count,
            'active_clients' => $b->active_clients_count,
            'total_pops' => $b->pops_count,
            'total_zones' => $b->zones_count,
        ])->toArray();
    }
}

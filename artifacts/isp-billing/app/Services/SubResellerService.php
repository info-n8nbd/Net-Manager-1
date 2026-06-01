<?php

namespace App\Services;

use App\Models\Reseller;
use App\Models\SubResellerCommission;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubResellerService
{
    public function createSubReseller(array $data, int $parentResellerId): Reseller
    {
        return DB::transaction(function () use ($data, $parentResellerId) {
            $parentReseller = Reseller::findOrFail($parentResellerId);

            $user = \App\Models\User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => bcrypt($data['password'] ?? Str::random(10)),
                'user_type' => 'sub_reseller',
                'is_active' => true,
            ]);

            $subReseller = Reseller::create([
                'user_id' => $user->id,
                'reseller_id' => 'SR-' . str_pad(Reseller::max('id') + 1, 5, '0', STR_PAD_LEFT),
                'name' => $data['name'],
                'company_name' => $data['company_name'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'] ?? null,
                'nid' => $data['nid'] ?? null,
                'type' => $parentReseller->type,
                'commission_rate' => $data['commission_rate'] ?? 0,
                'credit_limit' => $data['credit_limit'] ?? 0,
                'parent_reseller_id' => $parentResellerId,
                'reseller_level' => 'sub_reseller',
                'parent_commission_rate' => $data['parent_commission_rate'] ?? 0,
                'branch_id' => $data['branch_id'] ?? $parentReseller->branch_id,
                'status' => 'active',
            ]);

            return $subReseller;
        });
    }

    public function getSubResellers(int $parentResellerId)
    {
        return Reseller::where('parent_reseller_id', $parentResellerId)
            ->where('reseller_level', 'sub_reseller')
            ->with(['user'])
            ->get();
    }

    public function calculateCommissionSplit(float $paymentAmount, Reseller $subReseller): array
    {
        $parentReseller = $subReseller->parentReseller;
        if (!$parentReseller) {
            return [
                'sub_reseller_commission' => $paymentAmount * ($subReseller->commission_rate / 100),
                'reseller_commission' => 0,
                'admin_commission' => $paymentAmount - ($paymentAmount * ($subReseller->commission_rate / 100)),
            ];
        }

        $subResellerComm = $paymentAmount * ($subReseller->commission_rate / 100);
        $parentComm = $paymentAmount * ($subReseller->parent_commission_rate / 100);
        $adminComm = $paymentAmount - $subResellerComm - $parentComm;

        return [
            'sub_reseller_commission' => $subResellerComm,
            'reseller_commission' => $parentComm,
            'admin_commission' => max(0, $adminComm),
        ];
    }

    public function recordCommission(Reseller $subReseller, int $clientId, int $paymentId, float $paymentAmount): SubResellerCommission
    {
        $split = $this->calculateCommissionSplit($paymentAmount, $subReseller);

        return SubResellerCommission::create([
            'reseller_id' => $subReseller->parent_reseller_id ?? 0,
            'sub_reseller_id' => $subReseller->id,
            'client_id' => $clientId,
            'payment_id' => $paymentId,
            'payment_amount' => $paymentAmount,
            'reseller_commission' => $split['reseller_commission'],
            'sub_reseller_commission' => $split['sub_reseller_commission'],
            'admin_commission' => $split['admin_commission'],
        ]);
    }

    public function getSubResellerStats(int $subResellerId): array
    {
        $subReseller = Reseller::findOrFail($subResellerId);

        return [
            'total_clients' => \App\Models\ResellerClient::where('reseller_id', $subResellerId)->count(),
            'active_clients' => Client::whereIn('id', \App\Models\ResellerClient::where('reseller_id', $subResellerId)->pluck('client_id'))->where('status', 'active')->count(),
            'wallet_balance' => $subReseller->wallet_balance,
            'total_commission' => SubResellerCommission::where('sub_reseller_id', $subResellerId)->sum('sub_reseller_commission'),
            'this_month_commission' => SubResellerCommission::where('sub_reseller_id', $subResellerId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('sub_reseller_commission'),
        ];
    }
}

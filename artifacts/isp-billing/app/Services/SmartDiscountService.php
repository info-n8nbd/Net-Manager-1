<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\DB;

class SmartDiscountService
{
    public function calculateSuggestedDiscount(Client $client): array
    {
        $suggestions = [];
        $totalSuggestedDiscount = 0;
        $monthlyFee = $client->monthly_fee ?? $client->package?->price ?? 0;

        $accountAge = $client->created_at->diffInMonths(now());
        if ($accountAge >= 24) {
            $loyaltyDiscount = min($monthlyFee * 0.05, 100);
            $suggestions[] = [
                'type' => 'loyalty',
                'reason' => "Customer for {$accountAge} months",
                'discount' => $loyaltyDiscount,
            ];
            $totalSuggestedDiscount += $loyaltyDiscount;
        }

        $onTimePayments = DB::table('payments')
            ->where('client_id', $client->id)
            ->whereExists(function($q) {
                $q->select(DB::raw(1))
                  ->from('bills')
                  ->whereColumn('bills.id', 'payments.bill_id')
                  ->whereColumn('payments.payment_date', '<=', 'bills.due_date');
            })
            ->count();

        $totalBills = DB::table('bills')
            ->where('client_id', $client->id)
            ->count();

        if ($totalBills >= 6 && $onTimePayments >= ($totalBills * 0.9)) {
            $onTimeDiscount = min($monthlyFee * 0.03, 50);
            $suggestions[] = [
                'type' => 'on_time_payment',
                'reason' => "90%+ on-time payment history",
                'discount' => $onTimeDiscount,
            ];
            $totalSuggestedDiscount += $onTimeDiscount;
        }

        $familyMembers = DB::table('family_group_members')
            ->where('client_id', $client->id)
            ->count();

        if ($familyMembers > 0) {
            $familyGroup = DB::table('family_group_members')
                ->join('family_groups', 'family_group_members.family_group_id', '=', 'family_groups.id')
                ->where('family_group_members.client_id', $client->id)
                ->first();

            if ($familyGroup) {
                $memberCount = DB::table('family_group_members')
                    ->where('family_group_id', $familyGroup->family_group_id)
                    ->count();

                $familyDiscount = min($monthlyFee * 0.02 * $memberCount, 150);
                $suggestions[] = [
                    'type' => 'family_bundle',
                    'reason' => "Family bundle with {$memberCount} connections",
                    'discount' => $familyDiscount,
                ];
                $totalSuggestedDiscount += $familyDiscount;
            }
        }

        $referrals = Client::where('referred_by', $client->id)->count();
        if ($referrals >= 2) {
            $referralDiscount = min($referrals * 20, 100);
            $suggestions[] = [
                'type' => 'referral',
                'reason' => "Referred {$referrals} customers",
                'discount' => $referralDiscount,
            ];
            $totalSuggestedDiscount += $referralDiscount;
        }

        $currentDiscount = $client->permanent_discount ?? 0;
        $maxDiscount = $monthlyFee * 0.15;
        $recommendedDiscount = min($totalSuggestedDiscount, $maxDiscount);

        return [
            'client_id' => $client->id,
            'monthly_fee' => $monthlyFee,
            'current_discount' => $currentDiscount,
            'suggested_discount' => round($recommendedDiscount, 0),
            'max_allowed_discount' => round($maxDiscount, 0),
            'breakdown' => $suggestions,
            'net_bill' => $monthlyFee - $recommendedDiscount,
        ];
    }

    public function applyDiscount(Client $client, float $discount, string $reason): bool
    {
        $maxDiscount = ($client->monthly_fee ?? 0) * 0.20;
        $discount = min($discount, $maxDiscount);

        $client->update([
            'permanent_discount' => $discount,
        ]);

        DB::table('client_timeline')->insert([
            'client_id' => $client->id,
            'event_type' => 'note',
            'title' => 'Discount Applied',
            'description' => "Permanent discount of ৳{$discount} applied. Reason: {$reason}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    public function getBulkDiscountSuggestions(int $limit = 50): array
    {
        $clients = Client::where('status', 'active')
            ->with('package')
            ->limit($limit * 2)
            ->get();

        $suggestions = [];
        foreach ($clients as $client) {
            $suggestion = $this->calculateSuggestedDiscount($client);
            if ($suggestion['suggested_discount'] > $suggestion['current_discount']) {
                $suggestions[] = [
                    'client' => [
                        'id' => $client->id,
                        'client_id' => $client->client_id,
                        'name' => $client->name,
                    ],
                    'suggestion' => $suggestion,
                ];
            }
        }

        usort($suggestions, fn($a, $b) => $b['suggestion']['suggested_discount'] <=> $a['suggestion']['suggested_discount']);
        return array_slice($suggestions, 0, $limit);
    }
}

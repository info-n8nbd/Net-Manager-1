<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Collection;

class BillingCalculatorService
{
    public static function calculate(Client $client): array
    {
        $totalBills = (float) $client->bills()
            ->withoutGlobalScopes()
            ->where('is_cancelled', false)
            ->sum('total_amount');

        $totalPaidOnBills = (float) $client->bills()
            ->withoutGlobalScopes()
            ->where('is_cancelled', false)
            ->sum('paid_amount');

        $unlinkedPayments = (float) $client->payments()
            ->withoutGlobalScopes()
            ->where('status', 'approved')
            ->whereNull('bill_id')
            ->sum('amount');

        $advanceRemaining = (float) $client->advancePayments()
            ->withoutGlobalScopes()
            ->sum('remaining_amount');

        $totalPayments = $totalPaidOnBills + $unlinkedPayments + $advanceRemaining;

        $due = max(0, $totalBills - $totalPaidOnBills - $unlinkedPayments - $advanceRemaining);

        $advance = max(0, $totalPayments - $totalBills);

        if ($client->is_free) {
            $due = 0;
        }

        return [
            'total_bills'    => $totalBills,
            'total_payments' => $totalPayments,
            'due'            => (float) $due,
            'advance'        => (float) $advance,
            'has_due'        => $due > 0,
            'has_advance'    => $advance > 0,
        ];
    }

    public static function calculateWithBills(Client $client): array
    {
        $summary = self::calculate($client);

        $bills = $client->bills()
            ->withoutGlobalScopes()
            ->where('is_cancelled', false)
            ->with(['payments' => function ($q) {
                $q->withoutGlobalScopes()->with(['paymentMethod', 'collectedBy']);
            }])
            ->orderBy('bill_date', 'desc')
            ->get();

        $billsOldestFirst = $bills->sortBy('bill_date');
        $availableAdvance = $summary['advance'];
        $advanceCreatedAt = null;
        $billDetails = [];

        foreach ($billsOldestFirst as $bill) {
            $billUnpaid = $bill->total_amount - $bill->paid_amount;
            $billOverpaid = max(0.0, (float) -$billUnpaid);
            $advanceAllocated = 0;
            $advanceAppliedAt = null;

            if ($billUnpaid > 0 && $availableAdvance > 0) {
                $advanceAllocated = min($billUnpaid, $availableAdvance);
                $availableAdvance -= $advanceAllocated;
                $advanceAppliedAt = $advanceCreatedAt ?? $bill->created_at;
                // Persist advance_used to DB so it survives future payments
                if ((float)$bill->advance_used !== (float)$advanceAllocated) {
                    $bill->withoutGlobalScopes()->where('id', $bill->id)
                        ->update(['advance_used' => $advanceAllocated, 'advance_applied_at' => $advanceAppliedAt]);
                    $bill->advance_used = $advanceAllocated;
                    $bill->advance_applied_at = $advanceAppliedAt;
                }
            }

            // Roll overpayment from this bill into pool for subsequent bills
            if ($billOverpaid > 0) {
                $availableAdvance += $billOverpaid;
                $latestPayment = $bill->payments->sortByDesc('payment_date')->first();
                $advanceCreatedAt = $latestPayment
                    ? ($latestPayment->created_at)
                    : $bill->updated_at;
            }

            $effectiveDue = max(0, $billUnpaid - $advanceAllocated);
            $isPaid = $bill->status === 'paid' || $effectiveDue == 0;

            $billDetails[$bill->id] = [
                'bill'              => $bill,
                'unpaid'            => $billUnpaid,
                'advance_allocated' => $advanceAllocated,
                'advance_applied_at' => $advanceAppliedAt,
                'effective_due'     => $effectiveDue,
                'is_paid'           => $isPaid,
            ];
        }

        return array_merge($summary, [
            'bills'        => $bills,
            'bill_details' => $billDetails,
        ]);
    }

    public static function getDisplayData(Client $client): array
    {
        $data = self::calculateWithBills($client);

        $advancePayments = $client->payments()
            ->withoutGlobalScopes()
            ->whereNull('bill_id')
            ->where('status', 'approved')
            ->with(['paymentMethod', 'collectedBy'])
            ->orderBy('payment_date', 'desc')
            ->get();

        return [
            'total_bills'              => $data['total_bills'],
            'total_bills_formatted'    => number_format($data['total_bills'], 2),
            'total_payments'           => $data['total_payments'],
            'total_payments_formatted' => number_format($data['total_payments'], 2),
            'due'                      => $data['due'],
            'due_formatted'            => number_format($data['due'], 2),
            'advance'                  => $data['advance'],
            'advance_formatted'        => number_format($data['advance'], 2),
            'has_due'                  => $data['has_due'],
            'has_advance'              => $data['has_advance'],
            'bills'                    => $data['bills'],
            'bill_details'             => $data['bill_details'],
            'advance_payments'         => $advancePayments,
        ];
    }

    public static function syncClientCache(Client $client): void
    {
        $data = self::calculate($client);
        $client->due_amount = $data['due'];
        $client->balance    = $data['advance'];
        $client->saveQuietly();
    }

    public static function syncMultipleClients(Collection $clients): int
    {
        $count = 0;
        foreach ($clients as $client) {
            self::syncClientCache($client);
            $count++;
        }
        return $count;
    }
}

<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\ClientActivityLog;
use App\Models\ClientStatusLog;
use App\Models\AdvancePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SyncService
{
    public function logBillingActivity(
        Client $client,
        string $activityType,
        string $description,
        array $metadata = [],
        string $source = 'system',
        ?int $performedBy = null
    ): ?ClientActivityLog {
        try {
            return ClientActivityLog::create([
                'client_id' => $client->id,
                'action_type' => $activityType,
                'reason' => $description,
                'extra_data' => array_merge($metadata, ['source' => $source]),
                'triggered_by' => $source,
                'performed_by' => $performedBy ?? auth()->id(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("SyncService: Failed to log billing activity", [
                'client_id' => $client->id,
                'action_type' => $activityType,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function logStatusChange(
        Client $client,
        string $oldStatus,
        string $newStatus,
        string $source = 'manual',
        ?string $reason = null,
        ?int $changedBy = null
    ): ?ClientStatusLog {
        if ($oldStatus === $newStatus) {
            return null;
        }

        try {
            // Check for duplicate entry within last 5 seconds
            $recentDuplicate = ClientStatusLog::where('client_id', $client->id)
                ->where('old_status', $oldStatus)
                ->where('new_status', $newStatus)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->first();
            
            if ($recentDuplicate) {
                return $recentDuplicate; // Return existing entry instead of creating duplicate
            }
            
            $statusLog = ClientStatusLog::create([
                'client_id' => $client->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'source' => $source,
                'reason' => $reason,
                'changed_by' => $changedBy ?? auth()->id(),
            ]);

            $this->logBillingActivity(
                $client,
                'status_change',
                "Status changed from {$oldStatus} to {$newStatus}",
                [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'source' => $source,
                    'reason' => $reason
                ],
                $source,
                $changedBy
            );

            return $statusLog;
        } catch (\Exception $e) {
            Log::error("SyncService: Failed to log status change", [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function syncClientBalance(Client $client): array
    {
        $previousDue = $client->due_amount ?? 0;
        $previousAdvance = $client->balance ?? 0;

        $totalBills = Bill::where('client_id', $client->id)
            
            ->sum('total_amount');
        
        $totalPayments = Payment::where('client_id', $client->id)
            ->where('status', 'approved')
            
            ->sum('amount');
        
        $totalDue = max(0, $totalBills - $totalPayments);
        $totalAdvance = max(0, $totalPayments - $totalBills);

        $client->update([
            'due_amount' => round($totalDue, 2),
            'balance' => round($totalAdvance, 2)
        ]);

        $this->clearClientCache($client->id);

        return [
            'previous_due' => $previousDue,
            'new_due' => $totalDue,
            'previous_advance' => $previousAdvance,
            'new_advance' => $totalAdvance,
            'changed' => $previousDue != $totalDue || $previousAdvance != $totalAdvance
        ];
    }

    public function logBillGenerated(Bill $bill, string $source = 'system', ?int $generatedBy = null): void
    {
        $client = $bill->client;

        $this->logBillingActivity(
            $client,
            'bill_generated',
            "Bill #{$bill->bill_number} generated for " . date('F Y', mktime(0, 0, 0, $bill->billing_month, 1, $bill->billing_year)),
            [
                'bill_id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'amount' => $bill->total_amount,
                'due_date' => $bill->due_date?->format('Y-m-d'),
                'billing_month' => $bill->billing_month,
                'billing_year' => $bill->billing_year,
            ],
            $source,
            $generatedBy
        );

        $this->syncClientBalance($client);
        $this->clearDashboardCache();
    }

    public function logPaymentReceived(Payment $payment, string $source = 'admin', ?int $receivedBy = null): void
    {
        $client = $payment->client;
        $bill = $payment->bill;

        $description = $bill 
            ? "Payment of ৳{$payment->amount} received for Bill #{$bill->bill_number}"
            : "Payment of ৳{$payment->amount} received";

        $this->logBillingActivity(
            $client,
            'payment_received',
            $description,
            [
                'payment_id' => $payment->id,
                'bill_id' => $bill?->id,
                'bill_number' => $bill?->bill_number,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'reference' => $payment->reference_number,
            ],
            $source,
            $receivedBy
        );

        $this->syncClientBalance($client);
        $this->clearDashboardCache();
    }

    public function logAdvanceReceived(AdvancePayment $advance, string $source = 'admin', ?int $receivedBy = null): void
    {
        $client = $advance->client;

        $this->logBillingActivity(
            $client,
            'advance_received',
            "Advance payment of ৳{$advance->amount} received",
            [
                'advance_id' => $advance->id,
                'amount' => $advance->amount,
                'payment_method' => $advance->payment_method ?? 'cash',
                'reference' => $advance->reference_number ?? null,
            ],
            $source,
            $receivedBy
        );

        $this->clearClientCache($client->id);
        $this->clearDashboardCache();
    }

    public function logAdvanceUsed(Client $client, float $amount, ?Bill $bill = null, string $source = 'system'): void
    {
        $description = $bill 
            ? "Advance of ৳{$amount} applied to Bill #{$bill->bill_number}"
            : "Advance of ৳{$amount} applied";

        $this->logBillingActivity(
            $client,
            'advance_used',
            $description,
            [
                'amount_used' => $amount,
                'bill_id' => $bill?->id,
                'bill_number' => $bill?->bill_number,
            ],
            $source
        );

        $this->syncClientBalance($client);
    }

    public function verifySyncIntegrity(Client $client): array
    {
        $issues = [];
        $fixed = [];

        $calculatedDue = Bill::where('client_id', $client->id)
            ->where('status', '!=', 'paid')
            ->sum('due_amount');

        if (abs(($client->due_amount ?? 0) - $calculatedDue) > 0.01) {
            $issues[] = [
                'type' => 'due_mismatch',
                'stored' => $client->due_amount,
                'calculated' => $calculatedDue
            ];
            $client->update(['due_amount' => $calculatedDue]);
            $fixed[] = 'due_amount';
        }

        $unpaidBills = Bill::where('client_id', $client->id)
            ->where('status', '!=', 'paid')
            ->get();

        foreach ($unpaidBills as $bill) {
            $activityExists = ClientActivityLog::where('client_id', $client->id)
                ->where('action_type', 'bill_generated')
                ->whereJsonContains('extra_data->bill_id', $bill->id)
                ->exists();

            if (!$activityExists) {
                $issues[] = [
                    'type' => 'missing_bill_history',
                    'bill_id' => $bill->id
                ];
                $this->logBillGenerated($bill, 'sync_repair');
                $fixed[] = "bill_history_{$bill->id}";
            }
        }

        $payments = Payment::where('client_id', $client->id)
            ->where('created_at', '>=', now()->subMonths(3))
            ->get();

        foreach ($payments as $payment) {
            $activityExists = ClientActivityLog::where('client_id', $client->id)
                ->where('action_type', 'payment_received')
                ->whereJsonContains('metadata->payment_id', $payment->id)
                ->exists();

            if (!$activityExists) {
                $issues[] = [
                    'type' => 'missing_payment_history',
                    'payment_id' => $payment->id
                ];
                $this->logPaymentReceived($payment, 'sync_repair');
                $fixed[] = "payment_history_{$payment->id}";
            }
        }

        $recentStatusLogs = ClientStatusLog::where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($recentStatusLogs && $recentStatusLogs->new_status !== $client->status) {
            $existingLog = ClientStatusLog::where('client_id', $client->id)
                ->where('new_status', $client->status)
                ->where('changed_at', '>', $recentStatusLogs->changed_at)
                ->exists();

            if (!$existingLog) {
                $issues[] = [
                    'type' => 'missing_status_log',
                    'current_status' => $client->status,
                    'last_logged_status' => $recentStatusLogs->new_status
                ];
                $this->logStatusChange(
                    $client,
                    $recentStatusLogs->new_status,
                    $client->status,
                    'sync_repair',
                    'Auto-created by sync verification'
                );
                $fixed[] = 'status_log';
            }
        }

        return [
            'client_id' => $client->id,
            'issues_found' => count($issues),
            'issues' => $issues,
            'fixed' => $fixed
        ];
    }

    public function runFullSyncCheck(int $limit = 100): array
    {
        $results = [
            'checked' => 0,
            'issues_found' => 0,
            'clients_with_issues' => [],
            'total_fixed' => 0
        ];

        $clients = Client::whereNotNull('id')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        foreach ($clients as $client) {
            $result = $this->verifySyncIntegrity($client);
            $results['checked']++;

            if ($result['issues_found'] > 0) {
                $results['issues_found'] += $result['issues_found'];
                $results['clients_with_issues'][] = $result;
                $results['total_fixed'] += count($result['fixed']);
            }
        }

        Log::info("SyncService: Full sync check completed", $results);

        return $results;
    }

    public function clearClientCache(int $clientId): void
    {
        Cache::forget("client_balance_{$clientId}");
        Cache::forget("client_advance_{$clientId}");
        Cache::forget("client_bills_{$clientId}");
    }

    public function clearDashboardCache(): void
    {
        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_revenue');
        Cache::forget('dashboard_collections');
        Cache::forget('billing_summary');
    }
}

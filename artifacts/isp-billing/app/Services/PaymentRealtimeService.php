<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Bill;
use App\Models\AdvancePayment;
use App\Events\PaymentReceived;
use App\Events\AdvanceReceived;
use App\Events\ClientStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentRealtimeService
{
    public function processPaymentInstant(array $paymentData): array
    {
        $clientId = $paymentData['client_id'];
        $amount = $paymentData['amount'];
        $method = $paymentData['payment_method'] ?? 'cash';
        $trxId = $paymentData['trx_id'] ?? null;

        if ($trxId && $this->isDuplicateTrxId($trxId)) {
            return [
                'success' => false,
                'error' => 'Duplicate transaction ID detected',
                'alert_type' => 'fraud_suspected',
            ];
        }

        $client = Client::find($clientId);
        if (!$client) {
            return ['success' => false, 'error' => 'Client not found'];
        }

        DB::beginTransaction();
        try {
            $payment = Payment::create([
                'client_id' => $clientId,
                'amount' => $amount,
                'payment_method' => $method,
                'trx_id' => $trxId,
                'payment_date' => now(),
                'status' => 'completed',
                'collected_by' => $paymentData['collected_by'] ?? null,
            ]);

            $this->allocatePaymentToBills($clientId, $amount);

            $remainingDue = Bill::where('client_id', $clientId)
                ->where('status', 'unpaid')
                ->sum('total_amount');

            if ($remainingDue <= 0 && $client->status !== 'active') {
                $this->enableClientConnection($clientId);
            }

            if (!empty($client->reseller_id)) {
                $this->updateResellerWallet($client->reseller_id, $amount);
            }

            DB::commit();

            $this->logPaymentEvent($clientId, $payment->id, $amount);
            $this->sendPaymentConfirmation($clientId, $amount);
            
            event(new PaymentReceived($payment, 'realtime', $paymentData['collected_by'] ?? null));

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'remaining_due' => $remainingDue,
                'connection_enabled' => $remainingDue <= 0,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Payment processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Payment processing failed'];
        }
    }

    public function isDuplicateTrxId(string $trxId): bool
    {
        $exists = Payment::where('trx_id', $trxId)
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();

        if ($exists) {
            DB::table('fraud_alerts')->insert([
                'alert_type' => 'duplicate_trx_id',
                'trx_id' => $trxId,
                'description' => "Duplicate transaction ID detected: {$trxId}",
                'severity' => 'high',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $exists;
    }

    protected function allocatePaymentToBills(int $clientId, float $amount): void
    {
        $bills = Bill::where('client_id', $clientId)
            ->where('status', 'unpaid')
            ->orderBy('due_date')
            ->get();

        $remaining = $amount;

        foreach ($bills as $bill) {
            if ($remaining <= 0) break;

            $billDue = $bill->total_amount - ($bill->paid_amount ?? 0);

            if ($remaining >= $billDue) {
                $bill->update([
                    'paid_amount' => $bill->total_amount,
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
                $remaining -= $billDue;
            } else {
                $bill->update([
                    'paid_amount' => ($bill->paid_amount ?? 0) + $remaining,
                ]);
                $remaining = 0;
            }
        }

        if ($remaining > 0) {
            $client = Client::find($clientId);
            
            $advance = AdvancePayment::create([
                'client_id' => $clientId,
                'amount' => $remaining,
                'remaining_amount' => $remaining,
                'used_amount' => 0,
                'payment_date' => now(),
                'notes' => 'Overpayment credit from realtime payment',
                'collected_by' => auth()->id(),
            ]);

            $client->balance = ($client->balance ?? 0) + $remaining;
            $client->save();

            if ($client) {
                event(new AdvanceReceived($advance, 'realtime', auth()->id()));
            }
        }
    }

    protected function enableClientConnection(int $clientId): void
    {
        $client = Client::find($clientId);
        if (!$client) return;

        $oldStatus = $client->status;
        $client->update(['status' => 'active']);

        event(new ClientStatusChanged(
            $client,
            $oldStatus,
            'active',
            'auto',
            'Auto-enabled after payment received'
        ));

        if ($client->mikrotik_device_id && $client->pppoe_username) {
            try {
                $mikrotikService = app(MikroTikService::class);
                $mikrotikService->enableClient($client);
            } catch (\Exception $e) {
                Log::warning("Failed to enable client on MikroTik: " . $e->getMessage());
            }
        }
    }

    protected function updateResellerWallet(int $resellerId, float $amount): void
    {
        DB::table('notification_queue')->insert([
            'type' => 'wallet_update',
            'channel' => 'all',
            'recipient_type' => 'reseller',
            'recipient_id' => $resellerId,
            'title' => 'Collection Received',
            'message' => "৳{$amount} collected from your client",
            'priority' => 'normal',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function logPaymentEvent(int $clientId, int $paymentId, float $amount): void
    {
        DB::table('client_timeline')->insert([
            'client_id' => $clientId,
            'event_type' => 'payment',
            'title' => 'Payment Received',
            'description' => "৳{$amount} payment received (ID: {$paymentId})",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('audit_logs')->insert([
            'action' => 'payment_received',
            'model_type' => 'Payment',
            'model_id' => $paymentId,
            'changes' => json_encode(['amount' => $amount, 'client_id' => $clientId]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function sendPaymentConfirmation(int $clientId, float $amount): void
    {
        $client = Client::find($clientId);
        if (!$client || !$client->phone) return;

        DB::table('notification_queue')->insert([
            'type' => 'payment_confirmation',
            'channel' => 'sms',
            'recipient_type' => 'client',
            'recipient_id' => $clientId,
            'title' => 'Payment Confirmed',
            'message' => "Dear {$client->name}, your payment of ৳{$amount} has been received. Thank you!",
            'priority' => 'normal',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getPaymentVerificationStatus(int $paymentId): array
    {
        $payment = Payment::find($paymentId);
        if (!$payment) {
            return ['error' => 'Payment not found'];
        }

        return [
            'payment_id' => $paymentId,
            'status' => $payment->status,
            'verified' => $payment->status === 'completed',
            'amount' => $payment->amount,
            'method' => $payment->payment_method,
            'trx_id' => $payment->trx_id,
            'created_at' => $payment->created_at,
        ];
    }

    public function getCollectionApprovalStatus(int $collectorId): array
    {
        $pendingApprovals = DB::table('staff_dues')
            ->where('user_id', $collectorId)
            ->where('status', 'pending')
            ->get();

        $approvedToday = DB::table('staff_dues')
            ->where('user_id', $collectorId)
            ->where('status', 'approved')
            ->whereDate('approved_at', today())
            ->sum('amount');

        return [
            'collector_id' => $collectorId,
            'pending_count' => $pendingApprovals->count(),
            'pending_amount' => $pendingApprovals->sum('amount'),
            'approved_today' => $approvedToday,
        ];
    }

    public function getLiveCashInHand(): array
    {
        $todayCollections = Payment::whereDate('created_at', today())
            ->where('status', 'completed')
            ->selectRaw('collected_by, SUM(amount) as total')
            ->groupBy('collected_by')
            ->get();

        $totalCash = $todayCollections->sum('total');

        return [
            'date' => today()->format('Y-m-d'),
            'total_collections' => $totalCash,
            'by_collector' => $todayCollections->map(fn($c) => [
                'collector_id' => $c->collected_by,
                'amount' => $c->total,
            ])->toArray(),
        ];
    }
}

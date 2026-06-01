<?php

namespace App\Jobs;

use App\Events\PaymentReceived;
use App\Models\AndroidSmsLog;
use App\Models\Client;
use App\Models\Payment;
use App\Services\BillingService;
use App\Services\PaymentGatewayService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAndroidSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [5, 15, 30];

    public function __construct(
        public readonly int $logId,
        public readonly int $companyId
    ) {}

    public function handle(): void
    {
        $log = AndroidSmsLog::find($this->logId);
        if (!$log) {
            Log::warning("ProcessAndroidSmsJob: Log #{$this->logId} not found");
            return;
        }

        // Only process pending/matched logs — skip already applied/duplicate
        if (!in_array($log->status, ['pending', 'matched'])) {
            Log::info("ProcessAndroidSmsJob: Log #{$this->logId} already in status {$log->status}, skipping");
            return;
        }

        $amount    = $log->amount;
        $reference = $log->reference;
        $trxId     = $log->trx_id;
        $gateway   = $log->gateway ?? 'bkash';

        // Duplicate TrxID check
        if ($trxId) {
            $existing = AndroidSmsLog::where('trx_id', $trxId)
                ->where('id', '!=', $log->id)
                ->where('status', 'applied')
                ->first();
            if ($existing) {
                $log->update(['status' => 'duplicate', 'failure_reason' => 'Duplicate TrxID already applied (Log #' . $existing->id . ')']);
                Log::info("ProcessAndroidSmsJob: Duplicate TrxID {$trxId}, log #{$log->id}");
                return;
            }
        }

        // No reference → stay pending for manual assignment
        if (!$reference) {
            $log->update(['status' => 'pending', 'failure_reason' => 'No reference found — manual client assignment required']);
            return;
        }

        // Find client
        $client = $this->findClient($reference, $this->companyId);
        if (!$client) {
            $log->update(['status' => 'pending', 'failure_reason' => "Client not found for reference: {$reference} — manual assignment required"]);
            return;
        }

        $log->update(['client_id' => $client->id, 'status' => 'matched']);

        // Per-client lock to avoid race condition on concurrent SMS for same client
        $lockKey = 'android_sms_client_' . $client->id;
        $lock = Cache::lock($lockKey, 90);
        if (!$lock->block(15)) {
            Log::warning("ProcessAndroidSmsJob: Could not acquire lock for client {$client->id}, log #{$log->id} — releasing for retry");
            $this->release(10);
            return;
        }

        try {
            $this->applyPayment($log, $client, [
                'amount'    => $amount,
                'gateway'   => $gateway,
                'sender'    => $log->sender_number,
                'reference' => $reference,
                'trx_id'    => $trxId,
            ]);
        } finally {
            $lock->release();
        }
    }

    private function findClient(string $reference, int $companyId): ?Client
    {
        $service = app(\App\Services\AndroidSmsParserService::class);
        $lookups = $service->parseReference($reference);
        foreach ($lookups as $lookup) {
            $client = Client::where('company_id', $companyId)
                ->where(function ($q) use ($lookup) {
                    $q->where('username', $lookup)
                      ->orWhere('client_id', $lookup)
                      ->orWhere('phone', $lookup);
                })->first();
            if ($client) return $client;
        }
        return null;
    }

    private function applyPayment(AndroidSmsLog $log, Client $client, array $parsed): void
    {
        try {
            $gateway = in_array($parsed['gateway'], ['bkash', 'nagad', 'rocket'])
                ? $parsed['gateway'] : 'bkash';

            $paymentMethodId = app(PaymentGatewayService::class)->getPaymentMethodIdPublic($gateway);
            $trxId = $parsed['trx_id'] ?? ('SMS-' . $log->id . '-' . time());

            $payment = DB::transaction(function () use ($log, $client, $parsed, $gateway, $paymentMethodId, $trxId) {
                $payment = Payment::create([
                    'client_id'         => $client->id,
                    'payment_method_id' => $paymentMethodId,
                    'amount'            => $parsed['amount'],
                    'payment_date'      => now(),
                    'transaction_id'    => $trxId,
                    'status'            => 'approved',
                    'source'            => 'online',
                    'notes'             => 'Auto via Android SMS Forwarder. Sender: ' . ($parsed['sender'] ?? 'N/A') . '. Ref: ' . ($parsed['reference'] ?? 'N/A'),
                    'company_id'        => $client->company_id,
                ]);
                $log->update(['payment_id' => $payment->id, 'trx_id' => $trxId, 'status' => 'applied']);
                return $payment;
            });

            event(new PaymentReceived($payment, 'online', null));

            $billingService = app(BillingService::class);
            $billingService->reapplyAllPaymentsToBills($client->fresh());
            $billingService->recalculateClientBalance($client->fresh());

            $client->refresh();
            if ($client->status === 'inactive' && $client->due_amount <= 0) {
                try {
                    $client->update(['status' => 'active', 'last_active_date' => now()]);
                } catch (\Throwable $e) {
                    Log::warning("ProcessAndroidSmsJob: Could not reactivate client: " . $e->getMessage());
                }
            }

            try {
                $remaining = max(0, $client->fresh()->due_amount ?? 0);
                $msg = "Payment Received! Tk " . number_format($parsed['amount'], 2)
                     . " via " . strtoupper($gateway)
                     . ". Remaining Due: Tk " . number_format($remaining, 2)
                     . ". Ref: " . ($parsed['reference'] ?? '') . ". Thank you!";
                app(SmsService::class)->sendSms($client->phone, $msg, 'payment_confirmation', $payment->id);
            } catch (\Throwable $e) {
                Log::warning("ProcessAndroidSmsJob: Confirmation SMS failed: " . $e->getMessage());
            }

            Log::info("ProcessAndroidSmsJob: Payment applied", [
                'log_id'    => $log->id,
                'client_id' => $client->client_id,
                'amount'    => $parsed['amount'],
                'gateway'   => $gateway,
            ]);

        } catch (\Throwable $e) {
            try {
                $log->update(['status' => 'failed', 'failure_reason' => substr($e->getMessage(), 0, 500)]);
            } catch (\Throwable $ex) {}
            Log::error("ProcessAndroidSmsJob: Payment failed: " . $e->getMessage(), ['log_id' => $log->id]);
            throw $e; // re-throw so queue retries automatically
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessAndroidSmsJob: All retries exhausted for log #{$this->logId}: " . $exception->getMessage());
        try {
            AndroidSmsLog::where('id', $this->logId)
                ->whereNotIn('status', ['applied', 'duplicate'])
                ->update([
                    'status'         => 'failed',
                    'failure_reason' => 'Queue job failed after 3 retries: ' . substr($exception->getMessage(), 0, 400),
                ]);
        } catch (\Throwable $e) {}
    }
}

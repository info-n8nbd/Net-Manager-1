<?php

namespace App\Jobs;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\TenantContext;

class DetectPaymentFraudJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);
        $startTime = microtime(true);
        $recordsProcessed = 0;

        try {
            $this->logCronStart();

            $recentPayments = Payment::where('created_at', '>=', now()->subHours(24))
                ->whereNotNull('transaction_id')
                ->get();

            foreach ($recentPayments as $payment) {
                $duplicates = Payment::where('transaction_id', $payment->transaction_id)
                    ->where('id', '!=', $payment->id)
                    ->exists();

                if ($duplicates) {
                    DB::table('payment_fraud_alerts')->updateOrInsert(
                        ['payment_id' => $payment->id],
                        [
                            'alert_type' => 'duplicate_trxid',
                            'transaction_id' => $payment->transaction_id,
                            'amount' => $payment->amount,
                            'message' => "Duplicate transaction ID detected: {$payment->transaction_id}",
                            'is_resolved' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                    $recordsProcessed++;
                }

                $avgAmount = Payment::where('client_id', $payment->client_id)
                    ->where('id', '!=', $payment->id)
                    ->avg('amount') ?? 0;

                if ($avgAmount > 0 && $payment->amount > ($avgAmount * 3)) {
                    DB::table('payment_fraud_alerts')->updateOrInsert(
                        ['payment_id' => $payment->id, 'alert_type' => 'suspicious_amount'],
                        [
                            'transaction_id' => $payment->transaction_id,
                            'amount' => $payment->amount,
                            'message' => "Suspicious payment amount: {$payment->amount} BDT (avg: {$avgAmount} BDT)",
                            'is_resolved' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                    $recordsProcessed++;
                }
            }

            $this->logCronComplete($startTime, $recordsProcessed);

        } catch (\Exception $e) {
            Log::error("Payment fraud detection failed: " . $e->getMessage());
            $this->logCronFailed($startTime, $e->getMessage());
        }
    }

    protected function logCronStart(): void
    {
        DB::table('cron_job_logs')->insert([
            'job_name' => 'detect_payment_fraud',
            'job_class' => self::class,
            'status' => 'started',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function logCronComplete(float $startTime, int $records): void
    {
        $executionTime = (int)((microtime(true) - $startTime) * 1000);
        DB::table('cron_job_logs')
            ->where('job_class', self::class)
            ->where('status', 'started')
            ->orderByDesc('id')
            ->limit(1)
            ->update([
                'status' => 'completed',
                'records_processed' => $records,
                'execution_time_ms' => $executionTime,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function logCronFailed(float $startTime, string $message): void
    {
        $executionTime = (int)((microtime(true) - $startTime) * 1000);
        DB::table('cron_job_logs')
            ->where('job_class', self::class)
            ->where('status', 'started')
            ->orderByDesc('id')
            ->limit(1)
            ->update([
                'status' => 'failed',
                'message' => $message,
                'execution_time_ms' => $executionTime,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}

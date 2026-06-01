<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckWarrantyExpiryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $alertDays;

    public function __construct(int $alertDays = 30)
    {
        $this->alertDays = $alertDays;
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        $recordsProcessed = 0;

        try {
            $this->logCronStart();

            $expiringWarranties = DB::table('device_warranties')
                ->where('warranty_end', '<=', now()->addDays($this->alertDays))
                ->where('warranty_end', '>=', now())
                ->where('alert_sent', false)
                ->get();

            foreach ($expiringWarranties as $warranty) {
                $daysRemaining = now()->diffInDays($warranty->warranty_end);

                DB::table('network_event_logs')->insert([
                    'event_type' => 'alert',
                    'source' => 'warranty_expiry',
                    'message' => "Warranty expiring in {$daysRemaining} days for {$warranty->device_type} (S/N: {$warranty->serial_number})",
                    'severity' => $daysRemaining <= 7 ? 'warning' : 'info',
                    'metadata' => json_encode([
                        'device_type' => $warranty->device_type,
                        'device_id' => $warranty->device_id,
                        'serial_number' => $warranty->serial_number,
                        'warranty_end' => $warranty->warranty_end,
                        'days_remaining' => $daysRemaining,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('device_warranties')
                    ->where('id', $warranty->id)
                    ->update(['alert_sent' => true, 'updated_at' => now()]);

                $recordsProcessed++;
            }

            $expiredWarranties = DB::table('device_warranties')
                ->where('warranty_end', '<', now())
                ->where('alert_sent', false)
                ->get();

            foreach ($expiredWarranties as $warranty) {
                DB::table('network_event_logs')->insert([
                    'event_type' => 'alert',
                    'source' => 'warranty_expired',
                    'message' => "Warranty EXPIRED for {$warranty->device_type} (S/N: {$warranty->serial_number})",
                    'severity' => 'warning',
                    'metadata' => json_encode([
                        'device_type' => $warranty->device_type,
                        'device_id' => $warranty->device_id,
                        'serial_number' => $warranty->serial_number,
                        'warranty_end' => $warranty->warranty_end,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('device_warranties')
                    ->where('id', $warranty->id)
                    ->update(['alert_sent' => true, 'updated_at' => now()]);

                $recordsProcessed++;
            }

            $this->logCronComplete($startTime, $recordsProcessed);

        } catch (\Exception $e) {
            Log::error("Warranty expiry check failed: " . $e->getMessage());
            $this->logCronFailed($startTime, $e->getMessage());
        }
    }

    protected function logCronStart(): void
    {
        DB::table('cron_job_logs')->insert([
            'job_name' => 'check_warranty_expiry',
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

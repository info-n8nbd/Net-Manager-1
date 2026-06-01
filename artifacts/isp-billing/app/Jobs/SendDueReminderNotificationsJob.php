<?php

namespace App\Jobs;

use App\Models\Bill;
use App\Models\Client;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\TenantContext;

class SendDueReminderNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);
        $startTime = microtime(true);
        $recordsProcessed = 0;

        try {
            $this->logCronStart();

            $maintenanceMode = DB::table('maintenance_modes')
                ->where('is_global', true)
                ->where('pause_alerts', true)
                ->whereNull('ended_at')
                ->exists();

            if ($maintenanceMode) {
                Log::info("Due reminder notifications skipped - maintenance mode active");
                $this->logCronComplete($startTime, 0);
                return;
            }

            $dueBills = Bill::where('status', 'unpaid')
                ->whereBetween('due_date', [now()->subDays(5), now()->addDays(3)])
                ->with('client')
                ->get();

            foreach ($dueBills as $bill) {
                $client = $bill->client;
                if (!$client || !$client->phone) continue;

                $zoneInMaintenance = DB::table('maintenance_modes')
                    ->where('zone_id', $client->zone_id)
                    ->where('pause_alerts', true)
                    ->whereNull('ended_at')
                    ->exists();

                if ($zoneInMaintenance) continue;

                $lastReminder = DB::table('due_reminders')
                    ->where('bill_id', $bill->id)
                    ->whereDate('sent_at', today())
                    ->exists();

                if ($lastReminder) continue;

                $daysUntilDue = now()->diffInDays($bill->due_date, false);
                
                if ($daysUntilDue <= 0) {
                    $message = "Dear {$client->name}, your bill of ৳{$bill->total_amount} is OVERDUE. Pay now: " . rtrim(config('app.url'), '/') . "/payment SOFTYNEX";
                } elseif ($daysUntilDue <= 3) {
                    $message = "Dear {$client->name}, your bill of ৳{$bill->total_amount} is due in {$daysUntilDue} days. Pay: " . rtrim(config('app.url'), '/') . "/payment SOFTYNEX";
                } else {
                    continue;
                }

                DB::table('due_reminders')->insert([
                    'bill_id'      => $bill->id,
                    'client_id'   => $client->id,
                    'company_id'  => $client->company_id,
                    'reminder_day' => max((int) $daysUntilDue, 0),
                    'message'     => $message,
                    'channel'     => 'sms',
                    'sms_sent'    => false,
                    'sent_at'     => now(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                DB::table('sms_logs')->insert([
                    'phone' => $client->phone,
                    'message' => $message,
                    'type' => 'due_reminder',
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $recordsProcessed++;
            }

            $this->logCronComplete($startTime, $recordsProcessed);

        } catch (\Exception $e) {
            Log::error("Due reminder notifications failed: " . $e->getMessage());
            $this->logCronFailed($startTime, $e->getMessage());
        }
    }

    protected function logCronStart(): void
    {
        DB::table('cron_job_logs')->insert([
            'job_name' => 'send_due_reminder_notifications',
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

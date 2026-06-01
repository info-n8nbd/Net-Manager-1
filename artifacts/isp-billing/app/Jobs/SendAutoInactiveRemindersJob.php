<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\SystemSetting;
use App\Services\SmsService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendAutoInactiveRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);

        if (SystemSetting::get('sms_global_enabled', '0') !== '1') {
            Log::info('SendAutoInactiveRemindersJob: global SMS disabled, skipping.');
            return;
        }

        // Check maintenance mode
        $maintenanceMode = DB::table('maintenance_modes')
            ->where('is_global', true)
            ->where('pause_alerts', true)
            ->whereNull('ended_at')
            ->exists();

        if ($maintenanceMode) {
            Log::info('SendAutoInactiveRemindersJob: skipped — maintenance mode active.');
            return;
        }

        $today    = Carbon::today('Asia/Dhaka');
        $smsService = app(SmsService::class);

        foreach ([3, 1] as $daysAhead) {
            $eventType   = $daysAhead <= 1 ? SmsService::EVENT_DUE_REMINDER_1DAY : SmsService::EVENT_DUE_REMINDER_3DAY;
            $settingKey  = $daysAhead <= 1 ? 'sms_event_due_reminder_1day' : 'sms_event_due_reminder_3day';

            if (SystemSetting::get($settingKey, '1') !== '1') {
                continue;
            }

            $targetDate = $today->copy()->addDays($daysAhead)->toDateString();

            // Load active clients with due > 0 and a phone number
            $clients = Client::where('status', 'active')
                ->where('due_amount', '>', 0)
                ->whereNotNull('phone')
                ->whereNull('reseller_id')
                ->get();

            $sent   = 0;
            $failed = 0;

            foreach ($clients as $client) {
                try {
                    // Calculate the client's auto-inactive date
                    $aiDay = (int) min($client->payment_deadline ?? 15, 28);

                    if ($client->temp_deadline_date) {
                        $aiDate = Carbon::parse($client->temp_deadline_date, 'Asia/Dhaka');
                    } else {
                        $aiDate = $today->day <= $aiDay
                            ? Carbon::create($today->year, $today->month, $aiDay, 0, 0, 0, 'Asia/Dhaka')
                            : Carbon::create($today->year, $today->month, $aiDay, 0, 0, 0, 'Asia/Dhaka')->addMonthNoOverflow();
                    }

                    if ($aiDate->toDateString() !== $targetDate) {
                        continue;
                    }

                    // Check zone maintenance
                    $zoneInMaintenance = DB::table('maintenance_modes')
                        ->where('zone_id', $client->zone_id)
                        ->where('pause_alerts', true)
                        ->whereNull('ended_at')
                        ->exists();

                    if ($zoneInMaintenance) {
                        continue;
                    }

                    // Prevent duplicate for same event+client today
                    $alreadySent = DB::table('sms_logs')
                        ->where('client_id', $client->id)
                        ->where('event_type', $eventType)
                        ->where('status', 'sent')
                        ->whereDate('created_at', today())
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    $result = $smsService->sendDueReminderNotice($client, $daysAhead);

                    if ($result && $result->status === 'sent') {
                        $sent++;
                        Log::info("AutoInactiveReminder [{$daysAhead}d]: sent to {$client->client_id} ({$client->phone})");
                    } elseif ($result && $result->skip_reason) {
                        Log::info("AutoInactiveReminder [{$daysAhead}d]: skipped for {$client->client_id} — {$result->skip_reason}");
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("AutoInactiveReminder [{$daysAhead}d] failed for client {$client->id}: " . $e->getMessage());
                }
            }

            Log::info("SendAutoInactiveRemindersJob [{$daysAhead}d]: sent={$sent}, failed={$failed}");
        }
    }
}

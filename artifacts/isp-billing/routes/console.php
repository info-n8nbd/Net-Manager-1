<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncMikrotikSessionsJob;
use App\Jobs\CheckRevenueLeakageJob;
use App\Jobs\AutoDisableOverdueClientsJob;
use App\Jobs\GenerateDailyBillsJob;
use App\Jobs\DetectPaymentFraudJob;
use App\Jobs\PollOltOnuStatusJob;
use App\Jobs\PollOltRxPowerJob;
use App\Jobs\PollGponTelnetJob;
use App\Jobs\AutoCreateTicketJob;
use App\Jobs\DetectAreaOutageJob;
use App\Jobs\CheckWarrantyExpiryJob;
use App\Jobs\SendDueReminderNotificationsJob;
use App\Jobs\SendAutoInactiveRemindersJob;
use App\Jobs\SyncClientQualityScoresJob;
use App\Jobs\UpdateChurnPredictionsJob;
use App\Jobs\AutoEscalateTicketsJob;
use App\Jobs\CalculateResellerCommissionsJob;
use App\Jobs\ExpireSpeedBoostsJob;
use App\Jobs\SyncRouterHealthJob;
use App\Jobs\ProcessNotificationQueueJob;
use App\Jobs\DetectAbnormalBehaviorJob;
use App\Jobs\MonitorCronHealthJob;
use App\Jobs\DetectFiberCutsJob;
use App\Jobs\CheckTicketSlaJob;
use App\Jobs\AutoEnableAfterPaymentJob;
use App\Jobs\AutoExpireClientsJob;
use App\Jobs\MikrotikSyncJob;
use App\Models\SystemSetting;
use App\Services\TenantContext;
use App\Jobs\MapOnuToClientJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


/*
|--------------------------------------------------------------------------
| CORE SYSTEM JOBS
|--------------------------------------------------------------------------
*/
Schedule::call(function () {
    app()->make(SyncMikrotikSessionsJob::class)->handle();
})->everyMinute()
  ->name('sync-mikrotik-sessions')
  ->withoutOverlapping();

Schedule::job(new SyncRouterHealthJob)
    ->everyMinute()
    ->name('sync-router-health')
    ->withoutOverlapping();

Schedule::job(new PollOltOnuStatusJob)
    ->everyThreeMinutes()
    ->name('poll-olt-onu-status')
    ->withoutOverlapping();

// GPON Telnet: real per-ONU serial, rx/tx power, distance via CLI
// Only runs when OLT has telnet_port configured (VPS IP whitelisted on OLT)
Schedule::job(new PollGponTelnetJob)
    ->everyTenMinutes()
    ->name('poll-gpon-telnet')
    ->withoutOverlapping();

// PollOltRxPowerJob DISABLED — rx power now collected in combined session by PollOltOnuStatusJob
// Schedule::job(new PollOltRxPowerJob)
//     ->everyTenMinutes()
//     ->name('poll-olt-rx-power')
//     ->withoutOverlapping();

Schedule::job(new DetectAreaOutageJob(50))
    ->everyFiveMinutes()
    ->name('detect-area-outage')
    ->withoutOverlapping();

Schedule::job(new ExpireSpeedBoostsJob)
    ->everyFiveMinutes()
    ->name('expire-speed-boosts')
    ->withoutOverlapping();

Schedule::job(new DetectFiberCutsJob)
    ->everyFiveMinutes()
    ->name('detect-fiber-cuts')
    ->withoutOverlapping();

Schedule::job(new ProcessNotificationQueueJob)
    ->everyFiveMinutes()
    ->name('process-notification-queue')
    ->withoutOverlapping();

Schedule::job(new AutoCreateTicketJob(30))
    ->everyTenMinutes()
    ->name('auto-create-ticket')
    ->withoutOverlapping();

Schedule::job(new MonitorCronHealthJob)
    ->everyFifteenMinutes()
    ->name('monitor-cron-health')
    ->withoutOverlapping();

Schedule::job(new CheckRevenueLeakageJob)
    ->everyThirtyMinutes()
    ->name('check-revenue-leakage')
    ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| HOURLY ANALYTICS
|--------------------------------------------------------------------------
*/
Schedule::job(new SyncClientQualityScoresJob)
    ->hourly()
    ->name('sync-client-quality-scores')
    ->withoutOverlapping();

Schedule::job(new DetectPaymentFraudJob)
    ->hourly()
    ->name('detect-payment-fraud')
    ->withoutOverlapping();

Schedule::job(new AutoEscalateTicketsJob)
    ->hourly()
    ->name('auto-escalate-tickets')
    ->withoutOverlapping();

Schedule::job(new CheckTicketSlaJob)
    ->hourly()
    ->name('check-ticket-sla')
    ->withoutOverlapping();

Schedule::job(new DetectAbnormalBehaviorJob)
    ->hourly()
    ->name('detect-abnormal-behavior')
    ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| 🔴 REAL-TIME ISP AUTOMATION (run inline, not queued)
|--------------------------------------------------------------------------
*/
Schedule::call(function () {
    app()->make(\App\Jobs\ProcessClientSchedulesJob::class)->handle();
})->everyMinute()
  ->name('process-client-schedules')
  ->withoutOverlapping();

Schedule::call(function () {
    app()->make(AutoDisableOverdueClientsJob::class)->handle();
})->everyMinute()
  ->name('auto-disable-overdue-clients')
  ->withoutOverlapping();

Schedule::call(function () {
    app()->make(AutoEnableAfterPaymentJob::class)->handle();
})->everyMinute()
  ->name('auto-enable-after-payment')
  ->withoutOverlapping();

Schedule::call(function () {
    app()->make(AutoExpireClientsJob::class)->handle();
})->everyFiveMinutes()
  ->name('auto-expire-clients')
  ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| 🟢 BILL GENERATION (run inline, not queued)
|--------------------------------------------------------------------------
*/
Schedule::call(function () {
    $job = app()->make(GenerateDailyBillsJob::class);
    $job->handle(app(\App\Services\BillingService::class));
})->everyMinute()
  ->name('generate-daily-bills')
  ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| OTHER DAILY JOBS
|--------------------------------------------------------------------------
*/
Schedule::job(new UpdateChurnPredictionsJob)
    ->dailyAt('02:00')
    ->name('update-churn-predictions')
    ->withoutOverlapping();

Schedule::job(new SendDueReminderNotificationsJob)
    ->dailyAt('08:00')
    ->name('send-due-reminder-notifications')
    ->withoutOverlapping();

Schedule::job(new SendAutoInactiveRemindersJob)
    ->dailyAt(SystemSetting::get('sms_auto_reminder_time', '09:30'))
    ->name('send-auto-inactive-reminders')
    ->withoutOverlapping();

Schedule::job(new CheckWarrantyExpiryJob(30))
    ->dailyAt('09:00')
    ->name('check-warranty-expiry')
    ->withoutOverlapping();

Schedule::job(new CalculateResellerCommissionsJob)
    ->monthlyOn(1, '00:30')
    ->name('calculate-reseller-commissions')
    ->withoutOverlapping();

Schedule::call(function () {
    app()->make(\App\Jobs\GenerateResellerBillsJob::class)->handle();
})->monthlyOn(1, '01:00')
  ->name('generate-reseller-bills')
  ->withoutOverlapping();



/*
|--------------------------------------------------------------------------
| ONU-TO-CLIENT AUTO MAPPING
|--------------------------------------------------------------------------
*/
Schedule::job(new MapOnuToClientJob)
    ->everyFifteenMinutes()
    ->name("map-onu-to-client")
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| MIKROTIK SYNC
|--------------------------------------------------------------------------
*/
Schedule::call(function () {
    TenantContext::setSuperAdminMode(true);
    $devices = \App\Models\MikrotikDevice::where('is_active', true)->get();
    foreach ($devices as $device) {
        MikrotikSyncJob::dispatch($device->id, 'dry_run');
    }
})
->dailyAt('03:00')
->name('mikrotik-two-way-sync')
->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| VOICE CALL AUTOMATION (Twilio)
|--------------------------------------------------------------------------
*/
Schedule::command('voice:send-reminders --type=bill_reminder')
    ->dailyAt('10:00')
    ->name('voice-bill-reminder-calls')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('voice:send-reminders --type=expiry_warning')
    ->dailyAt('11:00')
    ->name('voice-expiry-warning-calls')
    ->withoutOverlapping()
    ->runInBackground();


/*
|--------------------------------------------------------------------------
| EMAIL AUTOMATION
|--------------------------------------------------------------------------
*/
Schedule::command('email:send-reminders --type=bill_reminder')
    ->dailyAt('09:00')
    ->name('email-bill-reminder')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('email:send-reminders --type=expiry_warning')
    ->dailyAt('09:30')
    ->name('email-expiry-warning')
    ->withoutOverlapping()
    ->runInBackground();


/*
|--------------------------------------------------------------------------
| AUTO LOG CLEANUP (Weekly)
|--------------------------------------------------------------------------
*/
Schedule::call(function () {
    // Keep only last 7 days of cron job logs
    DB::table("cron_job_logs")->where("created_at", "<", now()->subDays(7))->delete();
    // Keep only last 7 days of cron health logs
    DB::table("cron_health_logs")->where("created_at", "<", now()->subDays(7))->delete();
    // Keep only last 7 days of router health logs
    DB::table("router_health_logs")->where("created_at", "<", now()->subDays(7))->delete();
    // Keep resolved network events for 30 days
    DB::table("network_event_logs")->where("is_resolved", true)->where("created_at", "<", now()->subDays(30))->delete();
    // Delete old online client history (keep 14 days)
    DB::table("online_client_history")->where("recorded_at", "<", now()->subDays(14))->delete();
    // Delete old daily Laravel log files (keep 30 days)
    foreach (glob("/var/www/smart-isp/isp-app/storage/logs/laravel-*.log") as $f) {
        if (filemtime($f) < strtotime("-30 days")) {
            @unlink($f);
        }
    }
    Log::info("Auto log cleanup completed");
})
->weekly()
->sundays()
->at("03:00")
->name("auto-log-cleanup")
->withoutOverlapping();


<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\VoiceCallSetting;
use App\Services\VoiceCallService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendVoiceCallReminders extends Command
{
    protected $signature   = 'voice:send-reminders {--type=bill_reminder : bill_reminder or expiry_warning} {--dry-run : Show who would be called without actually calling}';
    protected $description = 'Auto-call clients with bill reminders or expiry warnings';

    public function handle(VoiceCallService $callService): int
    {
        $settings = VoiceCallSetting::first();

        if (!$settings || !$settings->is_active) {
            $this->warn('Voice call is not active. Enable it in Admin → Voice Calls → Settings.');
            return 0;
        }

        $type   = $this->option('type');
        $dryRun = $this->option('dry-run');

        if ($type === 'bill_reminder') {
            return $this->sendBillReminders($callService, $settings, $dryRun);
        }

        if ($type === 'expiry_warning') {
            return $this->sendExpiryWarnings($callService, $settings, $dryRun);
        }

        $this->error('Unknown type. Use --type=bill_reminder or --type=expiry_warning');
        return 1;
    }

    protected function sendBillReminders(VoiceCallService $callService, VoiceCallSetting $settings, bool $dryRun): int
    {
        if (!$settings->auto_bill_reminder) {
            $this->info('Auto bill reminder is disabled in settings.');
            return 0;
        }

        $clients = Client::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where('status', 'active')
            ->whereHas('bills', function ($q) {
                $q->where('status', 'unpaid')
                  ->where('due_date', '<=', now()->addDays(3));
            })
            ->with(['bills' => function ($q) {
                $q->where('status', 'unpaid')->orderBy('due_date');
            }])
            ->get();

        $this->info("Found {$clients->count()} client(s) with due bills.");

        $called = 0;
        foreach ($clients as $client) {
            if ($dryRun) {
                $this->line("  [DRY-RUN] Would call: {$client->name} ({$client->phone})");
                continue;
            }

            $log = $callService->sendBillReminder($client->id);
            $status = $log->status ?? 'unknown';
            $this->line("  Called {$client->name} ({$client->phone}) → {$status}");

            if ($status === 'initiated') {
                $called++;
                sleep(2);
            }
        }

        if (!$dryRun) {
            $this->info("Done. {$called} call(s) initiated.");
        }

        return 0;
    }

    protected function sendExpiryWarnings(VoiceCallService $callService, VoiceCallSetting $settings, bool $dryRun): int
    {
        if (!$settings->auto_expiry_warning) {
            $this->info('Auto expiry warning is disabled in settings.');
            return 0;
        }

        $days = $settings->reminder_days_before ?? 3;

        $clients = Client::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where('status', 'active')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->get();

        $this->info("Found {$clients->count()} client(s) with expiring packages.");

        $called = 0;
        foreach ($clients as $client) {
            if ($dryRun) {
                $this->line("  [DRY-RUN] Would call: {$client->name} ({$client->phone})");
                continue;
            }

            $log = $callService->sendExpiryWarning($client->id);
            $status = $log->status ?? 'unknown';
            $this->line("  Called {$client->name} ({$client->phone}) → {$status}");

            if ($status === 'initiated') {
                $called++;
                sleep(2);
            }
        }

        if (!$dryRun) {
            $this->info("Done. {$called} call(s) initiated.");
        }

        return 0;
    }
}

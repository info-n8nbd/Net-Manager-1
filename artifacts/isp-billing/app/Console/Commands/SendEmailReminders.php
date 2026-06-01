<?php

namespace App\Console\Commands;

use App\Services\EmailReminderService;
use Illuminate\Console\Command;

class SendEmailReminders extends Command
{
    protected $signature   = 'email:send-reminders {--type=all : bill_reminder, expiry_warning, or all}';
    protected $description = 'Send automated email reminders to clients';

    public function handle(EmailReminderService $service): int
    {
        $type = $this->option('type');

        if ($type === 'all' || $type === 'bill_reminder') {
            $result = $service->runBillReminderBatch();
            $this->info("Bill Reminders → Sent: {$result['sent']}, Failed: {$result['failed']}");
        }

        if ($type === 'all' || $type === 'expiry_warning') {
            $result = $service->runExpiryWarningBatch();
            $this->info("Expiry Warnings → Sent: {$result['sent']}, Failed: {$result['failed']}");
        }

        return Command::SUCCESS;
    }
}
